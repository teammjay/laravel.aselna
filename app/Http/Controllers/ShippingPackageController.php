<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingPackageRequest;
use App\Models\ShippingCarrierPackage;
use App\Models\ShippingPackage;
use App\Models\ShippingPackageShippingCarrierPackageLink;
use Illuminate\Http\Request;

class ShippingPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_packages = ShippingPackage::all();
        return view('shipping_package.list', compact('shipping_packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shippingCarrierPackages = ShippingCarrierPackage::with('shippingCarrier')->orderBy('name')->get();
        return view('shipping_package.add',compact('shippingCarrierPackages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingPackageRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ShippingPackage::create($request->all());

        return redirect()->route('shipping_package.index')
            ->with('success', 'Shipping package created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_package = ShippingPackage::findOrFail($id);
        $shippingCarrierPackages = ShippingCarrierPackage::with('shippingCarrier')->orderBy('name')->get();
        /*echo '<pre>';
        print_r($shippingCarrierPackages);
        echo '</pre>';die;*/
        $carrierPackages = ShippingPackageShippingCarrierPackageLink::with('ShippingCarrierPackage','ShippingCarrierPackage.shippingCarrier')->where('shipping_package_id',$id)->get();
        return view('shipping_package.add', compact('shipping_package','shippingCarrierPackages','carrierPackages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingPackageRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ShippingPackage::findOrFail($id)->update($request->except(['carrier_package']));

        $data = [];
        for($i=0; $i < count($request->carrier_package ?? []); $i++){
            $data[] = array(
                'shipping_package_id' => $id,
                'shipping_carrier_package_id' => $request->carrier_package[$i]
            );
        }
        ShippingPackageShippingCarrierPackageLink::where('shipping_package_id', $id)->delete();
        ShippingPackageShippingCarrierPackageLink::insert($data);

        return redirect()->route('shipping_package.index')
            ->with('success', 'Shipping package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ShippingPackage::findOrFail($id)->delete();

        return redirect()->route('shipping_package.index')
            ->with('success', 'Shipping package deleted successfully.');
    }
}
