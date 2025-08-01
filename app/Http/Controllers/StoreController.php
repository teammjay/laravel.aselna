<?php

namespace App\Http\Controllers;

use App\Jobs\FileSystemSyncJob;
use App\Jobs\MarketplaceCategorySyncJob;
use App\Jobs\StoreCategorySyncJob;
use App\Models\DeviceTypes;
use App\Models\FileSystem;
use App\Models\ListingTemplate;
use App\Models\LogisticCenter;
use App\Models\Manufacturer;
use App\Models\MessagingAccount;
use App\Models\ShippingCarrierService;
use App\Models\StoreCategory;
use App\Models\StoreDeviceType;
use App\Models\StoreLogisticCenter;
use App\Models\StoreManufactureLink;
use App\Models\StorePage;
use App\Models\PageType;
use App\Models\StoreMessageAccount;
use App\Models\StorePropertyInfo;
use App\Models\StorePropertyValues;
use App\Models\StoreShippingService;
use App\Models\StoreType;
use App\Services\Auth\EbayAuthService;
use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function Ramsey\Uuid\Generator\timestamp;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    private $perPage = 10;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = Store::select('*')
            ->with('storeType')
            ->orderBy('id','desc')
            ->get();
            //->paginate($this->perPage);
        $perPage = $this->perPage;
        $store_types = StoreType::all();

        return view('store.list', compact('stores','perPage','store_types'));
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
        $action = 'Add';
        $store = Store::findOrFail($store_id); // Retrieve the store by ID
        $devices = DeviceTypes::where('status','active')->get();
        $pageTypes = PageType::all();
        $logisticCenters = LogisticCenter::all();
        $store_types = StoreType::all();
        $listing_templates = ListingTemplate::all();
        $shipping_services = ShippingCarrierService::leftJoin('shipping_carrier', 'shipping_carrier_service.shipping_carrier_id', '=', 'shipping_carrier.id')->select('shipping_carrier_service.id', 'shipping_carrier_service.name', 'shipping_carrier.name as carrierName')->get();
        $storeDevices = StoreDeviceType::where('store_id', $store_id)->get();
        $storeShippingService = StoreShippingService::where('store_id', $store_id)->get();
        $storePages = StorePage::with('PageType')->where('store_id', $store_id)->get();
        $storeCategories = StoreCategory::where('store_id', $store_id)->get();
        $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $store_id)->get();
        $storeLogisticCenters = StoreLogisticCenter::with('LogisticCenter')->where('store_id', $store_id)->get();
        $fileSystems = FileSystem::all();
        $propertyInfo = StorePropertyInfo::all();
        $manufacturers = Manufacturer::orderBy('name','asc')->get();
        $storeManufacturers = [];
        /*$messagerecord = $storeMessageAccount->toArray();
        $incomingAccount = MessagingAccount::where('id', $messagerecord[0]['incoming_account_id'])->get();
        $outgoingAccount = MessagingAccount::where('id', $messagerecord[0]['outgoing_account_id'])->get();*/
        //echo '<pre>';
        //print_r($storeDevices);die;

        return view('store.update', compact('store','devices','storeDevices','shipping_services','storeShippingService','pageTypes','storePages','storeCategories','storeMessageAccount','logisticCenters','storeLogisticCenters','store_types','action','listing_templates','fileSystems','propertyInfo','manufacturers','storeManufacturers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);
        $request[$field] = $value;

        try {
            $store = Store::findOrFail($id); // Retrieve the store by ID
            $store->update($request->all());
            return response()->json(['success' => true, 'message' => 'Data updated successfully!']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Data updated successfully!']);
    }

    /**
     * Filter listing.
     */
    public function filter(Request $request)
    {
        $query = Store::query();

        $perPage = $request->input('perPage', $this->perPage);

        // Filter by store name
        if ($request->has('name') && $request->name != '') {
            $query->WhereLike('name', '%'.$request->name.'%');
        }

        // Filter by marketplace name
        if ($request->has('marketplace_name') && $request->marketplace_name != '') {
            $query->WhereLike('marketplace_name', '%'.$request->marketplace_name.'%');
        }

        // Filter by store status
        if ($request->has('store_status') && $request->store_status != '') {
            $query->where('store_status', $request->store_status);
        }

        // Filter by store authorized
        if ($request->has('authorized') && $request->authorized != '') {
            $query->where('authorized', $request->authorized);
        }

        // Filter by store authorized
        if ($request->has('ad_account') && $request->ad_account != '') {
            $query->where('ad_account', $request->ad_account);
        }

        // Filter by store type
        if ($request->has('store_type') && $request->store_type != '') {
            $query->where('store_type_id', $request->store_type);
        }

        $query->orderBy('id','desc');

        //$stores = $query->paginate($perPage); // Paginate results
        $stores = $query->get(); // Paginate results

        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('store.store_list', compact('stores'))->render(),
                //'pagination' => view('store.pagination', compact('stores'))->render(),
            ]);
        }

        return view('store.index', compact('stores'));
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

            $request['wholesale'] = $request->has('wholesale') ? 1 : 0;
            $request['auto_generate_listings'] = $request->input('auto_generate_listings','off') === 'on';
            Log::info('Request Listing: ' . json_encode($request['auto_generate_listing']));
            $request['test_store'] = $request->has('test_store') ? 1 : 0;
            $request['ad_account'] = $request->has('ad_account') ? 1 : 0;
            $request['download_listings'] = $request->has('download_listings') ? 1 : 0;
            $request['download_buy_box'] = $request->has('download_buy_box') ? 1 : 0;
            $request['download_reviews'] = $request->has('download_reviews') ? 1 : 0;
            $request['generate_device_details'] = $request->has('generate_device_details') ? 1 : 0;

            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'marketplace_name' => 'required',
                //'store_url' => 'required'
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
        }elseif($tabId == 13){
            $data = [];
            for($i=0; $i < count($request->store_property_info_id); $i++){
                $data[] = array(
                    'id' => (string) Str::uuid(),
                    'store_id' => $storeId,
                    'store_property_info_id' => $request->store_property_info_id[$i],
                    'value' => $request->value[$i],
                );
            }
            StorePropertyValues::where('store_id', $storeId)->delete();
            StorePropertyValues::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        }elseif($tabId == 14){
            $data = [];
            for($i=0; $i < count($request->manufacturer); $i++){
                $data[] = array(
                    'store_id' => $storeId,
                    'manufacturer_id' => $request->manufacturer[$i]
                );
            }
            StoreManufactureLink::where('store_id', $storeId)->delete();
            StoreManufactureLink::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
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
                //'store_url' => 'required'
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
        $action = 'Edit';
        $store = Store::findOrFail($id); // Retrieve the store by ID
        $devices = DeviceTypes::where('status','active')->get();
        $pageTypes = PageType::all();
        $logisticCenters = LogisticCenter::all();
        $store_types = StoreType::all();
        $listing_templates = ListingTemplate::all();
        $shipping_services = ShippingCarrierService::leftJoin('shipping_carrier', 'shipping_carrier_service.shipping_carrier_id', '=', 'shipping_carrier.id')->select('shipping_carrier_service.id', 'shipping_carrier_service.name', 'shipping_carrier.name as carrierName')->get();

        $storeDevices = StoreDeviceType::where('store_id', $id)->get();
        $storeDevices = $storeDevices->pluck('device_type_id')->values()->all();
        $storeShippingService = StoreShippingService::where('store_id', $id)->get();
        $storePages = StorePage::with('PageType')->where('store_id', $id)->get();
        $storeCategories = StoreCategory::where('store_id', $id)->get();
        $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $id)->get();
        $storeLogisticCenters = StoreLogisticCenter::with('LogisticCenter')->where('store_id', $id)->get();
        $fileSystems = FileSystem::all();
        $propertyInfo = StorePropertyInfo::all();
        $propertyValues = StorePropertyValues::with('propertyInfo')->where('store_id',$id)->get();
        $manufacturers = Manufacturer::orderBy('name','asc')->get();
        $storeManufacturers = StoreManufactureLink::where('store_id',$id)->get();
        $storeManufacturers = $storeManufacturers->pluck('manufacturer_id')->values()->all();
        //$storemanufacturers = Manufacturer::all();
        /*$messagerecord = $storeMessageAccount->toArray();
        $incomingAccount = MessagingAccount::where('id', $messagerecord[0]['incoming_account_id'])->get();
        $outgoingAccount = MessagingAccount::where('id', $messagerecord[0]['outgoing_account_id'])->get();*/
        //echo '<pre>';
        //print_r($outgoingAccount);die;

        return view('store.update', compact('store','devices','storeDevices','shipping_services','storeShippingService','pageTypes','storePages','storeCategories','storeMessageAccount','logisticCenters','storeLogisticCenters','store_types','action','listing_templates','fileSystems','propertyInfo','propertyValues','manufacturers','storeManufacturers'));
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
//            $service->delete(); // Delete the shipping

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
        $store = Store::findOrFail($id);

        if ($store) {
//            $store->delete(); // Delete the store
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
                'page_type' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            StorePage::updateOrCreate([
                'id' => $request->input('page_id'),
            ], $request->all());

            //StorePage::create($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    public function getStorePage(Request $request){

        $pageId = $request->input('pageId');
        $record = StorePage::find($pageId);
        return response()->json(['success' => true, 'data' => $record]);exit;
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

    public function saveMessageAccount(Request $request){

        try {

            $validator = Validator::make(request()->all(), [
                'name' => 'required',
                'email' => 'required',
                'acc_out_protocol' => 'required',
                'acc_out_email' => 'required',
                'acc_out_user_id' => 'required',
                'acc_out_password' => 'required',
                'acc_out_server' => 'required',
                'acc_out_port' => 'required',
                'acc_in_protocol' => 'required',
                'acc_in_email' => 'required',
                'acc_in_user_id' => 'required',
                'acc_in_password' => 'required',
                'acc_in_server' => 'required',
                'acc_in_port' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            /* save outgoing account*/
            $outgoingRecords = [
                'protocol' => $request->input('acc_out_protocol'),
                'email' => $request->input('acc_out_email'),
                'user_id' => $request->input('acc_out_user_id'),
                'password' => $request->input('acc_out_password'),
                'server' => $request->input('acc_out_server'),
                'port' => $request->input('acc_out_port'),
                'requires_ssl' => $request->has('acc_out_requires_ssl') ? 1 : 0,
                'requires_tls' => $request->has('acc_out_requires_tls') ? 1 : 0,
                'requires_authentication' => $request->has('acc_out_requires_ssl') ? 1 : 0,
                'ssl_port' => $request->input('acc_out_ssl_port'),
                'tls_port' => $request->input('acc_out_tls_port'),
                'filter_from_date' => date('Y-m-d H:i:s'),
                'created_by' => auth()->user()->name ?? 'admin',
            ];

            $messasgeOutAcc = MessagingAccount::updateOrCreate(
                ['id' => $request->input('acc_out_id')],
                $outgoingRecords
            );

            $outgoingAccountid = $messasgeOutAcc->id;


            /* save incoming account*/
            $incomingRecords = [
                'protocol' => $request->input('acc_in_protocol'),
                'email' => $request->input('acc_in_email'),
                'user_id' => $request->input('acc_in_user_id'),
                'password' => $request->input('acc_in_password'),
                'server' => $request->input('acc_in_server'),
                'port' => $request->input('acc_in_port'),
                'requires_ssl' => $request->has('acc_in_requires_ssl') ? 1 : 0,
                'requires_tls' => $request->has('acc_in_requires_tls') ? 1 : 0,
                'requires_authentication' => $request->has('acc_in_requires_ssl') ? 1 : 0,
                'ssl_port' => $request->input('acc_in_ssl_port'),
                'tls_port' => $request->input('acc_in_tls_port'),
                'folders' => $request->input('acc_in_folders'),
                'filter_from_date' => date('Y-m-d H:i:s'),
                'created_by' => auth()->user()->name ?? 'admin',
            ];

            $messasgeInAcc = MessagingAccount::updateOrCreate(
                ['id' => $request->input('acc_in_id')],
                $incomingRecords
            );

            $incomingAccountid = $messasgeInAcc->id;

            if($outgoingAccountid && $incomingAccountid) {
                $storeAccount = [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'client_id' => (string) Str::uuid(),
                    'store_id' => $request->input('store_id'),
                    'incoming_account_id' => $incomingAccountid,
                    'outgoing_account_id' => $outgoingAccountid,
                    'api_account' => $request->has('api_account') ? 1 : 0,
                    'active' => $request->has('active') ? 1 : 0,
                    'version' => 1,
                    'created_by' => auth()->user()->name ?? 'admin',
                    'updated_by' => auth()->user()->name ?? 'admin',
                ];

                StoreMessageAccount::updateOrCreate([
                    'id' => $request->input('store_account_id'),
                    'store_id' => $request->input('store_id')
                ],
                    $storeAccount
                );

                return response()->json(['success' => true, 'message' => 'Data saved successfully']);
            }else{
                return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again']);
            }

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removeMessageAccount(Request $request)
    {
        // Find the message account by id
        $accId = $request->input('accId');
        $messageAcc = StoreMessageAccount::find($accId);
        $messageRecord = $messageAcc->toArray();
        //print_r($messageRecord);die;
        if ($messageAcc && !empty($messageRecord)) {
            $messageAcc->delete(); // Delete the store message account
            $messageInAcc = MessagingAccount::find($messageRecord['incoming_account_id']);
            $messageInAcc->delete(); // Delete the message accoount
            $messageOutAcc = MessagingAccount::find($messageRecord['outgoing_account_id']);
            $messageOutAcc->delete(); // Delete the message account


            // Return a success response
            return response()->json(['success' => true, 'message' => 'Message account deleted successfully']);
        }

        // If the message accoubt doesn't exist, return an error
        return response()->json(['error' => 'Message account not found!'], 422);
    }

    public function saveLogisticCenter(Request $request){

        try {

            $request['overwrite_from_address'] = $request->has('overwrite_from_address') ? 1 : 0;
            $request['overwrite_return_address'] = $request->has('overwrite_return_address') ? 1 : 0;

            $validator = Validator::make(request()->all(), [
                'store_id' => 'required',
                'logistic_center_id' => 'required'
            ]);


            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            StoreLogisticCenter::create($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function removeLogisticCenter(Request $request)
    {
        // Find the page by id
        $centerId = $request->input('centerId');
        $center = StoreLogisticCenter::find($centerId);

        if ($center) {
//            $center->delete(); // Delete the page

            // Return a success response
            return response()->json(['success' => true, 'message' => 'Logistic center deleted successfully']);
        }

        // If the center doesn't exist, return an error
        return response()->json(['error' => 'Logistic center not found!'], 422);
    }

    // Redirect User to eBay Authorization Page
    public function authorize(string $id)
    {
        $store_detail = Store::with('storeType')->find($id);

        if(isset($store_detail->storeType)){
            $clientId = $store_detail->user_id;
            switch($store_detail->storeType->name){
                case 'Ebay':
                    //https://auth.sandbox.ebay.com/oauth2/authorize?client_id=CommonMr-Testing-SBX-76628eb6f-8cb87fba&response_type=code&redirect_uri=Common_Mrintunj-CommonMr-Testin-jyehhrol&
                    $scope = 'https://api.ebay.com/oauth/api_scope+https://api.ebay.com/oauth/api_scope/sell.marketing.readonly+https://api.ebay.com/oauth/api_scope/sell.marketing+https://api.ebay.com/oauth/api_scope/sell.inventory.readonly+https://api.ebay.com/oauth/api_scope/sell.inventory+https://api.ebay.com/oauth/api_scope/sell.account.readonly+https://api.ebay.com/oauth/api_scope/sell.account+https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly+https://api.ebay.com/oauth/api_scope/sell.fulfillment+https://api.ebay.com/oauth/api_scope/sell.analytics.readonly';

                    $clientId = env('EBAY_CLIENT_ID');
                    $clientSecret = env('EBAY_CLIENT_SECRET');
                    $redirectUri = env('EBAY_REDIRECT_URI');

                    //$scope = urlencode('https://api.ebay.com/oauth/api_scope/sell.inventory');
                    $authUrl = "https://auth.ebay.com/oauth2/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope=".$scope."&state=".$id;
                    return redirect()->away($authUrl);
                    break;
                default:
                    break;

            }
        }

        return redirect('/stores');

    }

    public function copyCategories(Request $request) {
        $store_id = $request->get('store_id');
        if($store_id) {
            StoreCategorySyncJob::dispatch($store_id);
        }
        return response()->json(['success' => true, 'message' => 'Categories are being copied in the background']);
    }

    public function syncCategories(Request $request) {
        $store_id = $request->get('store_id');
        if($store_id) {
            MarketplaceCategorySyncJob::dispatch($store_id);
        }
        return response()->json(['success' => true, 'message' => 'Categories are being synced in the background']);
    }

    // Handle eBay Callback
    public function handleCallback(Request $request)
    {
        /*echo '<pre>';
        print_r($_GET);
        echo '</pre>';die;*/
        if (!$request->has('code')) {
            return response()->json(['error' => 'Authorization code not found'], 400);
        }

        $store_id = $request->get('state');
        $authorizationCode = $request->get('code');
        $clientId = env('EBAY_CLIENT_ID');
        $clientSecret = env('EBAY_CLIENT_SECRET');
        $redirectUri = env('EBAY_REDIRECT_URI');
        $ebayAuth = new EbayAuthService($clientId, $clientSecret, $redirectUri,'');
        $tokenData = $ebayAuth->getAccessToken($authorizationCode);
        //Log::info('Token Data: ' . json_encode($tokenData));
        /*echo '<pre>';
        print_r($tokenData);
        echo '</pre>';die;*/
        if (isset($tokenData['access_token'])) {

           $updateData = [
                'authorized' => 1,
                'api_key' => $tokenData['access_token'],
                'user_id' => $tokenData['refresh_token']
            ];
            $store = Store::findOrFail($store_id); // Retrieve the store by ID
            $store->update($updateData);

            //$this->storeAccessToken(Auth::user()->id, $tokenData);
            return redirect('/stores');
        }

        return response()->json(['error' => 'Failed to get access token'], 400);
    }

    public function getStoreMessageAccount(Request $request){

        $account_id = $request->input('account_id');
        $record = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('id',$account_id)->first();

        return response()->json(['success' => true, 'data'=>$record, 'incomingAccount' => $record->incomingAccount, 'outgoingAccount' => $record->outgoingAccount]);exit;
    }
}
