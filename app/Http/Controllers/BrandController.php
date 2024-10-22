<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return view('brand.list');
    }

    public function create()
    {
        return view('brand.add');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'notes1' => 'nullable|string|max:255',
            'notes2' => 'nullable|string|max:255',
            'notes3' => 'nullable|string|max:255',
            'order_no' => 'nullable|integer'
        ]);

        // save the data to the database
        Brand::create($request->all());

        // redirect to the brands list
        return redirect()->route('brand.list');
    }
}
