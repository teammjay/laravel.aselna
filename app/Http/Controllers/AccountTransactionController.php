<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\ShippingAccount;
use App\Models\ShippingSettingCarrierAccount;
use App\Utils\EndicialabelService;
use Illuminate\Http\Request;

class AccountTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $account_id = $id;
        $shipping_account_transactions = AccountTransaction::with('shippingAccount')->where('shipping_account_id',$account_id)->get();
        $shipping_account = ShippingAccount::all();
        return view('shipping_account_transaction.list', compact('shipping_account_transactions','shipping_account','account_id'));
    }

    public function filter(Request $request){

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

        $transactions = $query->distinct()->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('shipping_account_transaction.search_list', compact('transactions'))->render(),
                'totalRecords' => $transactions->count()
            ]);
        }
    }

    public function cancelTransaction(Request $request){
        $transaction_id = $request->input('transaction_id');
        $account_id = $request->input('account_id');
        if($transaction_id){

            $carrierAccount = ShippingSettingCarrierAccount::with('shippingAccount','shippingAccount.shippingSolution')->where('shipping_account_id',$account_id)->first();
            $shippingAccount = $carrierAccount->shippingAccount;

            $transactionDetail = [
                'accountId' => $shippingAccount->account_id,
                'passPhrase' => $shippingAccount->pass_phrase,
                'requesterId' => $shippingAccount->requester_id,
                'transaction_id' => $transaction_id,
            ];

            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->cancelLabel($transactionDetail);

            if(!empty($response)){
                $flag = false;
                foreach($response as $trans){
                    if(isset($trans['transactionId']) && $trans['transactionId'] != ''){
                        $transaction = AccountTransaction::where('transaction_id',$trans['transactionId'])->first();
                        if(isset($trans['status'])){
                            switch ($trans['status']){
                                case 'Approved':
                                    $transaction->update(['canceled'=>1,'canceled_date' => date('Y-m-d H:i:s')]);
                                    $flag = true;
                                    break;
                                case 'DeniedInvalid':
                                    $flag = false;
                                    break;
                            }
                        }

                    }
                }

                if($flag){
                    return response()->json([
                        'success' => true,
                        'message' => 'Transaction voided successfully!'
                    ]);
                }else{
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

}
