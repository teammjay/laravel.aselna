<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\OrderOperation;
use App\Models\ShippingAccount;
use App\Models\ShippingSettingCarrierAccount;
use App\Services\DataTable\DataTableService;
use App\Utils\EndicialabelService;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $account_id = $id;
//        $shipping_account_transactions = AccountTransaction::with('shippingAccount')->where('shipping_account_id',$account_id)->get();
        $shipping_account = ShippingAccount::all();
        return view('shipping_account_transaction.list', compact('shipping_account', 'account_id'));
    }

    public function filter(Request $request)
    {
        $totalRecords = AccountTransaction::count();

        $query = AccountTransaction::with('shippingAccount')->select('*');

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

        $columnIndex = $request->input('order.0.column', 0);
        $sortColumn = $request->input('columns.' . $columnIndex . '.data', 'id');
        $sortDirection = $request->input('order.0.dir', 'asc');
        $query->orderBy($sortColumn, $sortDirection);

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
        $page = ($start / $length) + 1;

        $query->paginate($length, ['*'], 'page', $page);

        $transactions = $query->distinct()->get();

//        die("Transactions: " . $transactions->count());

        $data = [];
        foreach ($transactions as $transaction) {
            $data[] = [
                'shipping_account' => $transaction->shippingAccount->name,
                'tracking_number' => $transaction->tracking_number,
                'transaction_date' => $transaction->transaction_date,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $transaction->amount,
                'address' => $transaction->address,
                'canceled' => $transaction->canceled,
                'canceled_date' => $transaction->canceled_date,
                'created_at' => $transaction->created_at,
            ];
        }


        return response()->json([
//            'html' => view('shipping_account_transaction.search_list', compact('transactions'))->render(),
//            'totalRecords' => $transactions->count()
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    public function cancelTransaction(Request $request)
    {
        $transaction_id = $request->input('transaction_id');
        $id = $request->input('id');
        $account_id = $request->input('account_id');
        if ($transaction_id) {

            $carrierAccount = ShippingSettingCarrierAccount::with('shippingAccount', 'shippingAccount.shippingSolution')->where('shipping_account_id', $account_id)->first();
            $shippingAccount = $carrierAccount->shippingAccount;

            $transactionDetail = [
                'accountId' => $shippingAccount->account_id,
                'passPhrase' => $shippingAccount->pass_phrase,
                'requesterId' => $shippingAccount->requester_id,
                'pic_no' => $transaction_id,
            ];

            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->cancelLabel($transactionDetail);

            if (!empty($response)) {
                $flag = false;
                foreach ($response as $trans) {
                    if (isset($trans['status']) && $trans['status'] != '') {
                        $transaction = AccountTransaction::findOrFail($id);
                        switch ($trans['status']) {
                            case 'Approved':
                                $transaction->update(['canceled' => 1, 'canceled_date' => date('Y-m-d H:i:s')]);
                                $flag = true;
                                break;
                            case 'DeniedInvalid':
                                $flag = false;
                                break;
                        }
                    }
                }

                if ($flag) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Transaction voided successfully!'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction Denied!'
                    ]);
                }

            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong with API. Please try again later.'
            ]);
        }
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
