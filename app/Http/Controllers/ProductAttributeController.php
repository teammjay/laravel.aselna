<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductAttributeRequest;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_attributes = ProductAttribute::all();
        return view('product_attribute.list', compact('product_attributes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product_attribute.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductAttributeRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by]);

        ProductAttribute::create($request->all());

        return redirect()->route('product_attribute.index')
            ->with('success', 'Product attributes created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product_attribute = ProductAttribute::findOrFail($id);
        return view('product_attribute.add', compact('product_attribute'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductAttributeRequest $request, string $id)
    {
        $request->validated();
        ProductAttribute::findOrFail($id)->update($request->all());

        return redirect()->route('product_attribute.index')
            ->with('success', 'Product attributes updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductAttribute::findOrFail($id)->delete();

        return redirect()->route('product_attribute.index')
            ->with('success', 'Product attributes deleted successfully.');
    }
}
