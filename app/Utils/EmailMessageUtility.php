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

    public function sendTestEmail(string $toEmail)
    {
        try {
            Mail::raw('This is a test email from SMTP connector.', function ($message) use ($toEmail) {
                $message->to($toEmail)
                    ->subject('SMTP Test Email');
            });

            return "Email sent successfully to {$toEmail}.";
        } catch (Exception $e) {
            return "Failed to send email: " . $e->getMessage();
        }
    }



    public function fetchEmails($store_type, $folderName = 'INBOX', $limit = 100)
    {
        try {
            $this->client->connect();

            $fromDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime('-10 days')));
            $toDate = \Carbon\Carbon::parse(date('Y-m-d'));

            $folder = $this->client->getFolder($folderName);
            $messages = $folder->messages()
                        ->since($fromDate)
                        ->before($toDate)
                        ->limit($limit)
                        ->get()
                        ->sortByDesc(function($msg) {
                            return optional($msg->getDate()->get())->getTimestamp(); // safe
                        })
                        ->values(); // reindex after sorting

            /*echo '<pre>';
            print_r($messages);
            echo '</pre>';die;*/
            $emails = [];
            foreach ($messages as $key=>$message) {
                //if($key == 0) continue;
               /* echo '<pre>';
                print_r($message);
                echo '</pre>';*/

                $body = $message->getTextBody();
                if (empty($message->getTextBody())) {
                    $html = $message->getHtmlBody();
                    $body = $html; // fallback to text-like content
                }

                $parsedData = [];
                if($store_type != ''){
                    switch (strtolower($store_type)){
                        case 'walmart':
                            $walmartEmailParser = new WalmartEmailParser($message);
                            $walmartEmailParser->getMessage(strip_tags($body));
                            $parsedData = $walmartEmailParser->getEntities();
                            /*echo '<pre>';
                            print_r($parsedData);
                            echo '</pre>';*/
                            break;
                        case 'amazon':
                            $amazonEmailParser = new AmazonEmailParser($message);
                            $amazonEmailParser->getMessage(strip_tags($body));
                            $parsedData = $amazonEmailParser->getEntities();
                            /*echo '<pre>';
                            print_r($parsedData);
                            echo '</pre>';*/
                            break;
                        case 'shopify':
                            $shopifyEmailParser = new ShopifyEmailParser($message);
                            $shopifyEmailParser->getMessage(strip_tags($body));
                            $parsedData = $shopifyEmailParser->getEntities();
                            /*echo '<pre>';
                            print_r($parsedData);
                            echo '</pre>';*/
                            break;
                    }
                }

                //$parsedData = $this->parseEscalationEmail($body);

                $part = $message->getStructure()->parts[0];

                $parts = [
                    'mime_type' => $part->content_type,
                    'content' => $part->content,
                    'charset' => $part->charset,
                ];
                $emails[] = [
                    'subject' => $message->subject->first(),
                    'full_name' => $message->fromaddress->first(),
                    'name' => $message->getFrom()[0]->personal ?? null,
                    'from' => $message->getFrom()[0]->mail ?? null,
                    'reply_to' => $message->getReplyTo()[0]->mail ?? null,
                    'reply_to_name' => $message->getReplyTo()[0]->full ?? null,
                    'to' => $message->getTo()[0]->mail ?? null,
                    'cc' => $message->getCc()[0]->mail ?? null,
                    'bcc' => $message->getBcc()[0]->mail ?? null,
                    'message_id' => $message->message_id->first(),
                    'date' => date('Y-m-d H:i:s',strtotime($message->getDate())),
                    'parsedData' => $parsedData,
                    'parts' => $parts,
                    'body' => $body,
                ];

            }

            /*echo '<pre>';
            print_r($emails);
            echo '</pre>';die;*/
            return $emails;
        } catch (Exception $e) {
            /*echo '<pre>';
            print_r($e->getMessage());
            echo '</pre>';die;*/
            return ['error' => 'Failed to fetch emails: ' . $e->getMessage()];
        }
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
