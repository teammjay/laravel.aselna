<?php

namespace App\Http\Controllers;

use App\Models\ProductInterface;
use Illuminate\Http\Request;

class ProductInterfaceController extends Controller
{

    public function index()
    {
        $productInterfaces = ProductInterface::all();
        return view('product_interface.list', compact('productInterfaces'));
    }
}
