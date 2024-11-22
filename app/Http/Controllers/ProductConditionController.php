<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductConditionRequest;
use App\Models\ProductCondition;
use Illuminate\Http\Request;

class ProductConditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_conditions = ProductCondition::all();
        return view('product_condition.list', compact('product_conditions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product_condition.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductConditionRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ProductCondition::create($request->all());

        return redirect()->route('product_condition.index')
            ->with('success', 'Product condition created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product_condition = ProductCondition::findOrFail($id);
        return view('product_condition.add', compact('product_condition'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductConditionRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductCondition::findOrFail($id)->update($request->all());

        return redirect()->route('product_condition.index')
            ->with('success', 'Product condition updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductCondition::findOrFail($id)->delete();

        return redirect()->route('product_condition.index')
            ->with('success', 'Product condition deleted successfully.');
    }
}
