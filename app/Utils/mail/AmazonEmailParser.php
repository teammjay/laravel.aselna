<?php

namespace App\Utils\mail;


use App\Constants\HdTicketTypeCode;
use App\Mail\Extractors\AmazonOrderIdExtractor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AmazonEmailParser extends ReceivedMessageParser
{
    protected static $escalationFromEmailAddresses = [];

    const MESSAGE_FROM_AMAZON_CUSTOMER_PREFIX = 'from amazon customer';
    const MESSAGE_INQUIRY = 'an inquiry from one of our customers';
    const BEGIN_MESSAGE = '--- Begin message --';
    const BEGIN_MESSAGE2 = 'Message:';
    const END_MESSAGE = '--- End message --';
    const END_MESSAGE_HTML_VERSION = 'Was this email helpful';
    const ORIGINAL_MESSAGE = '--- Original message --';
    const ORDER_ID_PREFIX = 'Order ID';
    const ASIN_PREFIX = 'ASIN';
    const RETURN_MESSAGE_START_PREFIX = 'Qty:';
    const REQUEST_RECEIVED_PREFIX = 'Request received';
    const WE_HAVE_BEEN_CONTACTED_PREFIX = "We've been contacted by a customer regarding";
    const RETURN_REQUESTED_PREFIX = 'return requested for order';
    const PACKAGE_DIDNOT_ARRIVE_PREFIX = "package didn't arrive";
    const SOLD_SHIP_NOW = 'sold, ship now';
    const REASON_PREFIX = 'Reason:';
    const ESC_TERMINATE_LINE_PREFIX = '-------------------';

    private $ticketTypeMap = [
        'Product details inquiry' => HdTicketTypeCode::ORDER_INFORMATION,
        'Product customization request' => HdTicketTypeCode::CUSTOMER_SERVICE,
        'Inquiry' => HdTicketTypeCode::CUSTOMER_SERVICE,
        'Shipping inquiry' => HdTicketTypeCode::SHIPPING,
        'Change shipping address inquiry' => HdTicketTypeCode::SHIPPING,
        'Ship faster inquiry' => HdTicketTypeCode::SHIPPING,
        'Order delivery inquiry' => HdTicketTypeCode::SHIPPING,
        'Thank you' => HdTicketTypeCode::FEEDBACK,
        'Order cancellation request' => HdTicketTypeCode::ORDER_CANCELLATION_REQUEST,
        'Return policy inquiry' => HdTicketTypeCode::RETURN_REQUEST,
        'Sold, ship now' => HdTicketTypeCode::MARKETPLACE_NOTIFICATION,
    ];

    protected $customer_order_number = null;

    public function __construct($message)
    {
        parent::__construct($message);
        $this->addExtractor(new AmazonOrderIdExtractor());
        $this->addExtractor(new UspsTrackingNumberExtractor());
    }

    public function getMessage($text)
    {
        $subject = strtolower($this->message->subject->first() ?? '');
        $from = strtolower($this->message->getFrom()[0]->mail ?? '');

        if (in_array($from, static::$escalationFromEmailAddresses)) {
            $this->ticketTypeCode = HdTicketTypeCode::ESCALATION;
            return $text;
        }

        if (str_starts_with($subject, self::SOLD_SHIP_NOW)) {
            $this->ticketTypeCode = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;
            return $this->getAmazonCustomerMessage($text);
        }

        if (str_contains($subject, self::MESSAGE_FROM_AMAZON_CUSTOMER_PREFIX)) {
            $prefix = trim(Str::before($subject, self::MESSAGE_FROM_AMAZON_CUSTOMER_PREFIX));
            if (isset(self::$ticketTypeMap[$prefix])) {
                $this->ticketTypeCode = self::$ticketTypeMap[$prefix];
                return $this->getAmazonCustomerMessage($text);
            }
        }

        if (str_starts_with($subject, self::RETURN_REQUESTED_PREFIX)) {
            $this->ticketTypeCode = HdTicketTypeCode::RETURN_REQUEST;
            return $this->getAmazonCustomerMessage($text, true, self::RETURN_MESSAGE_START_PREFIX, self::REQUEST_RECEIVED_PREFIX);
        }

        if (str_contains($subject, self::MESSAGE_INQUIRY)) {
            $this->ticketTypeCode = HdTicketTypeCode::ESCALATION;
            return $this->getAmazonCustomerMessage($text);
        }

        if (str_contains($subject, self::PACKAGE_DIDNOT_ARRIVE_PREFIX)) {
            $this->ticketTypeCode = HdTicketTypeCode::ITEM_NOT_RECEIVED;
            return $this->getAmazonCustomerMessage($text);
        }

        if (str_contains($subject, self::RETURN_REQUESTED_PREFIX)) {
            $this->ticketTypeCode = HdTicketTypeCode::RETURN_REQUEST;
            return $this->getAmazonCustomerMessage($text);
        }

        /*$text = parent::getMessage($text);
        $this->processExtractors($text, false);
        if ($subject !== '') {
            $this->processExtractors($subject, false);
        }

        return $text;*/
    }

    private function getAmazonCustomerMessage($text, $returnRequested = false, $startMessagePrefix = null, $terminateLinePrefix = null)
    {
        $lines = collect(explode("\n", $text))
                ->map(fn($line) => trim($line))
                ->filter()
                ->values();
        $result = [];
        $messageWithoutOrder = [];

        $orderIdExtractor = new AmazonOrderIdExtractor(ORDER_ID,'/(?<=\D|^)\d{17}(?=\D|$)|(?<=\D|^)\d{3}-\d{7}-\d{7}(?=\D|$)/');
        $asinExtractor = new AmazonOrderIdExtractor(ORDER_ID,"(?<=\\D|^)B[A-Z0-9]{9}(?=\\D|$)");

        $orderIdPrefix = false;
        $asinPrefixSeen = false;
        $messageStarted = false;

        for ($i = 0; $i < $lines->count(); $i++) {
            $line = $lines[$i];

            if (!$orderIdPrefix && str_contains($line, self::ORDER_ID_PREFIX)) {
                $orderIdPrefix = true;
            }

            if (!$asinPrefixSeen && str_contains($line, self::ASIN_PREFIX)) {
                $asinPrefixSeen = true;
            }

            if (!$this->customer_order_number && $orderIdPrefix) {
                $entities = $orderIdExtractor->extract($line);
                if (!empty($entities)) {
                    $this->customer_order_number = $entities[0];
                    $this->addEntity($this->customer_order_number, 'order_id');
                }
            }

            if (!$this->customer_order_number && $asinPrefixSeen) {
                $entities = $asinExtractor->extract($line);
                if (!empty($entities)) {
                    $this->customer_order_number = $entities[0]->getText();
                    $this->addEntity($this->customer_order_number, 'item_id');
                }
            }

            if ($returnRequested && str_contains($line, $startMessagePrefix)) {
                $messageStarted = true;
            } elseif (!$returnRequested && str_starts_with($line, self::WE_HAVE_BEEN_CONTACTED_PREFIX)) {
                $returnRequested = true;
                $startMessagePrefix = self::REASON_PREFIX;
                $terminateLinePrefix = self::ESC_TERMINATE_LINE_PREFIX;
            } elseif (str_contains($line, self::BEGIN_MESSAGE) || str_contains($line, self::BEGIN_MESSAGE2)) {
                if (!$returnRequested && $lines->get($i + 4) === "We've been contacted by a customer regarding the order identified below.") {
                    $returnRequested = true;
                    $startMessagePrefix = self::REASON_PREFIX;
                    $terminateLinePrefix = self::ESC_TERMINATE_LINE_PREFIX;
                } else {
                    $messageStarted = true;
                }
                continue;
            } elseif (str_contains($line, self::ORIGINAL_MESSAGE) || str_contains($line, self::END_MESSAGE) || str_contains($line, self::END_MESSAGE_HTML_VERSION)) {
                $messageStarted = false;
                break;
            } elseif ($returnRequested && $messageStarted && str_contains($line, $terminateLinePrefix)) {
                $messageStarted = false;
                break;
            }

            if ($messageStarted) {
                $result[] = $line;
            } elseif (!$this->customer_order_number) {
                $messageWithoutOrder[] = $line;
            }
        }

        $processedMessage = !empty($result) ? implode("\n", $result) : implode("\n", $messageWithoutOrder);
        $this->parsedMessage = true;

        return parent::getMessage($processedMessage);
    }

    protected function addEntity($value, $type)
    {
        $this->entities[$type] = str_replace("&nbsp; ", "", html_entity_decode(trim($value)));
    }
}
