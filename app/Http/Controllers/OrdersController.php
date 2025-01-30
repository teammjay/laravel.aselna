<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    private $perPage = 25;

    public function index(Request $request)
    {
        $perPage = $this->perPage;
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('orders.list',compact('perPage','stores'));
    }
}
