<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCarrierPackageRequest;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierPackage;
use Illuminate\Http\Request;

class ShippingCarrierPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_carrier_packages = ShippingCarrierPackage::all();
        return view('shipping_carrier_package.list', compact('shipping_carrier_packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shippingCarrier = ShippingCarrier::orderBy('name')->get();
        return view('shipping_carrier_package.add',compact('shippingCarrier'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingCarrierPackageRequest $request)
    {
        $request->validated();
        $request['non_machinable'] = $request->has('non_machinable') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ShippingCarrierPackage::create($request->all());

        return redirect()->route('shipping_carrier_package.index')
            ->with('success', 'Shipping carrier package created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_carrier_package = ShippingCarrierPackage::findOrFail($id);
        $shippingCarrier = ShippingCarrier::orderBy('name')->get();
        return view('shipping_carrier_package.add', compact('shipping_carrier_package','shippingCarrier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingCarrierPackageRequest $request, string $id)
    {
        $request->validated();

        $request['non_machinable'] = $request->has('non_machinable') ? 1 : 0;

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ShippingCarrierPackage::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_carrier_package.index')
            ->with('success', 'Shipping carrier package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingCarrierPackage::findOrFail($id)->delete();

        return redirect()->route('shipping_carrier_package.index')
            ->with('success', 'Shipping carrier package deleted successfully.');
    }
}
