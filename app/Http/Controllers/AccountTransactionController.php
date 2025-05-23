<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
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
        return view('shipping_account_transaction.list', compact('shipping_account_transactions'));
    }

}
