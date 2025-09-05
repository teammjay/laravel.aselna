<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductColorRequest;
use App\Models\ProductColor;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductColorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_colors = ProductColor::all();
        return view('product_color.list', compact('product_colors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $product_types = ProductType::all();
        return view('product_color.add', compact('product_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductColorRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ProductColor::create($request->all());

        return redirect()->route('product_color.index')
            ->with('success', 'Product color created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product_color = ProductColor::findOrFail($id);
        $product_types = ProductType::all();
        return view('product_color.add', compact('product_color','product_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductColorRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductColor::findOrFail($id)->update($request->all());

        return redirect()->route('product_color.index')
            ->with('success', 'Product color updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductColor::findOrFail($id)->delete();

        return redirect()->route('product_color.index')
            ->with('success', 'Product color deleted successfully.');
    }
}
