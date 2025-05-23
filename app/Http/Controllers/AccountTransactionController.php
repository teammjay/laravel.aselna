<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\ShippingAccount;
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
        return view('shipping_account_transaction.list', compact('shipping_account_transactions','shipping_account'));
        return view('shipping_account_transaction.list', compact('shipping_account_transactions','shipping_account'));
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

}
