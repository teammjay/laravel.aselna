<?php

namespace App\Http\Controllers;

use App\Models\ShippingAccount;
use App\Models\ShippingAccountPurchase;
use Illuminate\Http\Request;

class ShippingAccountPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $account_id = $id;
        $shipping_account = ShippingAccount::all();
        return view('shipping_account_purchase.list', compact('shipping_account', 'account_id'));
    }

    public function filter(Request $request)
    {
        $totalRecords = ShippingAccountPurchase::count();

        $query = ShippingAccountPurchase::with('shippingAccount')->select('*');

        if ($request->has('shipping_account') && $request->shipping_account != '') {
            $query->where('shipping_account_id', $request->shipping_account);
        }

        if ($request->has('purchase_date') && $request->purchase_date != '') {
            $query->whereDate('purchase_date', $request->purchase_date);
        }

        if ($request->has('amount') && $request->amount != '') {
            $query->where('amount', $request->amount);
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

        //echo $page;die;
        $totalRecordwithFilter = $query->count();
        $query->paginate($length, ['*'], 'page', $page);

        $purchases = $query->distinct()->get();

//        die("Transactions: " . $transactions->count());

        $data = [];
        foreach ($purchases as $purchase) {
            $data[] = [
                'shipping_account' => $purchase->shippingAccount->name,
                'amount' => $purchase->amount,
                'errors' => $purchase->errors,
                'purchase_date' => $purchase->purchase_date,
                'created_at' => date('Y-m-d H:i:s',strtotime($purchase->created_at)),
            ];
        }


        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $data,
        ]);
    }
}
