<?php

namespace App\Jobs;

use App\Models\StoreMessageAccount;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class SendNotificationEmailSyncFailJob implements ShouldQueue
{
    use Queueable;

    protected $userId;

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
        $thresholdMinutes = 300;

        $storeMessageAccounts = StoreMessageAccount::with('store','store.storeType','incomingAccount')
            ->where('active', 1)
            ->where('api_account', 0)
            ->get();

        foreach ($storeMessageAccounts as $macc) {
            if (isset($macc->incomingAccount->filter_from_date) && $macc->incomingAccount->filter_from_date != '') {

                $lastSync = \Carbon\Carbon::parse($macc->incomingAccount->filter_from_date);
                if ($lastSync->lt(now()->subMinutes($thresholdMinutes))) {
                    Log::info('Filter date: ' . $macc->incomingAccount->filter_from_date);
                    // send notification email
                    $content = "Email Sync issue detected for store: {$macc->store->name} - {$macc->store->storeType->name}. \n Last sync at {$lastSync->toDateTimeString()}";

                    $this->sendMail($macc->store, $content);
                    /*Mail::send($content,
                        function ($message) use ($macc) {
                            $message->to('jayesh@mjailab.com')
                                ->subject("Store Email Sync Issue Alert : {$macc->store->name} - {$macc->store->storeType->name}");
                        }
                    );*/
                }
            }
        }
    }

    private function sendMail($store, $content){
        $userId = $this->userId;
        Log::info('User ID : '.$userId);
        $userDetail = User::findOrFail($userId);
        $sendTo = $userDetail->email;
        $sendTo = 'jayesh@mjailab.com';
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
