<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceSeries;
use App\Models\DeviceTypes;
use App\Models\ListingGenerationType;
use App\Models\ListingGenerationTypeTemplate;
use App\Models\Manufacturer;
use App\Models\ProductSearchTerm;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeListing;
use Illuminate\Http\Request;
use App\Models\Store;

class ListingController extends Controller
{
    private $perPage = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the data in the pagination format for datatable to display
        /*$products = Catalog::select('*')
            ->orderBy('product_id','desc')
            ->paginate($this->perPage);*/
        $stores = Store::Where('store_status',1)->get();
        $products = StoreTypeListing::Where('status',1)->get();

        $products = StoreTypeListing::with('product')->select('*')
            ->where('status',1)
            ->orderBy('created_at','desc')
            ->paginate($this->perPage);

        return view('listing.list',compact('stores','products'))->with('perPage', $this->perPage);
    }

    /**
     * Filter listing.
     */
    public function filter(Request $request)
    {
        $query = Catalog::query();

        $perPage = $request->input('perPage', $this->perPage);

        // Filter by product title
        if ($request->has('name') && $request->name != '') {
            $query->WhereLike('product_title', '%'.$request->name.'%');
        }

        // Filter by price range
        if ($request->has('price_min') && $request->price_min != '') {
            $query->where('product_price', '>=', $request->price_min);
        }

        if ($request->has('price_max') && $request->price_max != '') {
            $query->where('product_price', '<=', $request->price_max);
        }

        // Filter by product status
        if ($request->has('status') && $request->status != '') {
            $query->where('product_status', $request->status);
        }

        $products = $query->paginate($perPage); // Paginate results

        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('listing.product_list', compact('products'))->render(),
                'pagination' => view('listing.pagination', compact('products'))->render(),
            ]);
        }

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $pageAt = 'Add';
        $stores = Store::orderBy('name','asc')->get();
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::whereNull('parent_id')->get();
        $deviceTypes = DeviceTypes::all();
        $listingGenerationTypes = ListingGenerationType::all();
        $brands = Brand::orderBy('name','asc')->get();
        session()->forget('listing_product_details');
        return view('listing.create', compact('pageAt','stores','manufacturers','productTypes','deviceTypes','brands','listingGenerationTypes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageAt = 'Edit';
        $stores = Store::all();
        $manufacturers = Manufacturer::all();
        //echo '<pre>';
        //print_r($productTags);die;
        return view('listing.update', compact('manufacturers','pageAt','stores'));
    }

    public function getProducts(Request $request){

        $query = StoreProduct::select('store_product.id','store_product.product_id','store_product.title','store_product.price','store_product.created_at','product_type.name as ProductType')->with('Product');
        $query->leftJoin('products','products.product_id', '=', 'store_product.product_id');
        $query->leftJoin('product_type','products.product_type', '=', 'product_type.id');

        // Filter by product store
        if ($request->has('store_id') && $request->input('store_id') != '') {
            $query->where('store_product.store_id', $request->input('store_id'));
        }
        // Filter by product code
        if ($request->has('product_code') && $request->product_code != '') {
            $query->whereIn('products.product_sku', $request->product_code);
        }
        // Filter by manufacturer
        if ($request->has('manufacturer') && $request->manufacturer != '') {
            $query->whereIn('store_product.manufacturer_id', $request->manufacturer);
        }

        // Filter by product type and sub type
        if ($request->has('product_sub_type') && $request->product_sub_type != '') {
            $query->whereIn('products.product_type', $request->product_sub_type);
            $query->orWhere(function ($query) use ($request) {
                $query->whereIn('products.product_type', $request->product_type);
            });
        }else if ($request->has('product_type') && $request->product_type != '') {
            $query->whereIn('products.product_type', $request->product_type);
        }


        $products = $query->get();

        //echo '<pre>';
        //print_r($products);die;

        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('listing.search_product_list', compact('products'))->render()
            ]);
        }
    }

    public function search(Request $request){

        $term = $request->input('term');
        $type = $request->input('type');
        //echo $term;die;
        $data = [];
        switch ($type){

            case 'product_code':
                $product_code =  $request->input('product_code');
                if(!empty($product_code)){
                    $product = Catalog::select('product_id','product_sku')->WhereLike('product_sku', '%'.$term.'%')->whereNotIn('product_sku',$product_code)->get();
                }else{
                    $product = Catalog::select('product_id','product_sku')->WhereLike('product_sku', '%'.$term.'%')->get();
                }
                foreach ($product as $key => $value)
                {
                    $data["display"][] = [
                        "label" => $value->product_sku,
                        "value" => $value->product_sku
                    ];
                    $data["details"][$value->ID] = [
                        "name" => $value->product_sku,
                        "ID" => $value->product_sku
                    ];
                }
                break;
            case 'device_series':
                $device_series =  $request->input('device_series');
                if(!empty($device_series)){
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id',$device_series)->get();
                }else {
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->get();
                }
                foreach ($deviceSeries as $key => $value)
                {
                    $data["display"][] = [
                        "label" => $value->name,
                        "value" => $value->id
                    ];
                    $data["details"][$value->ID] = [
                        "name" => $value->name,
                        "ID" => $value->id
                    ];
                }
                break;
            case 'device':
                $device =  $request->input('device');
                if(!empty($device_series)){
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id',$device)->get();
                }else {
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->get();
                }
                foreach ($devices as $key => $value)
                {
                    $data["display"][] = [
                        "label" => $value->name,
                        "value" => $value->id
                    ];
                    $data["details"][$value->ID] = [
                        "name" => $value->name,
                        "ID" => $value->id
                    ];
                }
                break;
            case 'title_template':
                $template_type =  $request->input('template_type');
                $templates = ListingGenerationTypeTemplate::where('generation_type_id',$template_type)->get();
                foreach ($templates as $key => $value)
                {
                    $data["display"][] = [
                        "label" => $value->template,
                        "value" => $value->template
                    ];
                    $data["details"][$value->ID] = [
                        "name" => $value->tempate,
                        "ID" => $value->template
                    ];
                }
                break;
            default:
                break;

        }

        return response()->json($data);
    }

    /**
     * Get Product Sub Types from Product Type ID's.
     */
    public function getProductSubType(Request $request){

        $product_types = $request->input('product_types');
        if(!empty($product_types)){
            $option = '';
            $product_sub_types = ProductType::select('id','name')->WhereIn('parent_id', $product_types)->get()->toArray();
            if(!empty($product_sub_types)){
                foreach($product_sub_types as $stype){
                    $option .= '<option value="'.$stype['id'].'">'.$stype['name'].'</option>';
                }
            }

            return response()->json([
                'success' => true,
                'option' => $option
            ]);

        }else{
            return response()->json([
                'success' => false
            ]);
        }
    }

    /**
     * Get Selected Products.
     */
    public function getSelectedProducts(Request $request){

        $product_ids = $request->input('product_ids');
        $product_details = $request->input('product_detail');
        session(['listing_product_details' => $product_details]);
        $step = $request->input('step');
        if(!empty($product_ids)){
            $query = StoreProduct::select('store_product.id','store_product.product_id','store_product.title','store_product.price','store_product.created_at','product_type.name as ProductType')->with('Product');
            $query->leftJoin('products','products.product_id', '=', 'store_product.product_id');
            $query->leftJoin('product_type','products.product_type', '=', 'product_type.id');
            $query->whereIn('store_product.id', $product_ids);
            $products = $query->get();

            if($step == 2){
                $view = view('listing.selected_product_list', compact('products'))->render();
            }else if($step == 3){
                $view = view('listing.select_device_product_list', compact('products'))->render();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view
                ]);
            }

        }else{
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function getKeywords(Request $request){
        $product_ids = $request->input('selected_product');
        $keyword_seperator = $request->input('keyword_seperator');
        $long_key_length = $request->input('long_key_length');
        $short_key_length = $request->input('short_key_length');
        $append_front = $request->input('append_front');

        if(!empty($product_ids)){

            $keywords = ProductTitleKeyword::select('product_id','keyword')->whereIn('product_id',$product_ids)->orderBy('priority','asc')->get()->toArray();

            /*echo '<pre>';
            print_r($keywords);
            echo '</pre>';die;*/
            $short_keyword = [];
            $long_keyword = [];
            if(!empty($keywords)){
                $sKeyword = $lKeyword = $pro_ids = $shortFlag = $longFlag = [];
                $sflag = $lflag = 0;
                foreach($keywords as $key=>$val){
                    $pro_id = $val['product_id'];
                    if(!isset($shortFlag[$pro_id])){
                        $shortFlag[$pro_id] = 0;
                    }
                    if(!isset($longFlag[$pro_id])){
                        $longFlag[$pro_id] = $short_key_length;
                    }
                    if((!array_key_exists($pro_id, $shortFlag) || $shortFlag[$pro_id] < $short_key_length) || !array_key_exists($pro_id, $short_keyword)){
                        $short_keyword[$pro_id][] = $val['keyword'];
                        $shortFlag[$pro_id] = $shortFlag[$pro_id]+1;
                    }
                    //echo $key .'>'. $short_key_length.'<br>';
                    if($key >= $short_key_length || $long_key_length == 'all') {
                        if (((!array_key_exists($pro_id, $longFlag) || $longFlag[$pro_id] < $long_key_length) || $long_key_length == 'all') || !array_key_exists($pro_id, $long_keyword)) {
                            $long_keyword[$pro_id][] = $val['keyword'];
                            $longFlag[$pro_id] = $longFlag[$pro_id] + 1;
                        }
                    }
                    if(!in_array($pro_id, $pro_ids)){
                        $pro_ids[] = $pro_id;
                    }
                }//die;

                if(!empty($pro_ids)){
                    foreach($pro_ids as $id){
                        if(!empty($short_keyword)){
                            $sKeyword[$id] = implode(' ', $short_keyword[$id]);
                        }

                        if(!empty($short_keyword)){
                            $lKeyword[$id] = implode(' ', $long_keyword[$id]);
                        }
                    }
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'short_keyword' => $sKeyword,
                        'long_keyword' => $lKeyword
                    ]);
                }

            }

        }

        return response()->json([
            'success' => false
        ]);
    }

    public function getTitleKeywords(Request $request){
        $product_ids = $request->input('selected_product');
        $title_length = $request->input('title_length');

        if(!empty($product_ids)){

            $keywords = ProductTitleKeyword::select('product_id','keyword')->whereIn('product_id',$product_ids)->orderBy('priority','asc')->get()->toArray();
            if(!empty($keywords)){
                $title_keyword = $tKeyword = $pro_ids = $Flag = [];

                foreach($keywords as $key=>$val){
                    $pro_id = $val['product_id'];
                    if(!isset($shortFlag[$pro_id])){$shortFlag[$pro_id] = 0;}
                    if(!isset($Flag[$pro_id])){$Flag[$pro_id] = 0;}

                    if(((!array_key_exists($pro_id, $Flag) || $Flag[$pro_id] < $title_length)) || !array_key_exists($pro_id, $title_keyword)){
                        $title_keyword[$pro_id][] = $val['keyword'];
                        $Flag[$pro_id] = $Flag[$pro_id] + 1;
                    }

                    if(!in_array($pro_id, $pro_ids)){
                        $pro_ids[] = $pro_id;
                    }
                }
                if(!empty($pro_ids)){
                    foreach($pro_ids as $id){
                        if(!empty($title_keyword)){
                            $tKeyword[$id] = implode(' ', $title_keyword[$id]);
                        }
                    }
                }
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'title_keyword' => $tKeyword
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    /**
     * Get Compatible Device.
     */
    public function getCompatibleDevice(Request $request){

        $product_id = $request->input('product_id');
        $device_type = $request->input('device_type');
        $brand = $request->input('brand');
        $rating = $request->input('rating');
        $popularity = $request->input('popularity');
        if($product_id){
            $query = Device::select('device.*', 'product_interface.product_id')->with('brand')->with('deviceType');
            $query->leftJoin('device_interface','device_interface.device_id', '=', 'device.id');
            $query->leftJoin('product_interface','product_interface.interface_id', '=', 'device_interface.interface_id');
            $query->whereIn('product_interface.product_id', $product_id);
            if($device_type){
                $query->whereIn('device.device_type_id', $device_type);
            }
            if($brand){
                $query->whereIn('device.brand_id', $brand);
            }
            if($rating){
                $query->where('device.rating_id', $rating);
            }
            if($popularity){
                $query->where('device.rating_id', $popularity);
            }
            $query->orderBy('device.name', 'asc');
            $devices = $query->get();
            //echo $query->toSql();die;
            $count = $devices->count();


            $view = view('listing.target_product_listing', compact('devices'))->render();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view,
                    'total' => $count,
                ]);
            }

        }else{
            return response()->json([
                'error' => true
            ]);
        }
    }

    public function getTitleTemplate(Request $request){

        $generate_type = $request->input('type');
        if($generate_type){

            $templates = ListingGenerationTypeTemplate::where('generation_type_id',$generate_type)->get();
            $data = [];
            $option = '';
            if(!empty($templates)){
                foreach($templates as $key=>$template){
                    if($template->default_template == 1){
                        $option = $template->template;
                    }
                    $data["display"][] = [
                        "label" => $template->template,
                        "value" => $template->template
                    ];
                    $data["details"][$template->id] = [
                        "name" => $template->tempate,
                        "id" => $template->template
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'option' => $option,
                'data' => $data,
            ]);
        }
    }

    public function generateProductListing(Request $request){
        $product_ids = $request->input('product_id');
        $device_type = $request->input('device_type');
        $brand = $request->input('brand');
        $rating = $request->input('rating');
        $popularity = $request->input('popularity');
        $store_id = $request->input('store_id');
        $title_template = $request->input('title_template');
        $compress = $request->input('compress');
        $generate_type = $request->input('generate_type');
        $brand_seperator = $request->input('brand_seperator');
        $group_seperator = $request->input('group_seperator');
        $device_seperator = $request->input('device_seperator');
        $product_details = session('listing_product_details');
        $product_details = (array) json_decode($product_details);
        /*echo '<pre>';
        print_r($product_details);
        echo '</pre>';*/

        if($store_id && $product_ids){

            $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
            $max_title_length = $store_type_detail->max_title_length;
            $products = StoreProduct::getStoreProducts($product_ids);
            //echo $query2->toSql();die;

            //echo '<pre>';
            //print_r($products);die;
            if(count($products ?? []) > 0){
                $productsArray = [];
                foreach($products as $product){

                    $product_short_keyword = $product_details[$product->id]->short_keyword;
                    $product_long_keyword = $product_details[$product->id]->long_keyword;
                    $product_price = $product_details[$product->id]->price;
                    if($title_template){
                        if(str_contains($title_template, '#short_keyword')){

                            $title_template = str_replace('#short_keyword', $product_short_keyword, $title_template);
                        }

                        if(str_contains($title_template, '#long_keyword')){

                            $title_template = str_replace('#long_keyword', $product_long_keyword, $title_template);
                        }
                    }

                    $query = Device::select('device.*','brands.name as brand_name')->with('deviceType');
                    $query->leftJoin('brands','brands.id', '=', 'device.brand_id');
                    $query->leftJoin('device_interface','device_interface.device_id', '=', 'device.id');
                    $query->leftJoin('product_interface','product_interface.interface_id', '=', 'device_interface.interface_id');
                    $query->where('product_interface.product_id', $product->product_id);
                    if($device_type){
                        $query->whereIn('device.device_type_id', $device_type);
                    }
                    if($brand){
                        $query->whereIn('device.brand_id', $brand);
                    }
                    if($rating){
                        $query->where('device.rating_id', $rating);
                    }
                    if($popularity){
                        $query->where('device.rating_id', $popularity);
                    }
                    $query->orderBy('brand_name', 'asc');
                    $query->orderBy('device.name', 'asc');
                    $devices = $query->get();

                    $productsArray[] = $this->generateProductTitles($title_template,$product,$devices,$max_title_length, $brand_seperator,$device_seperator,$compress, $generate_type,$product_price);
                }

                /*echo '<pre>';
                print_r($productsArray);
                echo '</pre>';die;*/

            }
            if(!empty($productsArray)){
                $view = view('listing.target_listing', compact('productsArray','max_title_length'))->render();

                $models = array();
                $totalCount = 0;
                foreach($productsArray as $product){
                    if(!empty($product)){
                        $totalCount = count($product);
                        foreach($product as $key=>$pro){
                            $models[$key] = $pro['model'];
                        }
                    }
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'html' => $view,
                        'models' => $models,
                        'totalproduct' => $totalCount,
                    ]);
                }
            }else{
                return response()->json([
                    'error' => true
                ]);
            }


        }else{
            return response()->json([
                'error' => true
            ]);
        }
    }

    function generateProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price) {
        $titles = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $device_seperator; // Separator between device names
        $lastBrand = null;
        $currentBrand = null;
        $cnt = 0;
        foreach ($devices as $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name.' '.$device->name;
            $parts = explode(' ', $currentBrand, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? ''; // If no model, treat as empty

            // Use only the model if the brand is the same as the last one
            if($compress) {
                $nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            }else{
                $nameToAdd = $device->name;
            }


            // Check if adding the device exceeds the character limit
            $tempDevices = array_merge($currentDevices, [$nameToAdd]);
            $tempDeviceString = implode(', ', $tempDevices);
            $tempTitle = str_replace('#model_series', $tempDeviceString, $baseTitle);

            /*$sku = StoreTypeListing::generateUniqueAlphabeticSku($product->product_sku);*/

            // Check if adding the device exceeds the character limit
            if (strlen($tempTitle) > $charLimit) {

                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                $titles[$cnt]['title'] = str_replace('#model_series', implode(', ', $currentDevices), $baseTitle);
                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $titles[$cnt]['product_code'] = $product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['model'][] = $device->name;
            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['price'] = $product_price;
                //$currentTitle .= ($currentTitle === $baseTitle ? ' ' : ' '.$separator.' ') . $nameToAdd;
                $currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            /*$titles[$cnt]['title'] = rtrim($currentTitle, ' '.$separator.' ');*/
            $titles[$cnt]['title'] = str_replace('#model_series', implode(', ', $currentDevices), $baseTitle);
        }

        return $titles;
    }

    public function saveListingProducts(Request $request){

        if($request->input()){
            if(is_array($request->input('final_product_title'))){
                $product_titles = $request->input('final_product_title');
                $product_prices = $request->input('final_product_price');
                $product_codes = $request->input('final_product_code');
                $product_ids = $request->input('final_product_id');
                $store_id = $request->input('store_id');
                foreach($product_titles as $key=>$product){
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key]);
                    $created_by = auth()->user()->name ?? 'admin';
                    $listingProductArr = [
                        'product_name' => $product,
                        'sku' => $sku,
                        'price' => $product_prices[$key],
                        'item_id' => $product_ids[$key],
                        'store_id' => $store_id,
                        'has_offers' => 0,
                        'status' => 1,
                        'created_by' => $created_by,
                        'updated_by' => $created_by
                    ];

                    StoreTypeListing::create($listingProductArr);
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Product listing created successfully!'
                    ]);
                }
            }
        }

        return response()->json(['error' => true,'message' => 'Something went wrong! Please try again!']);
    }

}
