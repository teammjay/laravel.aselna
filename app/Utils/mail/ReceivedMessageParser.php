<?php

namespace App\Utils\mail;


use App\Constants\HdTicketTypeCode;
use App\Constants\PrintStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReceivedMessageParser
{
    protected $breakRules = [];
    protected $entities = [];

    protected $item_title = null;
    protected $item_id = null;
    protected $sku = null;
    protected $message;
    protected $reference_id = null;
    protected $previousMessage = null;
    protected $ticketTypeCode;
    protected $marketplaceCustomer = false;
    protected $senderIsAccountOwner = false;
    protected $content = null;

    protected $defaultExtractors = [];
    protected $extractors = [];

    protected $parsedMessage = false;

    public function __construct($message)
    {
        $this->message = $message;
        $this->ticketTypeCode = HdTicketTypeCode::CUSTOMER_SERVICE;

        /*$this->breakRules = [
            new YahooBreakRule(),
            new GmailBreakRule(),
            new BeginForwardedBreakRule(),
            new DashForwardedBreakRule(),
            new SentFromDeviceRule(),
            new GetOutlookForBreakRule(),
            new FromToBreakRule(),
        ];*/
    }

    /*protected function processExtractors($extractors, $text, $processAll)
    {
        foreach ($extractors as $ext) {
            $extracted = $ext->extract($text);
            foreach ($extracted as $entity) {
                $this->addEntity($entity);
            }
            if (!$processAll && count($extracted) > 0) {
                break;
            }
        }
    }

    protected function addEntity($entity)
    {
        foreach ($this->entities as $existing) {
            if ($existing->equals($entity)) {
                return;
            }
        }
        $this->entities[] = $entity;
    }*/

    public function parse()
    {
        $parts = $this->message->parts;
        $htmlContent = null;

        foreach ($parts as $part) {
            $mimeType = strtolower($part->mime_type);

            if ($this->content === null && $mimeType === 'text/plain') {
                $this->content = trim($part->content);
                if (strlen($this->content) === 0) {
                    $this->content = null;
                }
            }

            if ($htmlContent === null && $mimeType === 'text/html') {
                $htmlContent = trim($part->content);
                if (strlen($htmlContent) === 0) {
                    $htmlContent = null;
                }
            }
        }

        if ($this->content === null && $htmlContent !== null) {
            $this->content = strip_tags($htmlContent);
        }

        if ($this->content !== null) {
            $this->content = trim($this->getMessage($this->content));
        }
    }

    protected function getMessage(string $text)
    {
        $result = [];
        $previous = [];
        $lines = preg_split("/\r?\n/", $text);

        $foundIndex = -1;

        foreach ($lines as $i => $line) {
            if (!empty($previous)) {
                $previous[] = $line;
                continue;
            }

            /*if ($foundIndex === -1) {
                foreach ($this->breakRules as $rule) {
                    $foundIndex = $rule->found($i, $lines);
                    if ($foundIndex >= 0) {
                        break;
                    }
                }
            }*/

            if ($foundIndex >= 0) {
                if ($foundIndex === 0) {
                    $previous[] = $line;
                } else {
                    $result[] = substr($line, 0, $foundIndex);
                    $previous[] = substr($line, $foundIndex + 1);
                }
            } else {
                $result[] = $line;
            }
        }

        if (!empty($previous)) {
            $this->previousMessage = implode("\n", $previous);
        }

        return implode("\n", $result);
    }

    public function getEntities()
    {
        return $this->entities;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getPreviousMessage()
    {
        return $this->previousMessage;
    }

    public function isParsedMessage()
    {
        return $this->parsedMessage;
    }
}
