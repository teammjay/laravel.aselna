<?php

namespace App\Jobs;

use App\Models\HDMessage;
use App\Models\StoreMessageAccount;
use App\Models\User;
use App\Models\UserAccount;
use App\Utils\EmailMessageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class SendNotificationEmailSyncFailJob implements ShouldQueue
{
    use Queueable;

    protected $userId;

    protected $timeout = 300000;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id)
    {
        $this->userId = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('Starting SendNotificationEmailSyncFailJob Job');
        $storeMessageAccounts = StoreMessageAccount::with('store','store.storeType','incomingAccount')
            ->where('active', 1)
            ->where('api_account', 0)
            ->get();

        foreach ($storeMessageAccounts as $maccount) {
            Log::info('Checking store account ID: ' . $maccount->id);
            $smtpConfig = $imapConfig = [];
            if ($maccount->incoming_account_id != '') {
                Log::info('Processing store: ' . $maccount->store->name.' - '.$maccount->store->storeType->name);
                if (isset($maccount->incomingAccount)) {
                    Log::info('Processing incoming account: ' . $maccount->incomingAccount->user_id);
                    $account = $maccount->incomingAccount;
                    $folder = $maccount->incomingAccount->folders;
                    $imapConfig = [
                        'host' => $maccount->incomingAccount->server,
                        'port' => $maccount->incomingAccount->port,
                        'encryption' => 'ssl',
                        'username' => $maccount->incomingAccount->user_id,
                        'password' => $maccount->incomingAccount->password,
                    ];

                    Log::info('Imap Config: ' . json_encode($imapConfig));

                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);

                    $messages = $emailUtility->fetchTodaysEmail($folder);

                    $notFound = [];

                    foreach ($messages as $message) {
                        Log::info('Checking message ID: ' . trim($message['message_id']));
                        // check if message id exists in hd_messages table
                        if (HDMessage::where('message_id' , trim($message['message_id']))->doesntExist()) {
                            $notFound[] = $message;
                        };
                    }

                    if (count($notFound) > 0) {
                        Log::info('Not found messages count: ' . count($notFound));
                        $content = "Email Sync issue detected for store: {$maccount->store->name} - {$maccount->store->storeType->name}. \n Last sync at {$maccount->toDateTimeString()}";
                        // Attach the list of not found messages
                        if (count($notFound) > 0) {
                            $content .= "\n\nThe following email messages were not synced:\n";
                            foreach ($notFound as $msg) {
                                $content .= "Subject: {$msg['subject']}";
                            }
                        }
                        Log::info('Content: '.$content);
//                        $this->sendMail($maccount->store, $content);
                    }
                }
            }

        }
    }

    private function sendMail($store, $content){
        $userId = $this->userId;
        Log::info('User ID : '.$userId);
        $userDetail = User::findOrFail($userId);
        $sendTo = $userDetail->email;
        $cc_emails = '';
        if($userDetail->cc_email != ''){
            $ccEmails = explode(PHP_EOL,$userDetail->cc_email);
            if(is_array($ccEmails)){
                $cc_emails = implode(',', $ccEmails);
            }else{
                $cc_emails = $ccEmails;
            }
        }
        $userMessageAccount = UserAccount::with('outgoingAccount')->where('user_id', $userId)->where('active', 1)->first();

        if (isset($userMessageAccount->outgoingAccount)) {
            if ($userMessageAccount->outgoingAccount->requires_ssl) {
                $encryption = 'ssl';
            }
            if ($userMessageAccount->outgoingAccount->requires_tls) {
                $encryption = 'tls';
            }

            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $userMessageAccount->outgoingAccount->server,
                'port' => $userMessageAccount->outgoingAccount->port,
                'encryption' => $encryption,
                'username' => $userMessageAccount->outgoingAccount->username,
                'password' => $userMessageAccount->outgoingAccount->password,
                'timeout' => null,
                'auth_mode' => null,
            ]);

            Config::set('mail.from.address', $userMessageAccount->outgoingAccount->email);
            Config::set('mail.from.name', $userMessageAccount->name);

            Mail::send($content,
                function ($message) use ($store, $sendTo, $cc_emails) {
                    $message->to($sendTo)
                        ->cc($cc_emails)
                        ->subject("Store Email Sync Issue Alert : {$store->name} - {$store->storeType->name}");
                }
            );

            Log::info('Store Order Sync Issue Mail sent successfully', [
                'recipient' => $sendTo,
                'timestamp' => now()
            ]);
        }
    }
}
