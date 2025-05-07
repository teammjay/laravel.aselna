<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingServiceRulesRequest;
use App\Models\ShippingCarrierService;
use App\Models\ShippingPackage;
use App\Models\ShippingServiceRule;
use Illuminate\Http\Request;

class ShippingServiceRulesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_service_rules = ShippingServiceRule::with('packageType','ShippingCarrierService','ShippingCarrierService.ShippingCarrier')->get();
        return view('shipping_service_rule.list', compact('shipping_service_rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shippingCarrierServices = ShippingCarrierService::with('ShippingCarrier')->orderBy('name')->get();
        return view('shipping_service_rule.add',compact('shippingCarrierServices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingServiceRulesRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ShippingServiceRule::create($request->all());

        return redirect()->route('shipping_service_rule.index')
            ->with('success', 'Shipping service rule created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_service_rule = ShippingServiceRule::findOrFail($id);
        $ShippingPackages = ShippingPackage::orderBy('name')->get();
        $shippingCarrierServices = ShippingCarrierService::with('ShippingCarrier')->orderBy('name')->get();

        return view('shipping_service_rule.add', compact('shipping_service_rule','ShippingPackages','shippingCarrierServices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingServiceRulesRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ShippingServiceRule::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_service_rule.index')
            ->with('success', 'Shipping service rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingServiceRule::findOrFail($id)->delete();

        return redirect()->route('shipping_service_rule.index')
            ->with('success', 'Shipping service rule deleted successfully.');
    }
}
