<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\OrderOperation;
use App\Models\ShippingAccount;
use App\Models\ShippingSettingCarrierAccount;
use App\Models\Store;
use App\Services\DataTable\DataTableService;
use App\Utils\EndicialabelService;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AccountTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $account_id = $id;
        /*$states = AccountTransaction::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();*/

//        $shipping_account_transactions = AccountTransaction::with('shippingAccount')->where('shipping_account_id',$account_id)->get();

        $stores = Store::with('storeType')->where('store_status','<>', 0)->orderBy('name')->get();
        $shipping_account = ShippingAccount::all();
        return view('shipping_account_transaction.list', compact('shipping_account', 'account_id','stores'));
    }

    public function filter(Request $request)
    {
        $totalRecords = AccountTransaction::count();

        $query = AccountTransaction::with('shippingAccount','store','store.storeType','orderOperation.order')->select('*');



        if ($request->has('shipping_account_id') && $request->shipping_account_id != '') {
            $query->where('shipping_account_id', $request->shipping_account_id);
        }

        if ($request->has('tracking_number') && $request->tracking_number != '') {
            $query->where('tracking_number', $request->tracking_number);
        }

        if ($request->has('transaction_date') && $request->transaction_date != '') {
            $query->whereDate('transaction_date', $request->transaction_date);
        }

        if ($request->has('transaction_id') && $request->transaction_id != '') {
            $query->where('transaction_id', $request->transaction_id);
        }

        if ($request->has('cancelled_date') && $request->cancelled_date != '') {
            $query->whereDate('cancelled_date', $request->cancelled_date);
        }

        if ($request->has('address') && $request->address != '') {
            $query->whereLike('address', '%' . $request->address . '%');
        }

        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('order_id') && $request->order_id != '') {

            $query->where(function ($q) use ($request) {
                $q->whereRelation('orderOperation.order', 'order_id', $request->order_id)
                    ->orWhereRelation('orderOperation.order', 'purchase_order_id', $request->order_id);
            });
        }


        $columnIndex = $request->input('order.0.column', 0);
        $sortColumn = $request->input('columns.' . $columnIndex . '.data', 'id');
        $sortDirection = $request->input('order.0.dir', 'asc');
        $query->orderBy($sortColumn, $sortDirection);

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
        $page = ($start / $length) + 1;

        //echo $page;die;
        $totalRecordwithFilter = $query->count();
        $query->paginate($length, ['*'], 'page', $page);

        $transactions = $query->distinct()->get();

//        die("Transactions: " . $transactions->count());

        $data = [];
        foreach ($transactions as $transaction) {
            $order_id = '';
            if(isset($transaction->orderOperation) && isset($transaction->orderOperation->Order)) {
                if (isset($transaction->store->storeType) && strtolower($transaction->store->storeType->name) == 'shopify') {
                    $order_id = $transaction->orderOperation->Order->purchase_order_id;
                }else{
                    $order_id = $transaction->orderOperation->Order->order_id;
                }
            }
            $data[] = [
                'shipping_account' => $transaction->shippingAccount->name.'<input type="checkbox" name="transaction_id[]" style="visibility: hidden;" data-id="'.$transaction->id .'" value="'.$transaction->tracking_number.'">',
                'tracking_number' => $transaction->tracking_number,
                'transaction_date' => $transaction->transaction_date,
                'transaction_id' => $transaction->transaction_id,
                'store' => (isset($transaction->store) && isset($transaction->store->storeType)) ? $transaction->store->name . ' - ' . $transaction->store->storeType->name : '',
                'order_id' => $order_id,
                'amount' => $transaction->amount,
                'address' => $transaction->address,
                'status' => $transaction->status,
                'canceled' => $transaction->canceled,
                'canceled_date' => $transaction->canceled_date,
                'created_at' => date('Y-m-d H:i:s',strtotime($transaction->created_at)),
            ];
        }


        return response()->json([
//            'html' => view('shipping_account_transaction.search_list', compact('transactions'))->render(),
//            'totalRecords' => $transactions->count()
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $data,
        ]);
    }

    public function cancelTransaction(Request $request)
    {
        $transaction_ids = $request->input('transaction_ids');
        $ids = $request->input('ids');
        $account_id = $request->input('account_id');
        if (!empty($transaction_ids)) {
            $carrierAccount = ShippingSettingCarrierAccount::with('shippingAccount', 'shippingAccount.shippingSolution')->where('shipping_account_id', $account_id)->first();
            $shippingAccount = $carrierAccount->shippingAccount;
            $flag = false;
            $successCnt = 0;
            $failCnt = 0;
            $message = 'Transaction voided successfully!';
            foreach($transaction_ids as $key=>$trans){
                $id = $ids[$key];
                $transactionDetail = [
                    'accountId' => $shippingAccount->account_id,
                    'passPhrase' => $shippingAccount->pass_phrase,
                    'requesterId' => $shippingAccount->requester_id,
                    'requestId' => $id,
                    'pic_no' => $trans,
                ];

                $endiciaClient = new EndicialabelService();
                $response = $endiciaClient->cancelLabel($transactionDetail);

                Log::info('Endicia Label Refund Response:'. json_encode($response));

                if (!empty($response)) {

                    if (isset($response['status']) && $response['status'] != '') {
                        $transaction = AccountTransaction::findOrFail($id);
                        switch ($response['status']) {
                            case 'Approved':
                                $transaction->update(['status'=> 'completed','canceled' => 1, 'canceled_date' => date('Y-m-d H:i:s')]);
                                $flag = true;
                                $message = (isset($response['message'])) ? $response['message'] : '';
                                $successCnt++;
                                break;
                            case 'DeniedInvalid':
                                $flag = false;
                                $message = (isset($response['message'])) ? $response['message'] : '';
                                $failCnt++;
                                break;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'failCnt' => $failCnt,
                'successCnt' => $successCnt,
                'message' => $message
            ]);

        }
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong with API. Please try again later.'
        ]);
    }

    public function rePrintlabel(Request $request)
    {
        $transaction_ids = $request->input('transaction_id');

        foreach ($transaction_ids as $transaction_id) {
            $orderOperation = OrderOperation::where('tracking_number', $transaction_id)->first();
            if ($orderOperation) {
                $shippingDocumentCreator = new ShipmentDocumentsCreator($orderOperation);
                $printer = $shippingDocumentCreator->getPrinter();

                $trackingNumber = $orderOperation->tracking_number;
                $imageUrl = asset('storage/labels/' . $trackingNumber . '.txt');

                DB::connection('aselna_printer_db')->table('PrintJobs')->insert([
                    'id' => (string)Str::uuid(),
                    'fileName' => $trackingNumber . '.txt',
                    'filePath' => $imageUrl,
                    'status' => 'pending',
                    'createdAt' => now(),
                    'updatedAt' => now(),
                    'fileSize' => 0,
                    'printerId' => $printer->id ?? null,
                    'systemId' => $printer->internal_name ?? null,
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Label reprint request has been sent successfully!'
        ]);
    }

}
