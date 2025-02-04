<?php

namespace App\Http\Controllers;

use App\Models\ShippingCarrier;
use App\Models\Store;
use Illuminate\Http\Request;

class ShipmentsController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
        $shippingCarrier = ShippingCarrier::all();
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('shipment.list',compact('perPage','stores','shippingCarrier'));
    }


    public function edit(string $id)
    {
        return view('shipment.detail');
    }
}
