<?php

namespace App\Jobs;

use App\Models\AccountTransaction;
use App\Models\AccountTransactionSynchronization;
use App\Models\ShippingAccount;
use App\Services\CriticalErrorService;
use App\Utils\EndicialabelService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class TransactionStatusCheckJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000; // Set a timeout for the job

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function handle():void
    {
        $this->updateTrackingRecord(['tags' => 'Endicia Transaction Status Check']);

        AccountTransaction::with('shippingAccount')
            ->where(function ($q) {
                $q->where('canceled', 0)
                    ->orWhereNull('canceled');
            })
            ->where('status','pending')
            ->orderBy('transaction_date','asc')
            ->chunk(100, function ($transactions) {
                $this->checkTransactionStatus($transactions);
            });

    }

    /**
     * Execute the job.
     */
    public function handleOld(): void
    {
        /*AccountTransaction::with('shippingAccount')->where('canceled', 0)->whereNull('canceled')
            ->orderBy('transaction_date','desc')
            ->limit(10)
            ->chunk(5, function ($transactions) {
                $this->checkTransactionStatus($transactions);
            });*/

        $this->updateTrackingRecord(['tags' => 'Endicia Transaction Status Check']);

        try {

            $shipping_account = ShippingAccount::whereLike('name', '%Endicia Label Server%')->first();
            $sync_detail = AccountTransactionSynchronization::orderBy('created_at', 'desc')->first();
            //$last_pending_sync = AccountTransaction::where('status','pending')->orderBy('updated_at','asc')->first();
            //$filter_start_date = $last_pending_sync->updated_at;

            if ($sync_detail) {
                $startTime = date('Y-m-d H:i:s', strtotime($sync_detail->filter_end_date));
                //$startTime = date('Y-m-d H:i:s', strtotime($filter_start_date));
                Log::info('Transaction Status Start Time:' . $startTime);
                if ($startTime > date('Y-m-d H:i:s')) {
                    $startTime = date('Y-m-d H:i:s');
                }
                $endTime = date('Y-m-d H:i:s', strtotime('+20 day', strtotime($startTime)));
                Log::info('Transaction Status End Time:' . $endTime);
                if ($endTime > date('Y-m-d H:i:s')) {
                    $endTime = date('Y-m-d H:i:s');
                }
            } else {
                $endTime = date('Y-m-d H:i:s', strtotime('-3 months'));
                $startTime = date('Y-m-d H:i:s', strtotime('-20 days', strtotime($endTime)));
            }

            if (strtotime($startTime) == strtotime($endTime)) {
                return;
            }

            $data = [
                'shipping_account_id' => $shipping_account->id,
                'filter_start_date' => date('Y-m-d H:i:s', strtotime($startTime)),
                'filter_end_date' => date('Y-m-d H:i:s', strtotime($endTime)),
            ];

            $synchronize = AccountTransactionSynchronization::create($data);
            $sync_id = $synchronize->id;

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

            if (isset($response['status']) && $response['status'] == 0) {
                Log::info('Status:' . $response['status']);
                $transactions = $response['transactionResult'][0]->Transaction;
                if (isset($transactions) && !empty($transactions)) {
                    $key = 0;
                    $last_filter_date = '';
                    foreach ($transactions as $tres) {

                        /*echo '<pre>';
                        print_r($tres);
                        echo '</pre>';die;*/

                        $transactionType = (string)$tres['TransactionType'];
                        $transactionDateTime = (string)$tres['TransactionDateTime'];
                        $transactionDateTime = date('Y-m-d H:i:s', strtotime($transactionDateTime));
                        $postmarkDate = (string)$tres['PostmarkDate'];
                        $refundStatus = (string)$tres['RefundStatus'];

                        if (isset($transactionDateTime) && $transactionDateTime > $last_filter_date) {
                            $last_filter_date = $transactionDateTime;
                        }

                        $trackingNumber = (string)$tres->PIC;
                        $status_event = isset($tres->Status->StatusEvent[0]) ? (string)trim($tres->Status->StatusEvent[0]) : '';
                        $status_desription = isset($tres->Status->StatusDescription[0]) ? (string)trim($tres->Status->StatusDescription[0]) : '';
                        $status = 'Pending';
                        Log::info('Tracking Number:' . $trackingNumber.'- Status:'.$status_event);
                        if ($status_event != '') {
                            Log::info('Status Event:' . $status_event);
                            switch (trim($status_event)) {
                                /*case 'N':
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
                                    break;*/

                                case 'D':
                                    $status = 'completed';
                                    break;
                                default:
                                    $status = 'pending';
                                    break;
                            }
                        }

                        $transactionObj = AccountTransaction::where('tracking_number', $trackingNumber)->first();

                        if ($transactionObj) {
                            $canceled = 0;
                            if (strtolower($refundStatus) == 'refunded') {
                                $canceled = 1;
                            }
                            $updateRecords = [
                                'status' => $status,
                                'status_event' => $status_event,
                                'status_description' => $status_desription
                            ];

                            /*echo '<pre>';
                            print_r($updateRecords);
                            echo '</pre>';die;*/

                            Log::info('Update Tracking status:' . json_encode($updateRecords));
                            $transactionObj->update($updateRecords);
                            Log::info('Update Tracking status:' . $trackingNumber . '- status : ' . $status);

                            if ($last_filter_date != '') {
                                Log::info('Updating transaction last filter date to: ' . $last_filter_date);
                                $last_filter_datef = \Carbon\Carbon::parse($last_filter_date)->setTimezone('America/New_York');
                                $synchronize->update(['filter_end_date' => $last_filter_datef]);
                            }
                        }



                        /*if ($key == 0) {
                            $synchronize->update(['filter_end_date' => date('Y-m-d H:i:s', strtotime($transactionDateTime))]);
                            Log::info('Tracking:' . $trackingNumber);
                        }*/

                        $key++;
                    }

                }
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Error in transaction status check', [
                'marketplace' => 'Transaction Status',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    public function checkTransactionStatus(mixed $transactions)
    {
        try{
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
                $response = $endiciaClient->getStatusRequest($trans);

                if(isset($response['responsestatus']) && $response['responsestatus'] == 0){
                    foreach($trans['transactions'] as $transaction){
                        if(!empty($response['transaction_status'])) {
                            foreach ($response['transaction_status'] as $res_trans) {
                                $transactionObj = AccountTransaction::where('tracking_number', $transaction)->first();

                                if(isset($res_trans['tracking_number']) && $res_trans['tracking_number'] == $transaction){
                                    $status = 'Pending';
                                    $status_event = $res_trans['status'];
                                    $status_description = $res_trans['status_description'];
                                    Log::info('Tracking Number:' . $transaction.'- Status:'.$status_event);
                                    if ($status_event != '') {
                                        switch (trim($status_event)) {
                                            case 'D':
                                                $status = 'completed';
                                                break;
                                            default:
                                                $status = 'pending';
                                                break;
                                        }
                                    }else{
                                        $status = 'completed';
                                    }

                                    if ($transactionObj) {
                                        $updateRecords = [
                                            'status' => $status,
                                            'status_event' => $status_event,
                                            'status_description' => $status_description
                                        ];

                                        Log::info('Update Tracking status:' . json_encode($updateRecords));
                                    }
                                }else{
                                    $updateRecords = [
                                        'status' => 'completed',
                                        'status_event' => '',
                                        'status_description' => 'Status Not Found'
                                    ];
                                }

                                if($transactionObj && $updateRecords) {
                                    $transactionObj->update($updateRecords);
                                }
                            }
                        }
                    }
                }
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Error in transaction status check', [
                'marketplace' => 'Transaction Status',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
