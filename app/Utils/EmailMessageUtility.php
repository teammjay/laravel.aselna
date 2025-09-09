<?php

namespace App\Utils;

use App\Utils\mail\AmazonEmailParser;
use App\Utils\mail\ShopifyEmailParser;
use App\Utils\mail\WalmartEmailParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Mailable;
use Webklex\PHPIMAP\ClientManager;
use Exception;

class EmailMessageUtility
{
    protected $smtpConfig;
    protected $imapConfig;
    protected $client;

    public function __construct(array $smtpConfig = [], array $imapConfig = [])
    {
        $this->smtpConfig = $smtpConfig;
        $this->imapConfig = $imapConfig;

        if (!empty($smtpConfig)) {
            $this->setMailConfig();
        }

        if (!empty($imapConfig)) {
            $this->initImapClient();
        }
    }

    protected function setMailConfig()
    {
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $this->smtpConfig['host'],
            'port' => $this->smtpConfig['port'],
            'encryption' => $this->smtpConfig['encryption'],
            'username' => $this->smtpConfig['username'],
            'password' => $this->smtpConfig['password'],
            'timeout' => null,
            'auth_mode' => null,
        ]);

        Config::set('mail.from.address', $this->smtpConfig['from_address'] ?? $this->smtpConfig['username']);
        Config::set('mail.from.name', $this->smtpConfig['from_name'] ?? 'SMTP Sender');
        Config::set('mail.default', 'smtp');
    }

    protected function initImapClient()
    {
        $this->client = (new ClientManager())->make([
            'host'          => $this->imapConfig['host'],
            'port'          => $this->imapConfig['port'],
            'encryption'    => $this->imapConfig['encryption'],
            'validate_cert' => $this->imapConfig['validate_cert'] ?? true,
            'username'      => $this->imapConfig['username'],
            'password'      => $this->imapConfig['password'],
            'protocol'      => 'imap',
        ]);
    }

    public function sendEmail($toEmail, $subject = '', $message = '', $message_id = '', $attachment = '')
    {
        try {

            /*if($toEmail) {
                Mail::raw($message, function ($m) use ($toEmail, $subject, $message_id, $attachment) {
                    $m->to($toEmail)->subject('Re: '.$subject);
                    if($message_id != '') {
                        $m->getHeaders()->addTextHeader('In-Reply-To', '<'.$message_id.'>');
                        $m->getHeaders()->addTextHeader('References', '<'.$message_id.'>');
                    }
                    if($attachment != '' && file_exists($attachment)) {
                        $m->attach($attachment);
                    }
                });
            }*/

            if($toEmail) {

                Log::info("Email New Content". $message);

                Mail::send([], [], function ($m) use ($toEmail, $subject, $message_id, $attachment, $message) {
                    $m->to($toEmail)
                        ->subject('Re: ' . $subject)
                        ->html($message);

                    if (!empty($message_id)) {
                        $m->getHeaders()->addTextHeader('In-Reply-To', '<' . $message_id . '>');
                        $m->getHeaders()->addTextHeader('References', '<' . $message_id . '>');
                    }

                    if (!empty($attachment) && file_exists($attachment)) {
                        $m->attach($attachment);
                    }
                });
            }
            Log::info("Email sent successfully to {$toEmail}.");
            return true;
        } catch (Exception $e) {
            Log::info("Failed to send email: " . $e->getMessage());
            return false;
        }
    }



    public function fetchEmails($store_type, $filter_date, $folderName = 'INBOX', $limit = 20)
    {
        try {
            $this->client->connect();

            $filterDateObj = \Carbon\Carbon::parse($filter_date, 'America/New_York');
            $today = \Carbon\Carbon::now('America/New_York')->addDay();
            $maxDate = $filterDateObj->copy()->addDays(5);

            $toDate = $maxDate->lessThan($today) ? $maxDate : $today;

            Log::info("Fetching email headers from folder: {$folderName} with filter date {$filterDateObj->toDateTimeString()} to {$toDate->toDateTimeString()} and limit {$limit}");

            $folder = $this->client->getFolder($folderName);

            $searchDateCriteria = $filterDateObj->format('d-M-Y');
            $toDateCriteria = $toDate->format('d-M-Y');

            $query = $folder->query()
                ->where('SINCE', $searchDateCriteria)
                ->where('BEFORE',$toDateCriteria)
                ->setFetchOptions(\Webklex\PHPIMAP\IMAP::FT_UID | \Webklex\PHPIMAP\IMAP::FT_PEEK);

            $messages = $query->get();

            // Filter messages by exact time - more precise than IMAP can do
            $filteredMessages = collect($messages)->filter(function($message) use ($filterDateObj) {
                try {
                    Log::info('Message checking :: ' . $message->getDate());
                    $messageDate = $message->getDate()->first();
                    return $messageDate->greaterThanOrEqualTo($filterDateObj);
                } catch (Exception $e) {
                    Log::warning("Error filtering message date: " . $e->getMessage());
                    return false;
                }
            })->take($limit)->values();

            Log::info("Found " . count($filteredMessages) . " messages matching the time criteria");

            $emails = [];
            foreach ($filteredMessages as $key => $message) {
                try {
                    // Log the subject we're processing
                    $subject = $message->subject->first() ?? 'No Subject';
                    Log::info("Processing email #{$key}: {$subject}");

                    // Step 2: Now fetch the full message content for this specific message
                    $uid = $message->getUid();
                    $fullMessage = $folder->query()->whereUid($uid)->get()->first();

                    // Get the body content
                    $body = $fullMessage->getTextBody();
                    if (empty($body)) {
                        $body = $fullMessage->getHtmlBody();
                    }

                    // Process data for this specific message
                    $parsedData = [];
                    if($store_type != '') {
                        switch (strtolower($store_type)) {
                            case 'walmart':
                                $walmartEmailParser = new WalmartEmailParser($fullMessage);
                                $walmartEmailParser->getMessage(strip_tags($body));
                                $parsedData = $walmartEmailParser->getEntities();
                                break;
                            case 'amazon':
                                $amazonEmailParser = new AmazonEmailParser($fullMessage);
                                $amazonEmailParser->getMessage(strip_tags($body));
                                $parsedData = $amazonEmailParser->getEntities();
                                break;
                            case 'shopify':
                                $shopifyEmailParser = new ShopifyEmailParser($fullMessage);
                                $shopifyEmailParser->getMessage(strip_tags($body));
                                $parsedData = $shopifyEmailParser->getEntities();
                                break;
                        }
                    }

                    // Handle message structure
                    $structure = $fullMessage->getStructure();
                    $part = isset($structure->parts[0]) ? $structure->parts[0] : null;

                    $parts = [];
                    if ($part) {
                        $parts = [
                            'mime_type' => $part->content_type ?? null,
                            'content' => $part->content ?? null,
                            'charset' => $part->charset ?? null,
                        ];
                    }

                    if(str_contains($body, '<html>')) {
                        $body = $this->formatHtml($body);
                    }

                    $attachments = [];
                    if ($fullMessage->hasAttachments()) {
                        $attachments = $fullMessage->getAttachments();
                    }

                    $mDate = $fullMessage->getDate()->first();
                    $convertedDate = $mDate->copy()->setTimezone(config('app.timezone'));
                    $date = $convertedDate->format('Y-m-d H:i:s');

                    // Build the email data array
                    $eData = [
                        'subject' => $fullMessage->subject->first(),
                        'full_name' => $fullMessage->fromaddress->first(),
                        'name' => isset($fullMessage->getFrom()[0]) ? ($fullMessage->getFrom()[0]->personal ?? null) : null,
                        'from' => isset($fullMessage->getFrom()[0]) ? ($fullMessage->getFrom()[0]->mail ?? null) : null,
                        'reply_to' => isset($fullMessage->getReplyTo()[0]) ? ($fullMessage->getReplyTo()[0]->mail ?? null) : null,
                        'reply_to_name' => isset($fullMessage->getReplyTo()[0]) ? ($fullMessage->getReplyTo()[0]->full ?? null) : null,
                        'to' => isset($fullMessage->getTo()[0]) ? ($fullMessage->getTo()[0]->mail ?? null) : null,
                        'cc' => isset($fullMessage->getCc()[0]) ? ($fullMessage->getCc()[0]->mail ?? null) : null,
                        'bcc' => isset($fullMessage->getBcc()[0]) ? ($fullMessage->getBcc()[0]->mail ?? null) : null,
                        'message_id' => $fullMessage->message_id->first(),
                        'date' => $date,
                        'parsedData' => $parsedData,
                        'parts' => $parts,
                        'body' => $body,
                        'in_reply_to' => $fullMessage->in_reply_to->first(),
                        'references' => $fullMessage->references->all(),
                        'attachments' => $attachments,
                        'client_id' => null
                    ];

                    $emails[] = $eData;

                    // Free up memory by unsetting large objects
                    unset($fullMessage);
                    unset($body);
                    unset($parsedData);

                    // Allow garbage collection to free memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                } catch (Exception $e) {
                    Log::error("Error processing message #{$key}: " . $e->getMessage());
                }
            }

            Log::info("Successfully processed " . count($emails) . " emails");
            return $emails;
        } catch (Exception $e) {
            Log::error("Failed to fetch emails: " . $e->getMessage());
            return ['error' => 'Failed to fetch emails: ' . $e->getMessage()];
        }
    }

    private function formatHtml($emailHtml){

        // Remove multiple <br> in a row
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $emailHtml);

// Remove empty <p> and <div>
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);
        $html = preg_replace('/<div>\s*<\/div>/i', '', $html);

        // Optionally trim spaces between tags
        $html = preg_replace('/>\s+</', '><', $html);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true); // suppress parsing warnings
        $dom->loadHTML($html);

// Extract body content
        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHTML = '';
        foreach ($body->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }
        libxml_clear_errors();

        return $innerHTML;
    }

    private function parseEscalationEmail($emailBody){

        $data = [];

        // Extract Customer Name
        if (preg_match('/Customer Name:\s*(.+)/', $emailBody, $matches)) {
            $data['customer_name'] = trim($matches[1]);
        }

        // Extract Customer Order Number
        if (preg_match('/Customer Order Number:\s*(.+)/', $emailBody, $matches)) {
            $data['customer_order_number'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Customer Order Number
        if (preg_match('/Sales Order #:\s*(.+)/', $emailBody, $matches)) {
            $data['customer_order_number'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Purchase Order No
        if (preg_match('/Purchase Order No:\s*(.+)/', $emailBody, $matches)) {
            $data['purchase_order_number'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Reason for Contact
        if (preg_match('/Reason for Contact:\s*(.+)/', $emailBody, $matches)) {
            $data['reason'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Item Name/Number
        if (preg_match('/Item name\/number:\s*(.+)/', $emailBody, $matches)) {
            $data['item_name'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Item ID
        if (preg_match('/Item ID:\s*(.+)/', $emailBody, $matches)) {
            $data['item_id'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        // Extract Agent Message
        if (preg_match('/Agent message:\s*(.+)/', $emailBody, $matches)) {
            $data['agent_message'] = html_entity_decode(str_replace("&nbsp;", "", trim($matches[1])));
        }

        return $data;
    }
}
