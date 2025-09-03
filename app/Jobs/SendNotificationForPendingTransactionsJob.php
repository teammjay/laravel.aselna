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

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
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

                $userId = Auth::id();
                $userDetail = User::findOrFail($userId);
                $sendTo = $userDetail->email;
                $sendTo = 'jayesh@mjailab.com';
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

                    Mail::to($sendTo)
                        ->send(new PendingTrackingNumbersMail($tracking_numbers));

                    Log::info('Mail sent successfully', [
                        'recipient' => 'admin@example.com',
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
