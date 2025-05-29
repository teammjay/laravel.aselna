<?php

namespace App\Jobs;

use App\Models\ShippingAccount;
use App\Models\ShippingAccountPurchase;
use App\Models\ShippingAccountTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ShippingAccountsSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('shipping_account')
            ->orderBy('create_ts', 'desc')
            ->chunk(100, function ($shippingAccounts) {
                foreach ($shippingAccounts as $shippingAccount) {
                    $this->createSyncShippingAccount($shippingAccount);
                }
            });

        DB::connection('easyesale_db')->table('shipping_account_purchase')
            ->orderBy('create_ts', 'desc')
            ->chunk(100, function ($shippingAccountPurchases) {
                foreach ($shippingAccountPurchases as $shippingAccountPurchase) {
                    $this->createSyncShippingAccountPurchase($shippingAccountPurchase);
                }
            });

        DB::connection('easyesale_db')->table('shipping_account_transaction')
            ->orderBy('create_ts', 'desc')
            ->chunk(100, function ($shippingAccountTransactions) {
                foreach ($shippingAccountTransactions as $shippingAccountTransaction) {
                     $this->createSyncShippingAccountTransaction($shippingAccountTransaction);
                }
            });
    }

    private function createSyncShippingAccount(mixed $shippingAccount)
    {
        ShippingAccount::updateOrCreate(
            [
                'id' => $shippingAccount->id
            ],
            [
                'id' => $shippingAccount->id,
                'name' => $shippingAccount->name,
                'user_id' => $shippingAccount->user_id,
                'password' => $shippingAccount->password,
                'api_key' => $shippingAccount->api_key,
                'client' => $shippingAccount->client,
                'shipping_solution_id' => $shippingAccount->shipping_solution_id,
                'account_id' => $shippingAccount->account_id,
                'pass_phrase' => $shippingAccount->pass_phrase,
                'requester_id' => $shippingAccount->requester_id,
                'dazzle_client_id' => $shippingAccount->dazzle_client_id,
                'sandbox_account' => $shippingAccount->sandbox_account,
                'maximum_allowable_balance' => $shippingAccount->maximum_allowable_balance,
                'auto_recharge' => $shippingAccount->auto_recharge,
                'auto_recharge_threshold' => $shippingAccount->auto_recharge_threshold
            ]
        );
    }

    private function createSyncShippingAccountPurchase(mixed $shippingAccountPurchase)
    {
        ShippingAccountPurchase::updateOrCreate(
            [
                'id' => $shippingAccountPurchase->id
            ],
            [
                'id' => $shippingAccountPurchase->id,
                'shipping_account_id' => $shippingAccountPurchase->shipping_account_id,
                'purchase_date' => $shippingAccountPurchase->purchase_date,
                'amount' => $shippingAccountPurchase->amount,
            ]
        );
    }

    private function createSyncShippingAccountTransaction(mixed $shippingAccountTransaction)
    {
        ShippingAccountTransaction::updateOrCreate(
            [
                'id' => $shippingAccountTransaction->id
            ],
            [
                'id' => $shippingAccountTransaction->id,
                'shipping_account_id' => $shippingAccountTransaction->shipping_account_id,
                'transaction_date' => $shippingAccountTransaction->transaction_date,
                'amount' => $shippingAccountTransaction->amount,
                'currency' => $shippingAccountTransaction->currency,
                'type' => $shippingAccountTransaction->type,
                'description' => $shippingAccountTransaction->description,
                'client' => $shippingAccountTransaction->client,
            ]
        );
    }
}
