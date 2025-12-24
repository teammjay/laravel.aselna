<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CriticalErrorService
{

    protected $userId = 2;

    public static function handle(Throwable $e, $context = null, $extra = [])
    {
        $errorData = [
            'error_id' => uniqid('critical_', true),
            'timestamp' => now()->toDateTimeString(),
            'context' => $context,
            'type' => 'Throwable',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'extra' => $extra,
        ];

        self::process($errorData);
    }

    public static function report($message, $context = null, $extra = [])
    {

        $errorData = [
            'error_id' => uniqid('critical_', true),
            'timestamp' => now()->toDateTimeString(),
            'context' => $context,
            'type' => 'Manual',
            'message' => $message,
            'extra' => $extra,
        ];

        self::process($errorData);
    }

    protected static function process($errorData)
    {
        // Log to file
        Log::channel('critical')->error('Critical Error - '.$errorData['message'], $errorData);

        //  storOptionallye to DB
        // \App\Models\CriticalError::create($errorData);

        /* Notification*/
        self::sendNotificationEmail($errorData);
    }

    private static function sendNotificationEmail($errorData){

        $userId = Auth::id();
        Log::info('User ID : '.$userId);
        $userDetail = User::where('id',$userId)->first();
        if(!$userDetail){
            return false;
        }
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

            Log::info('User Email : '.$sendTo);
            Log::info('Email Content : '.json_encode($errorData['context']));

            /*Mail::mailer('custom_smtp')->row(json_encode($errorData['context']),
                function ($message) use ($errorData, $sendTo, $cc_emails) {
                    $message->to($sendTo)
                        //->cc($cc_emails)
                        ->subject("Critical Alert : {$errorData['message']}");
                }
            );*/

            Mail::mailer('custom_smtp')->send(
                'emails.critical-alert',
                [
                    'marketplace' => $errorData['context']['marketplace'] ?? 'N/A',
                    'jobName'     => $errorData['context']['method'] ?? 'N/A',
                    'status'      => 'Failed',
                    'severity'    => 'Critical',
                    'reason'      => $errorData['context']['reason'] ?? 'Unknown error',
                    'extraInfo'   => $errorData['extra'] ?? [],
                    'timestamp'   => now()
                ],
                function ($message) use ($sendTo, $cc_emails, $errorData) {
                    $message->to($sendTo)
                        ->subject("🚨 Critical Alert: {$errorData['message']}");

                    if (!empty($cc_emails)) {
                        $message->cc($cc_emails);
                    }
                }
            );


            Log::info('Critical Alert Mail Sent Successfully', [
                'recipient' => $sendTo,
                'timestamp' => now()
            ]);
        }
    }

}
