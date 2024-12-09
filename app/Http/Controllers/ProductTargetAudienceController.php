<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductTargetAudienceRequest;
use App\Models\ProductTargetAudience;
use Illuminate\Http\Request;

class ProductTargetAudienceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $product_target_audiences = ProductTargetAudience::all();

        return view('product_target_audience.list', compact('product_target_audiences'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product_target_audience.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductTargetAudienceRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ProductTargetAudience::create($request->all());

        return redirect()->route('product_target_audience.index')
            ->with('success', 'Product type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product_target_audience = ProductTargetAudience::findOrFail($id);
        return view('product_target_audience.add', compact('product_target_audience'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductTargetAudienceRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductTargetAudience::findOrFail($id)->update($request->all());

        return redirect()->route('product_target_audience.index')
            ->with('success', 'Product type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductTargetAudience::findOrFail($id)->delete();

        return redirect()->route('product_target_audience.index')
            ->with('success', 'Product type deleted successfully.');
    }
}
