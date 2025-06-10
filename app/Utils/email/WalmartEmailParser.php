<?php

namespace App\Utils\email;


use App\Constants\HdTicketTypeCode;
use App\Constants\PrintStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WalmartEmailParser
{
    protected $orderId;
    protected $message;
    protected static $escalationEmails = ['help@walmart.com'];
    protected static $marketplaceEmails = ['newsletters@em.walmart.com'];
    protected static $ticketTypeMap = [
        'cancel my order' => HdTicketTypeCode::ORDER_CANCELLATION_REQUEST,
        'cancel order' => HdTicketTypeCode::ORDER_CANCELLATION_REQUEST,
        'initiate return' => HdTicketTypeCode::RETURN_REQUEST,
        'initiate a return' => HdTicketTypeCode::RETURN_REQUEST,
        'request refund' => HdTicketTypeCode::ORDER_REFUND_REQUEST,
        'request a refund' => HdTicketTypeCode::ORDER_REFUND_REQUEST,
        'track order' => HdTicketTypeCode::SHIPPING,
        'ask question' => HdTicketTypeCode::ORDER_INFORMATION,
        'feedback' => HdTicketTypeCode::FEEDBACK,
        'escalation' => HdTicketTypeCode::ESCALATION,
        'where is my order,' => HdTicketTypeCode::ORDER_INFORMATION,
    ];
    const PREFIXES = [
        'customer_order_number' => 'Customer Order Number:',
        'item_id' => 'Item ID:',
        'item_title' => 'Item name/number:',
        'message' => 'Customer Message:',
        'note' => 'What you need to do now:',
        'ref_id' => 'RefID:',
    ];

    const ESCALATION_LABELS = ['this is an escalation', 'as an escalation'];
    const DESCRIPTION_LABEL = 'description of issue:';
    const DESCRIPTION_END_LABEL = 'what you need to do now';
    const ORDER_NUMBER_LABELS = ['order number:', 'order id:'];
    const DO_NOT_REPLY = 'do not reply to this email';

    const CUSTOMER_INFO_LABELS = [
        'name' => 'customer name:',
        'phone' => ['customer phone number:', 'customer phone:'],
        'reason' => 'reason for contact:',
    ];
    public function __construct($message)
    {
        $this->message = $message;
    }

    public function getMessage($text)
    {
        $subject = strtolower($this->message->subject->first() ?? '');
        $from = strtolower($this->message->getFrom()[0]->mail ?? '');

        if (in_array($from, self::$escalationEmails)) {
            $isTransferred = str_starts_with($subject, 'confirmation of customer transferred to your team');
            $parsed = $this->parseEscalation($text, $isTransferred);
            if ($parsed) {
                $this->ticketTypeCode = HdTicketTypeCode::ESCALATION;
                return $parsed;
            }
        }

        if (str_starts_with($subject, 'message from walmart.com customer:')) {
            $remaining = trim(substr($subject, strlen('message from walmart.com customer:')));
            $parts = explode(',', $remaining);

            if (count($parts) === 2) {
                $ticketTypeKey = trim(strtolower($parts[0]));
                $this->ticketTypeCode = self::$ticketTypeMap[$ticketTypeKey] ?? null;
                $this->addEntity(trim($parts[1]), 'order_id');
            }

            return $this->parseCustomerMessage($text);
        }

        if (in_array($from, self::$marketplaceEmails)) {
            $this->ticketTypeCode = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;
            return parent::getMessage($text);
        }

        $parsedText = parent::getMessage($text);
        $this->processExtractors($parsedText);
        if ($subject) {
            $this->processExtractors($subject);
        }

        return $parsedText;
    }

    protected function parseEscalation($text, $transferred)
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        if (count($lines) < 10) return null;

        $data = [
            'order_id' => null,
            'customer_name' => null,
            'customer_phone' => null,
            'reason' => null,
            'description' => [],
        ];

        $foundEscalation = false;
        $foundDoNotReply = false;

        foreach ($lines as $line) {
            $lower = strtolower($line);

            if (!$foundEscalation && str_contains($lower, self::ESCALATION_LABELS[0]) || str_contains($lower, self::ESCALATION_LABELS[1])) {
                $foundEscalation = true;
                continue;
            }

            if (!$transferred && !$foundDoNotReply && str_contains($lower, self::DO_NOT_REPLY)) {
                $foundDoNotReply = true;
                continue;
            }

            if (str_starts_with($lower, self::DESCRIPTION_END_LABEL)) break;

            foreach (self::ORDER_NUMBER_LABELS as $label) {
                if (!$data['order_id'] && str_starts_with($lower, $label)) {
                    $data['order_id'] = trim(substr($line, strlen($label)));
                }
            }

            if (!$data['customer_name'] && str_starts_with($lower, self::CUSTOMER_INFO_LABELS['name'])) {
                $data['customer_name'] = trim(substr($line, strlen(self::CUSTOMER_INFO_LABELS['name'])));
            }

            foreach (self::CUSTOMER_INFO_LABELS['phone'] as $phoneLabel) {
                if (!$data['customer_phone'] && str_starts_with($lower, $phoneLabel)) {
                    $data['customer_phone'] = trim(substr($line, strlen($phoneLabel)));
                }
            }

            if (!$data['reason'] && str_starts_with($lower, self::CUSTOMER_INFO_LABELS['reason'])) {
                $data['reason'] = trim(substr($line, strlen(self::CUSTOMER_INFO_LABELS['reason'])));
            }

            if (str_starts_with($lower, self::DESCRIPTION_LABEL)) {
                $desc = trim(substr($line, strlen(self::DESCRIPTION_LABEL)));
                if ($desc) {
                    $data['description'][] = $desc;
                }
                continue;
            }

            if (!empty($data['description'])) {
                $data['description'][] = $line;
            }
        }

        if (!$data['order_id'] || empty($data['description'])) {
            return null;
        }

        $this->addEntity($data['order_id'], 'order_id');
        if ($data['customer_phone']) {
            $this->addEntity($data['customer_phone'], 'phone_number');
        }

        $output = [
            strtoupper(self::ESCALATION_LABELS[0]),
            '',
            "Order ID: {$data['order_id']}",
        ];

        if ($data['customer_name']) $output[] = "Customer Name: {$data['customer_name']}";
        if ($data['customer_phone']) $output[] = "Customer Phone: {$data['customer_phone']}";

        $output[] = '';
        $output[] = strtoupper(self::DESCRIPTION_LABEL);
        $output[] = '';
        $output[] = implode("\n", $data['description']);

        $this->parsedMessage = true;
        return implode("\n", $output);
    }

    protected function parseCustomerMessage($text)
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $message = [];
        $itemTitle = [];
        $messageStarted = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, self::PREFIXES['customer_order_number'])) {
                $this->orderId = trim(substr($line, strlen(self::PREFIXES['customer_order_number'])));
                $this->addEntity($this->orderId, 'order_id');
            } elseif (str_starts_with($line, self::PREFIXES['item_id'])) {
                $this->itemId = trim(substr($line, strlen(self::PREFIXES['item_id'])));
            } elseif (str_starts_with($line, self::PREFIXES['item_title'])) {
                $itemTitle[] = trim(substr($line, strlen(self::PREFIXES['item_title'])));
            } elseif (str_starts_with($line, self::PREFIXES['message'])) {
                $messageStarted = true;
                $this->itemTitle = implode(" ", $itemTitle);
            } elseif (str_starts_with($line, self::PREFIXES['note'])) {
                $messageStarted = false;
            } elseif (str_starts_with($line, self::PREFIXES['ref_id'])) {
                $this->referenceId = trim(substr($line, strlen(self::PREFIXES['ref_id'])));
            } elseif ($messageStarted) {
                $message[] = $line;
            } elseif (!empty($itemTitle)) {
                $itemTitle[] = $line;
            }
        }

        $this->parsedMessage = true;
        return implode("\n", $message);
    }

    protected function addEntity($value, $type)
    {
        $this->entities[] = new Entity($value, $type);
    }
}
