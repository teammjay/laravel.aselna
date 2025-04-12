<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use App\Models\StoreTypeCategories;
use App\Http\Requests\StoreTypeCategoryRequest;
//use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use Illuminate\Http\Request;

class StoreTypeCategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $store_type_categories = StoreTypeCategory::with('parent')->with('storetype')->get();
        return view('store_type_category.list', compact('store_type_categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $store_types = StoreType::all();
        $store_type_categories = StoreTypeCategory::all();
        return view('store_type_category.add',compact('store_types','store_type_categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTypeCategoryRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StoreTypeCategory::create($request->all());

        return redirect()->route('store_type_category.index')
            ->with('success', 'Store type category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store_type_category = StoreTypeCategory::findOrFail($id);
        $store_types = StoreType::all();
        $store_type_categories = StoreTypeCategory::all();
        return view('store_type_category.add', compact('store_type_category','store_types','store_type_categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTypeCategoryRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StoreTypeCategory::findOrFail($id)->update($request->all());

        return redirect()->route('store_type_category.index')
            ->with('success', 'Store type category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        StoreTypeCategory::findOrFail($id)->delete();

        return redirect()->route('store_type_category.index')
            ->with('success', 'Store type category deleted successfully.');
    }
}
