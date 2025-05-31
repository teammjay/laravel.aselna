<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchEmailMessagesJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

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
            /* get shopify stores*/
            $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();
            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $store->id)->get();

                    if(count($storeMessageAccount ?? []) > 0){
                        foreach($storeMessageAccount as $maccount){
                            $smtpConfig = $imapConfig = [];
                            /*if($maccount->outgoing_account_id !='') {
                                $smtpConfig = [
                                    'host' => $maccount->incomingAccount->server,
                                    'port' => $maccount->incomingAccount->port,
                                    'encryption' => 'tls',
                                    'username' => $maccount->incomingAccount->user_id,
                                    'password' => $maccount->incomingAccount->password,
                                    'from_address' => $maccount->incomingAccount->email,
                                    'from_name' => $store->name,
                                ];
                            }*/

                            if($maccount->incoming_account_id !='') {
                                if(isset($maccount->incomingAccount)) {
                                    $imapConfig = [
                                        'host' => $maccount->incomingAccount->server,
                                        'port' => $maccount->incomingAccount->port,
                                        'encryption' => 'ssl',
                                        'username' => $maccount->incomingAccount->user_id,
                                        'password' => $maccount->incomingAccount->password,
                                    ];

                                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                                    $receiveEmails = $emailUtility->fetchEmails($maccount->incomingAccount->folders);

                                    if(count($receiveEmails ?? []) > 0){
                                        foreach($receiveEmails as $email){

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }
}
