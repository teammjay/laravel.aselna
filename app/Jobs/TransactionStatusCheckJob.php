<?php

namespace App\Jobs;

use App\Models\AccountTransaction;
use App\Utils\EndicialabelService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionStatusCheckJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 30000; // Set a timeout for the job

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
        AccountTransaction::with('shippingAccount')->where('canceled', 0)->whereNull('canceled')
            ->orderBy('transaction_date','desc')
            ->limit(10)
            ->chunk(5, function ($transactions) {
                $this->checkTransactionStatus($transactions);
            });
    }

    public function checkTransactionStatus(mixed $transactions)
    {

        $trans = [];
        if($transactions){
            foreach($transactions as $key=>$transaction){
                if($key == 0) {
                    $trans = [
                        'accountId' => $transaction->shippingAccount->account_id,
                        'passPhrase' => $transaction->shippingAccount->pass_phrase,
                        'requesterId' => $transaction->shippingAccount->requester_id,
                        'requestId' => (string)Str::uuid()
                    ];
                }

                $trans['transactions'][] = $transaction->tracking_number;
            }

            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->getTransactionLists($trans);

            Log::info('Transaction Status Response:'.json_enocode($response));

            if(isset($response['status']) && $response['status'] == 0){
                if(isset($response['transactionResult']) && !empty($response['transactionResult'])){
                    foreach($response['transactionResult'] as $tres){
                        $trans = $tres['transaction'];
                        $status = $trans['Status'];
                    }
                }
            }
        }

    }
}
