<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductTypeRequest;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_types = ProductType::with('parent')->get();

        return view('product_type.list', compact('product_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $product_types = ProductType::all();
        return view('product_type.add', compact('product_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductTypeRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ProductType::create($request->all());

        return redirect()->route('product_type.index')
            ->with('success', 'Product type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product_type = ProductType::findOrFail($id);
        $product_types = ProductType::all();
        return view('product_type.add', compact('product_type','product_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductTypeRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductType::findOrFail($id)->update($request->all());

        return redirect()->route('product_type.index')
            ->with('success', 'Product type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductType::findOrFail($id)->delete();

        return redirect()->route('product_type.index')
            ->with('success', 'Product type deleted successfully.');
    }
}
