<?php

namespace App\Jobs;

use App\Models\HDMessage;
use App\Models\StoreMessageAccount;
use App\Models\User;
use App\Models\UserAccount;
use App\Utils\EmailMessageUtility;
use App\Utils\FastEmailService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Jobs\Concerns\TrackableJob;

class SendNotificationEmailSyncFailJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Send Notification Email']);

        Log::info('Starting SendNotificationEmailSyncFailJob Job');
        $storeMessageAccounts = StoreMessageAccount::with('store','store.storeType','incomingAccount')
            ->where('active', 1)
            ->where('api_account', 0)
            ->get();

        foreach ($storeMessageAccounts as $maccount) {
            Log::info('Checking store account ID: ' . $maccount->id);
            try {
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

                        $emailUtility = new FastEmailService($smtpConfig, $imapConfig);

                        $messages = $emailUtility->fetchTodaysEmail($folder);

                        $notFound = [];
                        $currentTime = now(new \DateTimeZone('UTC'));
                        $bufferTimeMinutes = 12;

                        foreach ($messages as $message) {
                            $messageId = trim(trim($message['message_id']), '<>');
                            Log::info('Checking message ID: ' . trim(trim($message['message_id']), '<>'));

                            $messageDate = null;
                            if (!empty($message['date'])) {
                                try {
                                    $messageDate = new \DateTime($message['date']);
                                } catch (\Throwable $e) {

                                    $extraInfo = [
                                        'messageId' => $messageId
                                    ];

                                    \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

                                    Log::warning('Could not parse date for message ID: ' . $messageId);
                                }
                            }

                            // Skip messages received within the buffer time
                            if ($messageDate !== null) {
                                Log::info('Message Date: ' . $messageDate->format('Y-m-d H:i:s'));
                                Log::info('Current Time: ' . $currentTime->format('Y-m-d H:i:s'));
                                $minutesSinceReceived = abs($currentTime->diffInMinutes($messageDate));
                                Log::info("Minutes since received: {$minutesSinceReceived} minutes");
                                if ($minutesSinceReceived <= $bufferTimeMinutes) {
                                    Log::info("Skipping recent message (received {$minutesSinceReceived} minutes ago): {$messageId}");
                                    continue;
                                }
                            }

                            // check if message id exists in hd_messages table
                            if (HDMessage::where('message_id' , $messageId)->doesntExist()) {
                                $notFound[] = $message;
                            };
                        }

                        if (count($notFound) > 0) {
                            Log::info('Not found messages count: ' . count($notFound));
                            $content = "Email Sync issue detected for store: {$maccount->store->name} - {$maccount->store->storeType->name}.";
                            // Attach the list of not found messages
                            if (count($notFound) > 0) {
                                $content .= "\n\nThe following email messages were not synced:\n";
                                foreach ($notFound as $msg) {
                                    $content .= "Subject: {$msg['subject']} | From: <{$msg['from']}> | Date: {$msg['date']} | Message ID: {$msg['message_id']}\n";
                                }
                            }
                            Log::info('Content: '.$content);
                            $this->sendMail($maccount->store, $content);
                        }
                    }
                }
            } catch (\Throwable $e) {

                $extraInfo = [
                    'maccount_id' => $maccount->id
                ];

                \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

                CriticalErrorService::report('Send Notification Email Sync Failed', [
                    'marketplace' => 'N/A',
                    'reason' => $e->getMessage() ?? 'Unknown',
                    'method' => __METHOD__
                ]);

                Log::error('Error processing store account ID ' . $maccount->id . ': ' . $e->getMessage());
            }
        }
    }

    private function sendMail($store, $content){
        $userId = $this->userId;
        Log::info('User ID : '.$userId);
        $userDetail = User::findOrFail($userId);
        $sendTo = $userDetail->send_to_email;
        $cc_emails = '';
        if($userDetail->cc_email != ''){
            $cc_emails = array_filter(array_map('trim', explode(PHP_EOL, $userDetail->cc_email)));
            /*if(is_array($ccEmails)){
                $cc_emails = implode(',', $ccEmails);
            }else{
                $cc_emails = $ccEmails;
            }*/
        }
        $userMessageAccount = UserAccount::with('outgoingAccount')->where('user_id', $userId)->where('active', 1)->first();

        if (isset($userMessageAccount->outgoingAccount)) {
            if ($userMessageAccount->outgoingAccount->requires_ssl) {
                $encryption = 'ssl';
            }
            if ($userMessageAccount->outgoingAccount->requires_tls) {
                $encryption = 'tls';
            }

            Config::set('mail.mailers.custom_smtp', [
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

            Mail::mailer('custom_smtp')->raw($content,
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
