<?php

namespace App\Utils;

use App\Services\CriticalErrorService;
use App\Utils\mail\AmazonEmailParser;
use App\Utils\mail\EbayEmailParser;
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
            'host' => $this->imapConfig['host'],
            'port' => $this->imapConfig['port'],
            'encryption' => $this->imapConfig['encryption'],
            'validate_cert' => $this->imapConfig['validate_cert'] ?? true,
            'username' => $this->imapConfig['username'],
            'password' => $this->imapConfig['password'],
            'protocol' => 'imap',
        ]);
    }

    public function sendEmail($toEmail, $subject = '', $message = '', $message_id = '', $attachments = '', $referencesHeader = '')
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

            if ($toEmail) {

                Log::info("Email New Content" . $message);

                Mail::send([], [], function ($m) use ($toEmail, $subject, $message_id, $attachments, $message, $referencesHeader) {
                    $m->to($toEmail)
                        ->subject('Re: ' . $subject)
                        ->html($message);

                    if (!empty($message_id)) {
                        $m->getHeaders()->addTextHeader('In-Reply-To', '<' . $message_id . '>');
                        if($referencesHeader != '') {
                            $m->getHeaders()->addTextHeader('References', $referencesHeader);
                        }
                    }

                    if (!empty($attachments) && is_array($attachments)) {
                        foreach ($attachments as $attachment){
                            if(file_exists($attachment) && is_file($attachment)) {
                                $m->attach($attachment);
                            }
                        }
                    }
                });
            }
            Log::info("Email sent successfully to {$toEmail}.");
            return true;
        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Email message send fail', [
                'marketplace' => '',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['toemail' => $toEmail]);

            Log::info("Failed to send email: " . $e->getMessage());
            return false;
        }
    }


    public function fetchTodaysEmail($folderName = 'INBOX')
    {
        try {
            $this->client->connect();

            // echo php version
            //echo 'PHP version: ' . phpversion();

            $today = \Carbon\Carbon::now('America/New_York')->subDay();
            $searchDateCriteria = $today->format('d-M-Y');

            Log::info("Fetching email headers from folder: {$folderName} with filter date {$today->toDateTimeString()}");

            $folder = $this->client->getFolder($folderName);

            $query = $folder->query()
                ->whereText('SINCE', $searchDateCriteria)
                ->setFetchOptions(\Webklex\PHPIMAP\IMAP::FT_UID)
                ->limit(10)
                ->setFetchBody(false) // Don't fetch the full body content initially
                ->setFetchFlags(false); // Skip flags to reduce data transfer
//                ->setFetchAttachment(false); // Skip attachments

            $messages = $query->get();
            $messageCount = $messages->count();
            Log::info("Found {$messageCount} messages matching criteria");

            $messageData = [];

            foreach ($messages as $key => $message) {

                $uid = $message->getUid();
                Log::info("Processing email #{$key} with UID: {$uid}");
//                $fullMessage = $folder->query()->whereUid($uid)->get()->first();

                $messageData[] = [
                    'message_id' => $message->message_id->first(),
                    'subject' => $message->subject->first()
                ];

//                Log::info("Fetched email with Subject: " . $fullMessage->subject->first());

//                unset($fullMessage);

                if ($key % 50 === 0 && $key > 0) {
                    Log::info("Processed {$key} messages, running garbage collection");
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }

                    // Log memory usage
                    $memoryUsage = memory_get_usage(true) / 1024 / 1024;
                    Log::info("Current memory usage: {$memoryUsage} MB");
                }
            }

            return $messageData;
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error("Failed to fetch today's emails: " . $e->getMessage());
            return [];
        }
    }

    public function fetchEmails($store_type, $filter_date, $folderName = 'INBOX', $limit = 10, $storeName = null)
    {
        try {
            $this->client->connect();

            $filterDateObj = \Carbon\Carbon::parse($filter_date, 'America/New_York');
            $today = \Carbon\Carbon::now('America/New_York')->addDay();
            $maxDate = $filterDateObj->copy()->addDays(3);

            $toDate = $maxDate->lessThan($today) ? $maxDate : $today;

            Log::info("Fetching email headers from folder: {$folderName} with filter date {$filterDateObj->toDateTimeString()} to {$toDate->toDateTimeString()} and limit {$limit}");

            $folder = $this->client->getFolder($folderName);

            $searchDateCriteria = $filterDateObj->format('d-M-Y');
            $toDateCriteria = $toDate->format('d-M-Y');

//            $query = $folder->query()
//                ->where('SINCE', $searchDateCriteria)
//                ->where('BEFORE', $toDateCriteria)
//                ->setFetchOptions(\Webklex\PHPIMAP\IMAP::FT_UID | \Webklex\PHPIMAP\IMAP::FT_PEEK);
//
//            $messages = $query->get();
            $batchSize = 10;

            $filteredMessages = collect();
            $page = 1;

            do {
                Log::info('Page ::' . $page . ' :: Fetching batch of size ' . $batchSize);
                $query = $folder->query()
                    ->since($searchDateCriteria)
                    ->before($toDateCriteria)
                    ->limit($batchSize, $page)
                    ->setFetchOptions(\Webklex\PHPIMAP\IMAP::FT_UID | \Webklex\PHPIMAP\IMAP::FT_PEEK)
                    //->setFetchOptions(\Webklex\PHPIMAP\IMAP::FT_UID)
                    ->setFetchBody(false)
                    ->setFetchFlags(false);

                $tempMessages = $query->get();
                Log::info("Fetched batch {$page} with " . $tempMessages->count() . " messages");

                if ($tempMessages->count() === 0) {
                    break;
                }

                // Filter messages by exact time within the loop
                $filteredBatch = $tempMessages->filter(function ($message) use ($filterDateObj) {
                    try {
                        $messageDate = $message->getDate()->first();
                        if ($messageDate === null || !$messageDate instanceof \Carbon\Carbon) {
                            return true;
                        }
                        Log::info("Message date: " . $messageDate->toDateTimeString() . " | Filter date: " . $filterDateObj->toDateTimeString());
                        return $messageDate->greaterThan($filterDateObj);
                    } catch (Exception $e) {
                        Log::warning("Error filtering message date: " . $e->getMessage());
                        return false;
                    }
                });

                $filteredMessages = $filteredMessages->merge($filteredBatch);
                Log::info("Added " . $filteredBatch->count() . " filtered messages. Total filtered so far: " . $filteredMessages->count());

                // Break the loop if we've already reached our limit
                if ($filteredMessages->count() >= $limit) {
                    $filteredMessages = $filteredMessages->take($limit);
                    break;
                }

                $page++;

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }

                sleep(1); // prevent server throttling
                Log::info('Temp Messages Count :: ' . $tempMessages->count());
                $query = null;
            //} while ($tempMessages->count() > 0 && !($tempMessages->count() < $batchSize) && $filteredMessages->count() <= $limit);
            } while ($tempMessages->count() === $batchSize && $filteredMessages->count() < $limit);

            $filteredMessages = $filteredMessages->values();

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
                    if ($store_type != '') {
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
                            case 'ebay':
                                $ebayEmailParser = new EbayEmailParser($fullMessage);
                                $ebayEmailParser->getMessage(strip_tags($body));
                                $parsedData = $ebayEmailParser->getEntities();
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

                    if (str_contains($body, '<html>')) {
                        $body = $this->formatHtml($body);
                    }

                    $attachments = [];
                    if ($fullMessage->hasAttachments()) {
                        $attachments = $fullMessage->getAttachments();
                    }

                    $mDate = $fullMessage->getDate()->first();
                    $convertedDate = $mDate instanceof \Carbon\Carbon ? $mDate->copy()->setTimezone(config('app.timezone')) : null;
                    $date = $convertedDate ? $convertedDate->format('Y-m-d H:i:s') : null;

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
                        'date' => $date ?? $filterDateObj->format('Y-m-d H:i:s'),
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
        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Fetch emails failed:'.$storeName.'-'.$store_type, [
                'marketplace' => ucfirst($store_type),
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_type' => $store_type,'store_name' => $storeName]);

            Log::error("Failed to fetch emails: " . $e->getMessage());
            Log::error("Failed to fetch emails: " . $e->getTraceAsString());
            //return ['error' => 'Failed to fetch emails: ' . $e->getMessage()];
            return [];
        }
    }

    private function formatHtml($emailHtml)
    {

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

    private function parseEscalationEmail($emailBody)
    {

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
