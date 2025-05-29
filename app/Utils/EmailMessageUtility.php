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

    public function __construct(array $smtpConfig, array $imapConfig = [])
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



    public function fetchEmails($folderName = 'INBOX', $limit = 10)
    {
        try {
            $this->client->connect();

            $folder = $this->client->getFolder($folderName);
            $messages = $folder->messages()->all()->limit($limit)->get();

            $emails = [];
            foreach ($messages as $message) {
                $emails[] = [
                    'subject' => $message->getSubject(),
                    'from' => $message->getFrom()[0]->mail ?? null,
                    'body' => $message->getTextBody(),
                    'date' => $message->getDate()->format('Y-m-d H:i:s'),
                ];
            }

            return $emails;
        } catch (Exception $e) {
            return ['error' => 'Failed to fetch emails: ' . $e->getMessage()];
        }
    }
}
