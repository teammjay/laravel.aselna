<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        // get the data in the pagination format for datatable to display
        $brands = Brand::select('id', 'name', 'notes1', 'notes2', 'notes3', 'order_no')
            ->orderBy('order_no')
            ->get();
        return view('brand.list', compact('brands'));
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
            'order_no' => 'nullable|integer|min:1'
        ]);

        try {
            // save the data to the database
            Brand::create($request->all());
            // redirect to the brands list with success message
            return redirect()->route('brand.list')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the brand. Please try again.']);
        }
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        return view('brand.add', compact('brand'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:30',
            'notes1' => 'nullable|string|max:255',
            'notes2' => 'nullable|string|max:255',
            'notes3' => 'nullable|string|max:255',
            'order_no' => 'nullable|integer'
        ]);

        // save the data to the database
        Brand::findOrFail($id)->update($request->all());

        // redirect to the brands list
        return redirect()->route('brand.list');
    }

    public function destroy($id)
    {
        // delete the brand
        Brand::findOrFail($id)->delete();

        // redirect to the brands list
        return redirect()->route('brand.list');
    }
}
