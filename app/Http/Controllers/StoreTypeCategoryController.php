<?php

namespace App\Http\Controllers;

use App\Models\StoreType;
use App\Models\StoreTypeCategories;
use Illuminate\Http\Request;

class StoreTypeCategoryController extends Controller
{
    public function index()
    {
        $storeCategories = StoreTypeCategories::all();
        return view('store_category.list', compact('storeCategories'));
    }

    public function create()
    {
        $storeType = StoreType::all();
        return view('store_category.create', compact('storeType'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        StoreTypeCategories::create($request->all());
        return redirect()->route('store_category.index')->with('success', 'Store category created successfully.');
    }

    public function edit($id)
    {
        $storeCategory = StoreTypeCategories::find($id);
        return view('store_category.edit', compact('storeCategory'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $storeCategory = StoreTypeCategories::find($id);

        $storeCategory->update($request->all());
        return redirect()->route('store_category.index')->with('success', 'Store category updated successfully.');
    }

    public function destroy($id)
    {
        StoreTypeCategories::find($id)->delete();
        return redirect()->route('store_category.index')->with('success', 'Store category deleted successfully.');
    }
}
