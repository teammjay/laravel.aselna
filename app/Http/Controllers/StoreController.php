<?php

namespace App\Http\Controllers;

use App\Models\DeviceTypes;
use App\Models\ShippingCarrierService;
use App\Models\StoreCategory;
use App\Models\StoreDeviceType;
use App\Models\StorePage;
use App\Models\PageType;
use App\Models\StoreShippingService;
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
        $devices = DeviceTypes::where('status','active')->get();
        $pageTypes = PageType::all();
        $shipping_services = ShippingCarrierService::leftJoin('shipping_carrier', 'shipping_carrier_service.shipping_carrier_id', '=', 'shipping_carrier.id')
            ->select('shipping_carrier_service.id', 'shipping_carrier_service.name', 'shipping_carrier.name as carrierName')  // Select the fields you need
            ->get();
        $storeDevices = StoreDeviceType::where('store_id', $store_id)->get();
        $storeShippingService = StoreShippingService::where('store_id', $store_id)->get();
        $storePages = StorePage::with('PageType')->where('store_id', $store_id)->get();
        $storeCategories = StoreCategory::where('store_id', $store_id)->get();
        //print_r($devices);die;
        return view('store.update',compact('store','devices','storeDevices','shipping_services','storeShippingService','pageTypes','storePages','storeCategories'));
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

            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'marketplace_name' => 'required',
                'store_url' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $store = Store::findOrFail($storeId); // Retrieve the store by ID
            $store->update($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);


        } elseif ($tabId == 2 || $tabId == 3) {

            try {
                $store = Store::findOrFail($storeId); // Retrieve the store by ID
                $store->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }elseif ($tabId == 6){

            try {
                $request['authorized'] = $request->has('authorized') ? 1 : 0;

                $store = Store::findOrFail($storeId); // Retrieve the store by ID
                $store->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }

        }elseif ($tabId == 7){

            try {

                $request['alter_image_files'] = $request->has('alter_image_files') ? 1 : 0;
                $request['apply_watermark'] = $request->has('apply_watermark') ? 1 : 0;

                if ($request->hasFile('watermark_image')) {

                    $image = $request->file('watermark_image');
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('images'), $imageName);
                    $request['watermark_image_id'] =$imageName;
                }

                $store = Store::findOrFail($storeId); // Retrieve the store by ID
                $store->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }

        }elseif($tabId == 12){
            try {
                $data = [];
                for($i=0; $i < count($request->devices); $i++){
                    $data[] = array(
                        'store_id' => $storeId,
                        'device_type_id' => $request->devices[$i]
                    );
                }
                StoreDeviceType::where('store_id', $storeId)->delete();
                StoreDeviceType::upsert($data, ['store_id','device_type_id'], ['device_type_id']);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }
    }


    /**
     * Submit the records from different tab.
     */
    public function finalize(Request $request) {
        $storeId = $request->input('store_id');

        try {
            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'marketplace_name' => 'required',
                'store_url' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $store = Store::findOrFail($storeId); // Retrieve the product by ID
            $store->update($request->all());

            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }


    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $store = Store::findOrFail($id); // Retrieve the store by ID
        $devices = DeviceTypes::where('status','active')->get();
        $pageTypes = PageType::all();
        $shipping_services = ShippingCarrierService::leftJoin('shipping_carrier', 'shipping_carrier_service.shipping_carrier_id', '=', 'shipping_carrier.id')
            ->select('shipping_carrier_service.id', 'shipping_carrier_service.name', 'shipping_carrier.name as carrierName')  // Select the fields you need
            ->get();

        $storeDevices = StoreDeviceType::where('store_id', $id)->get();
        $storeDevices = $storeDevices->pluck('device_type_id')->values()->all();
        $storeShippingService = StoreShippingService::where('store_id', $id)->get();
        $storePages = StorePage::with('PageType')->where('store_id', $id)->get();
        $storeCategories = StoreCategory::where('store_id', $id)->get();
        //echo '<pre>';
        //print_r($storePages);die;

        return view('store.update', compact('store','devices','storeDevices','shipping_services','storeShippingService','pageTypes','storePages','storeCategories'));
    }

    public function saveShippingService(Request $request){

        try {

            StoreShippingService::create($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removeShipping(Request $request)
    {
        // Find the service by id
        $shippingId = $request->input('shippingId');
        $service = StoreShippingService::find($shippingId);

        if ($service) {
            $service->delete(); // Delete the shipping

            // Return a success response
            return response()->json(['success' => true, 'message' => 'Service deleted successfully']);
        }

        // If the shipping service doesn't exist, return an error
        return response()->json(['error' => 'Service not found!'], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the store by id
        $store = Store::find($id);

        if ($store) {
            $store->delete(); // Delete the store
            // Return a success response
            return response()->json(['success' => 'Store deleted successfully!']);
        }

        // If the store doesn't exist, return an error
        return response()->json(['error' => 'Store not found!'], 404);
    }

    public function savePages(Request $request){

        try {

            $validator = Validator::make(request()->all(), [
                'title' => 'required|string',
                'page_type_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            StorePage::create($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removePage(Request $request)
    {
        // Find the page by id
        $pageId = $request->input('pageId');
        $page = StorePage::find($pageId);

        if ($page) {
            $page->delete(); // Delete the page

            // Return a success response
            return response()->json(['success' => true, 'message' => 'Page deleted successfully']);
        }

        // If the page doesn't exist, return an error
        return response()->json(['error' => 'Page not found!'], 422);
    }

    public function saveCategory(Request $request){

        try {

            $validator = Validator::make(request()->all(), [
                'name' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            StoreCategory::create($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removeCategory(Request $request)
    {
        // Find the page by id
        $categoryId = $request->input('categoryId');
        $category = StoreCategory::find($categoryId);

        if ($category) {
            $category->delete(); // Delete the category

            // Return a success response
            return response()->json(['success' => true, 'message' => 'Category deleted successfully']);
        }

        // If the page doesn't exist, return an error
        return response()->json(['error' => 'Category not found!'], 422);
    }
}
