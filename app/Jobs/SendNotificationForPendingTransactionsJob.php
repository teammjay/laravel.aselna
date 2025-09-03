<?php

namespace App\Jobs;

use App\Mail\PendingTrackingNumbersMail;
use App\Models\AccountTransaction;
use App\Models\UserAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class SendNotificationForPendingTransactionsJob implements ShouldQueue
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
        try {
            $tracking_numbers = AccountTransaction::select('tracking_number')->with('shippingAccount')->where('status', 'pending')
                ->where('transaction_date', '<=', Carbon::now()->subDays(10))
                ->orderBy('transaction_date', 'asc')
                ->pluck('tracking_number');

            if ($tracking_numbers->count() > 0) {

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

                    $mail = Mail::to($sendTo);

                    if (!empty($cc_emails)) {
                        $mail->cc($cc_emails);
                    }
                    $mail->send(new PendingTrackingNumbersMail($tracking_numbers,$userDetail));

                    Log::info('Mail sent successfully', [
                        'recipient' => $sendTo,
                        'tracking_count' => count($tracking_numbers),
                        'timestamp' => now()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Mail sending failed', [
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);
        }
    }
}
