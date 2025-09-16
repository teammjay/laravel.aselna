<?php

namespace App\Utils\mail;


use App\Constants\HdTicketTypeCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EbayEmailParser extends ReceivedMessageParser
{
    protected $customer_order_number;
    protected $message;
    protected $parsedMessage;
    protected static $marketplaceEmails = ['ebay@ebay.com'];
    protected static $ticketTypeMap = [
        'order #' => HdTicketTypeCode::ORDER_INFORMATION,
    ];
    const PREFIXES = [
        'customer_name' => 'Customer Name:',
        'customer_order_number' => 'Order number:',
        'item_id' => 'Item ID:',
        'item_title' => 'Item name/number:',
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
        //echo $subject.'<br>';
        // echo $from;

        if (preg_match('/\b\d{2}-\d{5}-\d{5}\b/', $subject, $matches)) {
            $orderId = $matches[1];
            $this->ticketTypeCode = HdTicketTypeCode::ORDER_INFORMATION;
            $this->addEntity(trim($this->ticketTypeCode), 'ticket_type_id');
            $this->addEntity(trim($orderId), 'order_id');
        }

        if (str_contains($subject, 'sent a message about')) {
            $this->ticketTypeCode = HdTicketTypeCode::ORDER_INFORMATION;
            $this->addEntity(trim($this->ticketTypeCode), 'ticket_type_id');

            if (preg_match('/^(.*?)\s+sent a message/i', $subject, $matches)) {
                $username = $matches[1];
                $this->addEntity(trim($username), 'username');
            }
        }

        if (in_array($from, self::$marketplaceEmails)) {
            $this->ticketTypeCode = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;
            $this->addEntity(trim($this->ticketTypeCode), 'ticket_type_id');
            return parent::getMessage($text);
        }

        return $this->parseCustomerMessage($text);

        /*$parsedText = parent::getMessage($text);
        $this->processExtractors($parsedText);
        if ($subject) {
            $this->processExtractors($subject);
        }

        return $parsedText;*/
    }


    protected function parseCustomerMessage($text)
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $message = [];
        $itemTitle = [];
        $messageStarted = false;

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), self::PREFIXES['customer_name'])) {
                $this->customer_name = trim(substr(trim($line), strlen(self::PREFIXES['customer_name'])));
                $this->addEntity($this->customer_name, 'customer_name');
            }elseif (str_starts_with(trim($line), self::PREFIXES['customer_order_number'])) {
                $this->customer_order_number = trim(substr(trim($line), strlen(self::PREFIXES['customer_order_number'])));
                $this->addEntity($this->customer_order_number, 'order_id');
            }elseif (str_starts_with(trim($line), self::PREFIXES['item_id'])) {
                $this->item_id = trim(substr(trim($line), strlen(self::PREFIXES['item_id'])));
                $this->addEntity($this->item_id, 'item_id');
            }elseif ($messageStarted) {
                $message[] = $line;
            }elseif (!empty($itemTitle)) {
                $itemTitle[] = trim($line);
            }

        }

        if (preg_match('/\b\d{2}-\d{5}-\d{5}\b/', $text, $matches)){
            $orderId = $matches[0];
            ///echo $orderId;
            $this->addEntity(trim($orderId), 'order_id');
        }elseif (preg_match('/\b\d{2}\s*-\s*\d{5}\s*-\s*\d{5}\b/', $text, $matches)) {
            $orderId = preg_replace('/\s+/', '', $matches[0]);
            //echo $orderId;
            $this->addEntity(trim($orderId), 'order_id');
        }

        /*echo '<pre>';
        echo 'message';
        print_r($message);
        echo '</pre>';*/

        $this->parsedMessage = true;
        return implode("\n", $message);
    }


    protected function addEntity($value, $type)
    {
        $this->entities[$type] = str_replace("&nbsp; ", "", trim($value));
    }
}
