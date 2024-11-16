<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTypesRequest;

class StoreTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $store_types = StoreType::all();
        return view('store_type.list', compact('store_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('store_type.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTypesRequest $request)
    {
        $request->validated();

        $request['acknowledge_order'] = $request->has('acknowledge_order') ? 1 : 0;
        $request['create_image_per_listing'] = $request->has('create_image_per_listing') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StoreType::create($request->all());

        return redirect()->route('store_type.index')
            ->with('success', 'Store type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store_types = StoreType::findOrFail($id);
        return view('store_type.add', compact('store_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTypesRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StoreType::findOrFail($id)->update($request->all());

        return redirect()->route('store_type.index')
            ->with('success', 'Store type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        StoreType::findOrFail($id)->delete();

        return redirect()->route('store_type.index')
            ->with('success', 'Store type deleted successfully.');
    }
}
