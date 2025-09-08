<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCarrierServiceRequest;
use App\Models\ShippingCarrierService;
use App\Models\ShippingCarrier;
use Illuminate\Http\Request;

class ShippingCarrierServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_carrier_services = ShippingCarrierService::with('ShippingCarrier')->get();

        //echo '<pre>';
        //print_r($shipping_carrier_services);die;
        return view('shipping_carrier_service.list', compact('shipping_carrier_services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shipping_carriers = ShippingCarrier::all();
        return view('shipping_carrier_service.add',compact('shipping_carriers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingCarrierServiceRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        $request['included_insurance'] = $request->has('included_insurance') ? 1 : 0;

        ShippingCarrierService::create($request->all());

        return redirect()->route('shipping_carrier_service.index')
            ->with('success', 'Shipping carrier service created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_carrier_service = ShippingCarrierService::findOrFail($id);
        $shipping_carriers = ShippingCarrier::all();
        return view('shipping_carrier_service.add', compact('shipping_carrier_service','shipping_carriers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingCarrierServiceRequest $request, string $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        $request['included_insurance'] = $request->has('included_insurance') ? 1 : 0;

        ShippingCarrierService::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_carrier_service.index')
            ->with('success', 'Shipping carrier service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ShippingCarrierService::findOrFail($id)->delete();

        return redirect()->route('shipping_carrier_service.index')
            ->with('success', 'Shipping carrier service deleted successfully.');
    }
}
