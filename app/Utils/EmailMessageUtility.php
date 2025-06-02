<?php

namespace App\Utils;

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



    public function fetchEmails($folderName = 'INBOX', $limit = 50)
    {
        try {
            $this->client->connect();

            $fromDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime('-10days')));
            $toDate = \Carbon\Carbon::parse(date('Y-m-d'));

            $folder = $this->client->getFolder($folderName);
            $messages = $folder->messages()
                        ->since($fromDate)
                        ->before($toDate)
                        ->limit($limit)->get();

            $emails = [];
            foreach ($messages as $message) {
                /*echo '<pre>';
                print_r($message);
                echo '</pre>';*/
                $body = $message->getTextBody();
                if (empty($message->getTextBody())) {
                    $html = $message->getHtmlBody();
                    $body = strip_tags($html); // fallback to text-like content
                }

                $emails[] = [
                    'subject' => $message->subject->first(),
                    'from' => $message->getFrom()[0]->mail ?? null,
                    'body' => $body,
                    'date' => date('Y-m-d H:i:s',strtotime($message->getDate())),
                ];
            }

            echo '<pre>';
            print_r($emails);
            echo '</pre>';die;

            return $emails;
        } catch (Exception $e) {
            /*echo '<pre>';
            print_r($e->getMessage());
            echo '</pre>';die;*/
            return ['error' => 'Failed to fetch emails: ' . $e->getMessage()];
        }
    }
}
