<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    private $perPage = 10;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = Store::select('*')
            ->orderBy('id','desc')
            ->paginate($this->perPage);

        return view('store.list')->with('stores', $stores)->with('perPage', $this->perPage);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!session()->has('store_id')) {
            $record = Store::create(['store_status' => 0]);
            session(['store_id' => $record->id]);
            $store_id = $record->store_id;
        }else{
            $store_id = session('store_id');
        }

        $store = Store::findOrFail($store_id); // Retrieve the store by ID
        //print_r($store);die;
        return view('store.update',compact('store'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Save the records from different tab.
     */
    public function saveTabData(Request $request)
    {
        $storeId = $request->input('store_id');
        $tabId = $request->input('tab');
        $request->except('store_id', 'tab');

        // Logic to handle saving data based on $tabId
        if ($tabId == 1) {

            try {

                $request->validate([
                    'name' => 'required|string|max:30',
                    'marketplace_name' => 'required|string',
                    'store_url' => 'required|string'
                ]);

                $store = Store::findOrFail($storeId); // Retrieve the product by ID
                $store->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }

        } elseif ($tabId == 2) {

            try {
                $store = Store::findOrFail($storeId); // Retrieve the product by ID
                $store->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }elseif ($tabId == 3) {

        }elseif ($tabId == 4) {

        }elseif ($tabId == 5) {

        }elseif ($tabId == 6){

        }elseif ($tabId == 8){

        }elseif ($tabId == 11){

        }
    }


    /**
     * Submit the records from different tab.
     */
    public function finalize(Request $request) {
        $storeId = $request->input('store_id');

        try {
            $request->validate([
                'name' => 'required|string|max:30',
                'store_type' => 'required|string',
                'marketplace_name' => 'required|string',
                'store_url' => 'required|string'
            ]);

            $store = Store::findOrFail($storeId); // Retrieve the product by ID
            $store->update($request->all());

            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }


    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store = Store::findOrFail($id); // Retrieve the store by ID

        return view('store.update', compact('store'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
