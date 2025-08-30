<?php

namespace App\Jobs;

use App\Models\AccountTransaction;
use App\Models\AccountTransactionSynchronization;
use App\Models\ShippingAccount;
use App\Utils\EndicialabelService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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
        /*AccountTransaction::with('shippingAccount')->where('canceled', 0)->whereNull('canceled')
            ->orderBy('transaction_date','desc')
            ->limit(10)
            ->chunk(5, function ($transactions) {
                $this->checkTransactionStatus($transactions);
            });*/

        $shipping_account = ShippingAccount::whereLike('name','%Endicia Label Server%')->first();
        $sync_detail = AccountTransactionSynchronization::orderBy('created_at','desc')->first();

        if($sync_detail){
            $startTime = date('Y-m-d H:i:s',strtotime($sync_detail->filter_end_date));
            if($startTime > date('Y-m-d H:i:s')){
                $startTime = date('Y-m-d H:i:s');
            }
            $endTime = date('Y-m-d H:i:s',strtotime('+25 day', strtotime($startTime)));
            if($endTime > date('Y-m-d H:i:s')){
                $endTime = date('Y-m-d H:i:s');
            }
        }else {
            $endTime   = date('Y-m-d H:i:s', strtotime('-3 months'));
            $startTime = date('Y-m-d H:i:s', strtotime('-25 days', strtotime($endTime)));
        }

        DB::beginTransaction();
        $data = [
            'shipping_account_id' => $shipping_account->id,
            'filter_start_date' => date('Y-m-d H:i:s',strtotime($startTime)),
            'filter_end_date' => date('Y-m-d H:i:s',strtotime($endTime)),
        ];

        AccountTransactionSynchronization::create($data);

        $trans = [
            'accountId' => $shipping_account->account_id,
            'passPhrase' => $shipping_account->pass_phrase,
            'requesterId' => $shipping_account->requester_id,
            'requestId' => (string)Str::uuid(),
            'filter_start_date' => $startTime,
            'filter_end_date' => $endTime,
        ];

        $endiciaClient = new EndicialabelService();
        $response = $endiciaClient->getTransactionLists($trans);

        //Log::info('Transaction Status Response:'.json_enocode($response));

        if(isset($response['status']) && $response['status'] == 0){
            if(isset($response['transactionResult'][0]['Transaction']) && !empty($response['transactionResult'][0]['Transaction'])){
                foreach($response['transactionResult'][0]['Transaction'] as $tres){

                    $trackingNumber = $tres->PIC;
                    $status_event = $tres->Status->StatusEvent;
                    $status = 'Pending';
                    if(!array($status_event)){
                        switch($status_event){
                            case 'N':
                                $status = 'New Item';
                                break;
                            case 'D':
                                $status = 'Delivered';
                                break;
                            case 'O':
                                $status = 'Out for Delivery';
                                break;
                            case 'P':
                                $status = 'Available for Pickup';
                                break;
                            case 'R':
                                $status = 'Returned to Sender';
                                break;
                            case 'U':
                                $status = 'Undeliverable';
                                break;
                            case 'V':
                                $status = 'Delivery Status Not Updated';
                                break;
                            case 'L':
                                $status = 'Notice Left for Recipient';
                                break;
                            case 'F':
                                $status = 'Forwarded to New Address';
                                break;
                            case 'E':
                                $status = 'Dead Letter';
                                break;
                            case 'C':
                                $status = 'Customer Recall';
                                break;
                            case 'I':
                                $status = 'Scanned in Route';
                                break;
                        }
                    }

                    $transaction = AccountTransaction::where('tracking_number',$trackingNumber)->first();

                    if($transaction){
                        $transaction->update(['status' => $status]);
                        Log::info('Update Tracking status:'.$trackingNumber.'- status : '.$status);
                    }
                    Log::info('Tracking:'.$trackingNumber);

                    DB::commit();

                }
            }
        }else{
            DB::rollBack();
        }
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


        }

    }
}
