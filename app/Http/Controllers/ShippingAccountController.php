<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingAccountRequest;
use App\Models\ShippingAccount;
use Illuminate\Http\Request;

class ShippingAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_accounts = ShippingAccount::all();
        return view('shipping_account.list', compact('shipping_accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('shipping_account.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingAccountRequest $request)
    {
        $request->validated();

        ShippingAccount::create($request->all());

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_account = ShippingAccount::findOrFail($id);
        return view('shipping_account.add', compact('shipping_account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingAccountRequest $request, string $id)
    {
        $request->validated();

        $request['shipment_estimate_available'] = $request->has('shipment_estimate_available') ? 1 : 0;

        ShippingAccount::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingAccount::findOrFail($id)->delete();

        return redirect()->route('shipping_account.index')
            ->with('success', 'Shipping account deleted successfully.');
    }
}
