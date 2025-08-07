<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageTemplatesRequest;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StorePageTemplate;
use Illuminate\Http\Request;

class StorePageTemplatesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $store_page_templates = StorePageTemplate::with('PageType','store','store.storeType')->get();
        return view('store_page_templates.list', compact('store_page_templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $product_types = ProductType::orderBy('sort_order', 'asc')->get();
        return view('store_page_templates.add',compact('stores','product_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageTemplatesRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StorePageTemplate::create($request->all());

        return redirect()->route('store_page_templates.index')
            ->with('success', 'Store page created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store_page_templates = StorePageTemplate::findOrFail($id);
        /*echo '<pre>';
        print_r($store_page_templates);
        echo '</pre>';die;*/
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        return view('store_page_templates.add', compact('store_page_templates','stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePageTemplatesRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StorePageTemplate::findOrFail($id)->update($request->all());

        return redirect()->route('store_page_templates.index')
            ->with('success', 'Store page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        StorePage::findOrFail($id)->delete();

        return redirect()->route('store_page_templates.index')
            ->with('success', 'Store page deleted successfully.');
    }
}
