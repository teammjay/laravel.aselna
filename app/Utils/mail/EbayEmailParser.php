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

        if (str_contains($subject, 'Issue refund')) {
            $this->ticketTypeCode = HdTicketTypeCode::ORDER_REFUND_REQUEST;
            $this->addEntity(trim($this->ticketTypeCode), 'ticket_type_id');
        }

        if (str_starts_with($subject, 'Return')) {
            $this->ticketTypeCode = HdTicketTypeCode::RETURN_REQUEST;
            $this->addEntity(trim($this->ticketTypeCode), 'ticket_type_id');
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

        //Log::info('Lines : '.json_encode($lines));
        $message = [];
        $itemTitle = [];
        $messageStarted = false;

        if($lines) {
            foreach ($lines as $line) {
                if (stripos($line, self::PREFIXES['customer_name']) === 0) {
                    $this->customer_name = trim(substr(trim($line), strlen(self::PREFIXES['customer_name'])));
                    $this->addEntity($this->customer_name, 'customer_name');
                }elseif (stripos($line, self::PREFIXES['customer_order_number']) === 0) {
                    $this->customer_order_number = trim(substr(trim($line), strlen(self::PREFIXES['customer_order_number'])));
                    $this->addEntity($this->customer_order_number, 'order_id');
                } elseif (stripos($line, self::PREFIXES['item_id']) === 0) {
                    $this->item_id = trim(substr(trim($line), strlen(self::PREFIXES['item_id'])));
                    $this->addEntity($this->item_id, 'item_id');
                } elseif ($messageStarted) {
                    $message[] = $line;
                } elseif (!empty($itemTitle)) {
                    $itemTitle[] = trim($line);
                }

            }
        }

        if (preg_match('/Order number:\s*([\r\n]+)?\s*(\d{2}-\d{5}-\d{5})/i', $text, $matches)) {
            $orderId = trim($matches[2]);
            $this->addEntity($orderId, 'order_id');
        }else if (preg_match('/Order number:\s*[\r\n]+([\d]{2}-[\d]{5}-[\d]{5})/i', $text, $matches)) {
            $orderId = trim($matches[1]);
            $this->addEntity($orderId, 'order_id');
        }else if (preg_match('/\b\d{2}-\d{5}-\d{5}\b/', $text, $matches)){
            $orderId = $matches[0];
            ///echo $orderId;
            $this->addEntity(trim($orderId), 'order_id');
        }elseif (preg_match('/\b\d{2}\s*-\s*\d{5}\s*-\s*\d{5}\b/', $text, $matches)) {
            $orderId = preg_replace('/\s+/', '', $matches[0]);
            //echo $orderId;
            $this->addEntity(trim($orderId), 'order_id');
        }

        $html = $this->message->getHtmlBody();
        if($html) {

            $html = preg_replace('/&(?!#?[a-zA-Z0-9]+;)/', '&amp;', $html);
            $html = preg_replace('/&nbsp(?!;)/i', '&nbsp;', $html);
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML(
                $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR
            );

            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // Order ID (inside <a> with orderId param)
            $orderNode = $xpath->query("//a[contains(@href,'orderId=')]");
            $orderId = $orderNode->length > 0 ? trim($orderNode->item(0)->nodeValue) : null;
            if ($orderId) {
                $this->addEntity(trim($orderId), 'order_id');
            }

            $orderNode = $xpath->query("//td[contains(text(),'Order number:')]/following-sibling::td");
            $itemId = $orderNode->length > 0 ? trim($orderNode->item(0)->nodeValue) : null;
            if ($itemId) {
                $this->addEntity(trim($itemId), 'item_id');
            }

            // Item ID (numeric only, after 'Item Id:')
            $itemIdNode = $xpath->query("//td[contains(text(),'Item Id:')]/following-sibling::td");
            $itemId = $itemIdNode->length > 0 ? trim($itemIdNode->item(0)->nodeValue) : null;
            if ($itemId) {
                $this->addEntity(trim($itemId), 'item_id');
            }

            // Buyer ID
            $buyerIdNode = $xpath->query("//td[p[contains(text(),'Buyer Id:')]]/following-sibling::td/p");
            $buyerId = $buyerIdNode->length > 0 ? trim($buyerIdNode->item(0)->nodeValue) : null;
            if ($buyerId) {
                $this->addEntity(trim($buyerId), 'username');
            }

            // Return ID

            $returnIdNode = $xpath->query("//td[p[contains(text(),'Return Id:')]]/following-sibling::td/p");
            $returnId = $returnIdNode->length > 0 ? trim($returnIdNode->item(0)->nodeValue) : null;
            if ($returnId) {
                $this->addEntity(trim($returnId), 'return_id');
            }

            $nodes = $xpath->query("//td[contains(@class,'product-bids')]");
            //Log::info('Nodes : '.json_encode($nodes));

            if ($nodes) {
                foreach ($nodes as $node) {
                    $content = preg_replace('/<br\s*\/?>/i', "\n", $dom->saveHTML($node));
                    $content = strip_tags($content);
                    $lines = array_map('trim', explode("\n", $content));

                    foreach ($lines as $line) {
                        if (preg_match('/^Item ID:\s*(\d+)/i', $line, $m)) {
                            $this->addEntity(trim($m[1]), 'item_id');
                        }
                        if (preg_match('/^Order number:\s*([\d-]+)/i', $line, $m)) {
                            $this->addEntity(trim($m[1]), 'order_id');
                        }
                    }
                }
            }
        }

        /*echo '<pre>';
        echo 'message';
        print_r($message);
        echo '</pre>';*/

        $this->parsedMessage = true;
        return true;
    }


    protected function addEntity($value, $type)
    {
        $this->entities[$type] = str_replace("&nbsp; ", "", trim($value));
    }
}
