<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCarrierRequest;
use Illuminate\Http\Request;
use App\Models\ShippingCarrier;

class ShippingCarrierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_carriers = ShippingCarrier::all();
        return view('shipping_carrier.list', compact('shipping_carriers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('shipping_carrier.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingCarrierRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        ShippingCarrier::create($request->all());

        return redirect()->route('shipping_carrier.index')
            ->with('success', 'Shipping carrier created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_carrier = ShippingCarrier::findOrFail($id);
        return view('shipping_carrier.add', compact('shipping_carrier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingCarrierRequest $request, string $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        ShippingCarrier::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_carrier.index')
            ->with('success', 'Shipping carrier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingCarrier::findOrFail($id)->delete();

        return redirect()->route('shipping_carrier.index')
            ->with('success', 'Shipping carrier deleted successfully.');
    }
}
