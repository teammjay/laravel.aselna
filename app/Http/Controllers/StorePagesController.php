<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StorePage;
use Illuminate\Http\Request;

class StorePagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $store_pages = StorePage::with('PageType','store','store.storeType')->get();
        return view('store_pages.list', compact('store_pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        return view('store_pages.add',compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePagesRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StorePage::create($request->all());

        return redirect()->route('store_pages.index')
            ->with('success', 'Store page created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store_pages = StorePage::findOrFail($id);
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        return view('store_pages.add', compact('store_pages','stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePagesRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StorePage::findOrFail($id)->update($request->all());

        return redirect()->route('store_pages.index')
            ->with('success', 'Store page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        StorePage::findOrFail($id)->delete();

        return redirect()->route('store_pages.index')
            ->with('success', 'Store page deleted successfully.');
    }
}
