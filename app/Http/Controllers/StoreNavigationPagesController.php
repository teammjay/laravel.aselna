<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreNavigationPage;
use Illuminate\Http\Request;

class StoreNavigationPagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $navigation_pages = StoreNavigationPage::with('store','store.storeType')->get();
        return view('store_navigation_pages.list', compact('navigation_pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        return view('store_navigation_pages.add', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'store_id' => 'required',
            'navigation_type' => 'required',
            'content' => 'required'
        ]);

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StoreNavigationPage::create($request->all());

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $navigation_page = StoreNavigationPage::findOrFail($id);
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        return view('store_navigation_pages.add', compact('navigation_page', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required',
            'store_id' => 'required',
            'navigation_type' => 'required',
            'content' => 'required'
        ]);

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StoreNavigationPage::findOrFail($id)->update($request->all());

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        StoreNavigationPage::findOrFail($id)->delete();

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page deleted successfully.');
    }
}
