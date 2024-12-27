<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceSeries;
use App\Models\DeviceTypes;
use App\Models\Listing;
use App\Models\ListingGenerationType;
use App\Models\ListingGenerationTypeTemplate;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\Manufacturer;
use App\Models\ProductSearchTerm;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    private $perPage = 20;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the data in the pagination format for datatable to display
        /*$products = Catalog::select('*')
            ->orderBy('product_id','desc')
            ->paginate($this->perPage);*/
        $stores = Store::with('storeType')->where('store_status',1)->orderBy('name','asc')->get();
        $device_types = DeviceTypes::Where('status',1)->get();
        $product_types = ProductType::with('children')->where('parent_id',NULL)->get();
        $manufacturers = Manufacturer::all();
        $devices = Device::all();
        $brands = Brand::all();
        //DB::enableQueryLog();
        /*$products = ListingItem::select('listing_item.id','listing_item.title','listing_item.sku','listing_item.store_sku','listing_item.price','listing_item.sales_count','listing_item.last_sale_date','products.product_sku','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name','listing_generation_type.name as generation_type_name','store_type.name as storeTypeName')
//            ->leftJoin('listing_item_link','listing_item.id', '=', 'listing_item_link.item_id')
            ->leftJoin('store_product','store_product.id', '=', 'listing_item.store_product_id')
            ->leftJoin('products','products.product_id', '=', 'store_product.product_id')
            ->leftJoin('store','store.id', '=', 'store_product.store_id')
            ->leftJoin('store_type','store_type.id', '=', 'store.store_type_id')
            ->leftJoin('listing_generation_type','listing_generation_type.id', '=', 'listing_item.generation_type_id')
            ->where('listing_item.status',1)
//            ->groupBy('listing_item.id','listing_item.title','listing_item.sku','listing_item.store_sku','listing_item.price','listing_item.sales_count','listing_item.last_sale_date','products.product_sku', 'products.dimensions_width', 'products.product_color', 'store.name', 'store.marketplace_name')
//            ->orderBy('listing_item.created_at','desc')
            ->paginate($this->perPage);*/
        /*$lastQuery = DB::getQueryLog();
        dd(end($lastQuery));*/

        /*echo '<pre>';
        print_r($products);
        echo '</pre>';die;*/

        return view('listing.list',compact('stores','device_types','product_types','manufacturers','devices','brands'))->with('perPage', $this->perPage);
    }

    /**
     * Filter listing.
     */
    public function filter(Request $request)
    {


        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = ListingItem::select('listing_item.id','listing_item.title','listing_item.sku','listing_item.store_sku','listing_item.price','listing_item.sales_count','listing_item.last_sale_date','listing_item.status','listing_item.created_at','products.product_sku','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name','listing_generation_type.name as generation_type_name','store_type.name as storeTypeName');
//        $query->leftJoin('listing_item_link','listing_item.id', '=', 'listing_item_link.item_id');
        $query->leftJoin('store_product','store_product.id', '=', 'listing_item.store_product_id');
        $query->leftJoin('products','products.product_id', '=', 'store_product.product_id');
        $query->leftJoin('store','store.id', '=', 'store_product.store_id');
        $query->leftJoin('store_type','store_type.id', '=', 'store.store_type_id');
        $query->leftJoin('listing_generation_type','listing_generation_type.id', '=', 'listing_item.generation_type_id');

        //$perPage = $request->input('perPage', $this->perPage);


        // Filter by sku
        if ($request->has('sku') && $request->sku != '') {
            $operator = $request->sku_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.sku', '%'.$request->sku.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.sku', $request->sku.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.sku', '%'.$request->sku);
                    break;
                case '=':
                    $query->Where('listing_item.sku', $request->sku);
                    break;
                case 'in':
                    $skus = explode(',',$request->sku);
                    $query->WhereIn('listing_item.sku',$skus);
                    break;
                case 'not_in':
                    $skus = explode(',',$request->sku);
                    $query->WhereNotIn('listing_item.sku', $skus);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.sku', '%'.$request->sku.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.sku');
                    break;
            }

        }

        // Filter by store
        if ($request->has('store') && $request->store != '') {
            $operator = $request->store_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('store.name', '%'.$request->store.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('store.name', $request->store.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('store.name', '%'.$request->store);
                    break;
                case '=':
                    $query->Where('store.name', $request->store);
                    break;
                case 'in':
                    $stores = explode(',',$request->store);
                    $query->WhereIn('store.name',$stores);
                    break;
                case 'not_in':
                    $stores = explode(',',$request->store);
                    $query->WhereNotIn('store.name', $stores);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('store.name', '%'.$request->store.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('store.name');
                    break;
            }

        }

        // Filter by title
        if ($request->has('title') && $request->title != '') {
            $operator = $request->title_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.title', '%'.$request->title.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.title', $request->title.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.title', '%'.$request->title);
                    break;
                case '=':
                    $query->Where('listing_item.title', $request->title);
                    break;
                case 'in':
                    $stores = explode(',',$request->title);
                    $query->WhereIn('listing_item.title',$stores);
                    break;
                case 'not_in':
                    $stores = explode(',',$request->title);
                    $query->WhereNotIn('listing_item.title', $stores);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.title', '%'.$request->title.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.title');
                    break;
            }

        }

        // Filter by product code
        if ($request->has('product_code') && $request->product_code != '') {
            $operator = $request->product_code_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('products.product_sku', '%'.$request->product_code.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('products.product_sku', $request->product_code.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('products.product_sku', '%'.$request->product_code);
                    break;
                case '=':
                    $query->Where('products.product_sku', $request->product_code);
                    break;
                case 'in':
                    $producCodes = explode(',',$request->product_code);
                    $query->WhereIn('products.product_sku',$producCodes);
                    break;
                case 'not_in':
                    $producCodes = explode(',',$request->product_code);
                    $query->WhereNotIn('products.product_sku', $producCodes);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('products.product_sku', '%'.$request->product_code.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('products.product_sku');
                    break;
            }

        }

        // Filter by price
        if ($request->has('price') && $request->price != '') {
            $operator = $request->price_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.price', '%'.$request->price.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.price', $request->price.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.price', '%'.$request->price);
                    break;
                case '=':
                    $query->Where('listing_item.price', $request->price);
                    break;
                case 'in':
                    $prices = explode(',',$request->price);
                    $query->WhereIn('listing_item.price',$prices);
                    break;
                case 'not_in':
                    $prices = explode(',',$request->price);
                    $query->WhereNotIn('listing_item.price', $prices);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.price', '%'.$request->price.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.price');
                    break;
            }

        }

        // Filter by ID
        if ($request->has('id') && $request->id != '') {
            $operator = $request->id_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.id', '%'.$request->id.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.id', $request->id.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.id', '%'.$request->id);
                    break;
                case '=':
                    $query->Where('listing_item.id', $request->id);
                    break;
                case 'in':
                    $ids = explode(',',$request->id);
                    $query->WhereIn('listing_item.id',$ids);
                    break;
                case 'not_in':
                    $ids = explode(',',$request->id);
                    $query->WhereNotIn('listing_item.id', $ids);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.id', '%'.$request->id.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.id');
                    break;
            }

        }

        // Filter by sales count
        if ($request->has('sales_count') && $request->sales_count != '') {
            $operator = $request->sales_count_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.sales_count', '%'.$request->sales_count.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.sales_count', $request->sales_count.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.sales_count', '%'.$request->sales_count);
                    break;
                case '=':
                    $query->Where('listing_item.sales_count', $request->sales_count);
                    break;
                case 'in':
                    $sales_counts = explode(',',$request->sales_count);
                    $query->WhereIn('listing_item.sales_count',$sales_counts);
                    break;
                case 'not_in':
                    $sales_counts = explode(',',$request->sales_count);
                    $query->WhereNotIn('listing_item.sales_count', $sales_counts);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.sales_count', '%'.$request->sales_count.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.sales_count');
                    break;
            }

        }

        // Filter by manufacturer
        if ($request->has('manufacturer') && $request->manufacturer != '') {
            $operator = $request->manufacturer_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.manufacturer_id', '%'.$request->manufacturer.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.manufacturer_id', $request->manufacturer.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.manufacturer_id', '%'.$request->manufacturer);
                    break;
                case '=':
                    $query->Where('listing_item.manufacturer_id', $request->manufacturer);
                    break;
                case 'in':
                    $manufacturers = explode(',',$request->manufacturer);
                    $query->WhereIn('listing_item.manufacturer_id',$manufacturers);
                    break;
                case 'not_in':
                    $manufacturers = explode(',',$request->manufacturer);
                    $query->WhereNotIn('listing_item.manufacturer_id', $manufacturers);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.manufacturer_id', '%'.$request->manufacturer.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.manufacturer_id');
                    break;
            }

        }

        // Filter by sync message
        if ($request->has('sync_message') && $request->sync_message != '') {
            $operator = $request->sync_message_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('listing_item.sync_message', '%'.$request->sync_message.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('listing_item.sync_message', $request->sync_message.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('listing_item.sync_message', '%'.$request->sync_message);
                    break;
                case '=':
                    $query->Where('listing_item.sync_message', $request->sync_message);
                    break;
                case 'in':
                    $sync_messages = explode(',',$request->sync_message);
                    $query->WhereIn('listing_item.sync_message',$sync_messages);
                    break;
                case 'not_in':
                    $sync_messages = explode(',',$request->sync_message);
                    $query->WhereNotIn('listing_item.sync_message', $sync_messages);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('listing_item.sync_message', '%'.$request->sync_message.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('listing_item.sync_message');
                    break;
            }

        }

        // Filter by device
        if ($request->has('device') && $request->device != '') {
            $query->where('listing_item.device_id', $request->device);
        }

        // Filter by brand
        if ($request->has('device_brand') && $request->device_brand != '') {
            $query->where('listing_item.brand_id', $request->device_brand);
        }

        // Filter by qty
        if ($request->has('quantity') && $request->quantity != '') {
            $query->where('listing_item.quantity', $request->quantity);
        }


        // Filter by product type
        if ($request->has('product_type') && $request->product_type != '') {
            $query->where('products.product_type', $request->product_type);
        }

        // Filter by product title
        if ($request->has('device_type') && $request->device_type != '') {
            $query->where('product_title', $request->device_type);
        }

        // Filter by created date
        if ($request->has('item_created') && $request->item_created != '') {
            $query->where('listing_item.created_at', $request->item_created);
        }

        // Filter by last sale date
        if ($request->has('last_sale_date') && $request->last_sale_date != '') {
            $query->where('listing_item.last_sale_date', $request->last_sale_date);
        }

        // Filter by product type
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_product.store_id', $request->store_id);
        }


        // Filter by product status
        if ($request->has('listing_status') && $request->listing_status != '') {
            $query->where('listing_item.status', $request->listing_status);
        }

        // Filter by product status
        if ($request->has('product_status') && $request->product_status != '') {
            $query->where('products.status', $request->product_status);
        }

//        $query->groupBy('listing_item.id','listing_item.title','listing_item.sku','listing_item.store_sku','listing_item.price','listing_item.sales_count','listing_item.last_sale_date','products.product_sku', 'products.dimensions_width', 'products.product_color', 'store.name', 'store.marketplace_name');

        if($columnName != ''){
            switch(strtolower($columnName)){
                case 'sku':
                    $query->orderBy('listing_item.sku', $columnSortOrder);
                    break;
                case 'sale_count':
                    $query->orderBy('listing_item.sale_count', $columnSortOrder);
                    break;
                case 'last_sale_date':
                    $query->orderBy('listing_item.last_sale_date', $columnSortOrder);
                    break;
                case 'store_sku':
                    $query->orderBy('listing_item.store_sku', $columnSortOrder);
                    break;
                case 'product_sku':
                    $query->orderBy('products.product_sku', $columnSortOrder);
                    break;
                case 'store_name':
                    $query->orderBy('store.name', $columnSortOrder);
                    break;
                case 'title':
                    $query->orderBy('listing_item.title', $columnSortOrder);
                    break;
                case 'status':
                    $query->orderBy('listing_item.status', $columnSortOrder);
                    break;
                case 'product_size':
                    $query->orderBy('products.dimensions_width', $columnSortOrder);
                    break;
                case 'product_color':
                    $query->orderBy('products.product_color', $columnSortOrder);
                    break;
                case 'sync_status':
                    $query->orderBy('listing_item.sync_status', $columnSortOrder);
                    break;
                case 'generation_type_name':
                    $query->orderBy('listing_generation_type.name', $columnSortOrder);
                    break;
                case 'price':
                    $query->orderBy('listing_item.price', $columnSortOrder);
                    break;
                case 'qty':
                    $query->orderBy('listing_item.quantity', $columnSortOrder);
                    break;
                case 'created_date':
                    $query->orderBy('listing_item.created_at', $columnSortOrder);
                    break;

            }
        }

        $productsRecord = $query->paginate($perPage); // Paginate results
        $products = $query->skip($start)->take($perPage)->get(); // Paginate results

        //echo $query->toSql();die;

        $totalRecordwithFilter = $productsRecord->total();

        $data = [];
        if(!empty((array) $products)){
            foreach($products as $product) {
                $data[] = array(
                    "sku" => $product->sku,
                    "sale_count" => ($product->sale_count) ? $product->sale_count : 0,
                    "last_sale_date" => '',
                    "store_sku" => $product->store_sku,
                    "product_sku" => $product->product_sku,
                    "store_name" => $product->store_name.' - '.$product->storeTypeName,
                    "title" => strlen($product->title) > 50 ? substr($product->title, 0, 50) . "..." : $product->title,
                    "status" => ($product->status == 1) ? 'Active' : 'Inactive',
                    "product_size" => '',
                    "product_color" => $product->product_color,
                    "sync_status" => '',
                    "generation_type_name" => $product->generation_type_name,
                    "price" => $product->price,
                    "qty" => 0,
                    "created_date" => date('Y-m-d H:i:s',strtotime($product->created_at)),
                    "action" => '<a href="'.route('listing.edit', $product->id).'" class="btn btn-primary">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                                </a>',
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;

        /*// Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('listing.product_list', compact('products'))->render(),
                'pagination' => view('listing.pagination', compact('products','perPage'))->render(),
            ]);
        }

        return view('listing.list');*/
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $pageAt = 'Add';
        $stores = Store::with('storeType')->where('store_status',1)->orderBy('name','asc')->get();
        /*echo '<pre>';
        print_r($stores);die;*/
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::whereNull('parent_id')->get();
        $deviceTypes = DeviceTypes::all();
        $listingGenerationTypes = ListingGenerationType::orderBy('code','asc')->get();
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
        $productDetail = ListingItem::select('listing_item.*','products.product_sku','products.product_description','products.manufacturer_id','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name','listing_generation_type.name as generation_name','store_type.product_qty')
            //->leftJoin('listing_item_link','listing_item.id', '=', 'listing_item_link.item_id')
            ->leftJoin('store_product','store_product.id', '=', 'listing_item.store_product_id')
            ->leftJoin('products','products.product_id', '=', 'store_product.product_id')
            ->leftJoin('store','store.id', '=', 'store_product.store_id')
            ->leftJoin('store_type','store.store_type_id', '=', 'store_type.id')
            ->leftJoin('listing_generation_type','listing_generation_type.id', '=', 'listing_item.generation_type_id')
            ->where('listing_item.id',$id)
            ->first();

        $store_type_listing_id = $productDetail->store_type_listing_id;
        $storeTypeDetail = StoreTypeListing::findOrFail($store_type_listing_id);

        $productVariants = ListingItemDetail::select('listing_item_detail.*','brands.name as brand_name','device.name as device_name')
            ->leftJoin('device','device.id', '=', 'listing_item_detail.device_id')
            ->leftJoin('brands','brands.id', '=', 'device.brand_id')
            ->where('listing_item_detail.listing_item_id',$productDetail->id)
            ->get();

        /*$productDetail = StoreTypeListing::select('store_type_listing.*','products.product_sku','products.product_description','products.manufacturer_id','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name')
            ->leftJoin('products','products.product_id', '=', 'store_type_listing.item_id')
            ->leftJoin('store','store.id', '=', 'store_type_listing.store_id')
            ->where('listing_item.id',$id)
            ->first();*/

        return view('listing.update', compact('manufacturers','pageAt','stores','productDetail','storeTypeDetail','productVariants'));
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
        $store_id = $request->input('store_id');
        session(['listing_product_details' => $product_details]);
        $step = $request->input('step');
        if(!empty($product_ids)){
            $query = StoreProduct::select('store_product.id','store_product.store_type_category_id','store_product.product_id','store_product.title','store_product.price','store_product.created_at','product_type.name as ProductType')->with('Product');
            $query->leftJoin('products','products.product_id', '=', 'store_product.product_id');
            $query->leftJoin('product_type','products.product_type', '=', 'product_type.id');
            $query->whereIn('store_product.id', $product_ids);
            $products = $query->get();
            $storeCategories = [];
            if($store_id){
                $storeCategories = StoreTypeCategory::select('store_type_category.*')
                    ->leftJoin('store_type','store_type.id', '=', 'store_type_category.store_type_id')
                    ->leftJoin('store','store_type.id', '=', 'store.store_type_id')
                    ->where('store.id',$store_id)
                    ->get();
            }

            if($step == 2){
                $view = view('listing.selected_product_list', compact('products','storeCategories'))->render();
            }else if($step == 3){
                $view = view('listing.select_device_product_list', compact('products','storeCategories'))->render();
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
        $is_selected = $request->input('is_selected');
        $selected_device = $request->input('selected_device');
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


            $view = view('listing.target_product_listing', compact('devices','selected_device','is_selected'))->render();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view,
                    'total' => $count
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
        $title_templates = $request->input('title_template');
        $compress = $request->input('compress');
        $generate_type = $request->input('generate_type');
        $generate_type_name = $request->input('generate_type_name');
        $brand_seperator = $request->input('brand_seperator');
        $group_seperator = $request->input('group_seperator');
        $device_seperator = $request->input('device_seperator');
        $selected_device = $request->input('selected_device');
        $product_details = session('listing_product_details');
        $product_details = (array) json_decode($product_details);
        /*echo '<pre>';
        print_r($product_details);
        echo '</pre>';die;*/

        if($store_id && $product_ids){

            $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
            $max_title_length = $store_type_detail->max_title_length;
            $products = StoreProduct::getStoreProducts($product_ids);
            //echo $query2->toSql();die;

            //echo '<pre>';
            //print_r($products);die;
            if(count($products ?? []) > 0){
                $productsArray = [];
                foreach($products as $key=>$product){
                    $store_product_id = $product->id;
                    $product_short_keyword = $product_details[$product->id]->short_keyword;
                    $product_long_keyword = $product_details[$product->id]->long_keyword;
                    $product_title = $product_details[$product->id]->title;
                    $product_price = $product_details[$product->id]->price;
                    $product_price_overwrite = $product_details[$product->id]->price_overwrite;
                    $title_template = $title_templates[$key];
                    if($title_template){
                        if(str_contains($title_template, '#short_keyword')){

                            $title_template = str_replace('#short_keyword', $product_short_keyword, $title_template);
                        }

                        if(str_contains($title_template, '#long_keyword')){

                            $title_template = str_replace('#long_keyword', $product_long_keyword, $title_template);
                        }

                        if(str_contains($title_template, '#unique_code')){

                            $title_template = str_replace('#unique_code', '[^]', $title_template);
                        }

                        if(str_contains($title_template, '#product')){

                            $title_template = str_replace('#product', $product_title, $title_template);
                        }
                    }

                    $query = Device::select('device.*','brands.name as brand_name')->with('deviceType');
                    $query->leftJoin('brands','brands.id', '=', 'device.brand_id');
                    $query->leftJoin('device_interface','device_interface.device_id', '=', 'device.id');
                    $query->leftJoin('product_interface','product_interface.interface_id', '=', 'device_interface.interface_id');
                    $query->where('product_interface.product_id', $product->product_id);
                    if(isset($selected_device[$product->product_id]) && !empty($selected_device[$product->product_id])){
                        $query->whereIn('device.id', $selected_device[$product->product_id]);
                    }
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

                    /*echo '<pre>';
                    print_r($devices);
                    echo '<pre>';die;*/

                    if($generate_type_name){
                        switch(trim(strtolower($generate_type_name))){
                            case 'combined devices':
                                $productsArray[] = $this->generateProductTitles($title_template,$product,$devices,$max_title_length, $brand_seperator,$device_seperator,$compress,$generate_type,$product_price,$store_product_id,$product_price_overwrite);
                                break;
                            case 'free group':
                                $productsArray[] = $this->generateFreeGroupProductTitles($title_template,$product,$devices,$max_title_length, $brand_seperator,$device_seperator,$compress,$generate_type,$product_price,$store_product_id,$product_price_overwrite);
                                break;
                            case 'single device':
                                $productsArray[] = $this->generateSingleDeviceProductTitles($title_template,$product,$devices,$max_title_length, $brand_seperator,$device_seperator,$compress,$generate_type,$product_price,$store_product_id,$product_price_overwrite);
                                break;
                        }
                    }


                }

                /*echo '<pre>';
                print_r($productsArray);
                echo '</pre>';die;*/

            }
            if(!empty($productsArray)){
                $view = view('listing.target_listing', compact('productsArray','max_title_length'))->render();

                $models = $modelDetail = array();
                $totalCount = 0;
                foreach($productsArray as $product){
                    if(!empty($product)){
                        $totalCount = count($product);
                        foreach($product as $key=>$pro){
                            if(isset($pro['model'])) {
                                $models[$key] = $pro['model'];
                                $modelDetail[$key] = $pro['model_detail'];
                            }
                        }
                    }
                }

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'html' => $view,
                        'models' => $models,
                        'modelDetail' => $modelDetail,
                        'totalproduct' => $totalCount,
                        'generate_type' => $generate_type,
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

    private function generateProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price,$store_product_id,$product_price_overwrite) {
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

            if(str_contains($baseTitle, '#device')){
                $baseTitle = str_replace('#device', $currentBrand, $baseTitle);
            }

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
                $titles[$cnt]['title'] = str_replace('#model_series', implode($separator.' ', $currentDevices), $baseTitle);
                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $titles[$cnt]['product_code'] = $product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                //$currentTitle .= ($currentTitle === $baseTitle ? ' ' : ' '.$separator.' ') . $nameToAdd;
                $currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            /*$titles[$cnt]['title'] = rtrim($currentTitle, ' '.$separator.' ');*/
            $titles[$cnt]['title'] = str_replace('#model_series', implode($separator.' ', $currentDevices), $baseTitle);
        }

        return $titles;
    }

    private function generateSingleDeviceProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price,$store_product_id,$product_price_overwrite) {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k=>$device) {

            $currentBrand = $device->brand_name.' '.$device->name;

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#device', $currentBrand, $tempTitle);
            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product_sku;
            $titles[$k]['product_id'] = $product->product_id;
            $titles[$k]['price'] = $product_price;
            $titles[$k]['price_overwrite'] = $product_price_overwrite;
            $titles[$k]['generate_type'] = $generate_type;
            $titles[$k]['store_product_id'] = $store_product_id;
            $titles[$k]['model'][] = $device->name;
            $titles[$k]['model_detail'][] = $device;

        }


        return $titles;
    }

    private function generateFreeGroupProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price,$store_product_id,$product_price_overwrite) {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k=>$device) {

            $currentBrand = $device->brand_name.' '.$device->name;

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#device', $currentBrand, $tempTitle);
            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product_sku;
            $titles[$k]['product_id'] = $product->product_id;
            $titles[$k]['price'] = $product_price;
            $titles[$k]['price_overwrite'] = $product_price_overwrite;
            $titles[$k]['generate_type'] = $generate_type;
            $titles[$k]['store_product_id'] = $store_product_id;
            $titles[$k]['model'][] = $device->name;
            $titles[$k]['model_detail'][] = $device;

        }
        return $titles;
    }

    public function saveListingProducts(Request $request){

        if($request->input()){
            if(is_array($request->input('final_product_title'))){
                $generate_type = $request->input('generate_type');
                $generation_type_id = $request->input('generation_type_id');
                $product_titles = $request->input('final_product_title');
                $product_prices = $request->input('final_product_price');
                $product_price_overwrites = $request->input('final_product_price_overwrite');
                $product_codes = $request->input('final_product_code');
                $store_product_ids = $request->input('final_store_product_id');
                $product_ids = $request->input('final_product_id');
                $store_id = $request->input('store_id');
                $product_models = $request->input('product_models');
                if($product_models){
                    $product_models = json_decode($product_models);
                }


                $created_by = auth()->user()->name ?? 'admin';

                $listingArr = [
                    'store_id' => $store_id,
                    'generation_type_id' => $generation_type_id,
                    'feed_type' => 1,
                    'status' => 2,
                    'chained' => 1,
                    'include_carriers' => 0,
                    'brand_combo' => 0,
                    'carrier_combo' => 0,
                    'created_by' => $created_by,
                    'updated_by' => $created_by
                ];

                $lisrecord = Listing::create($listingArr);
                $listing_id = $lisrecord->id;

                /*echo '<pre>';
                print_r();
                echo '</pre>';die;*/
                $summary_products = [];
                if($listing_id && !empty($product_titles)) {
                    foreach ($product_titles as $key => $product) {
                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key],4);
                        $productDetail = StoreProduct::getStoreProductDetail($store_product_ids[$key]);
                        $productDetail = $productDetail->toArray();
                        $product_name = $product;
                        if(str_contains($product, '[^]')){
                            $product_name = str_replace('[^]', $sku, $product);
                        }
                        $summary_products[$key]['title'] = $product_name;
                        $summary_products[$key]['sku'] = $sku;
                        $summary_products[$key]['product_type'] = $productDetail['ProductType'];
                        $summary_products[$key]['product_code'] = $productDetail['product_sku'];
                        $listingProductArr = [
                            'product_name' => $product_name,
                            'description' => $productDetail['description'],
                            'product_category' => $productDetail['ProductType'],
                            'sku' => $sku,
                            'price' => $product_prices[$key],
                            'item_id' => $product_ids[$key],
                            'store_id' => $store_id,
                            'has_offers' => 0,
                            'status' => 1,
                            'created_by' => $created_by,
                            'updated_by' => $created_by
                        ];
                        $record = StoreTypeListing::create($listingProductArr);
                        $store_type_listing_id = $record->id;

                        if($store_type_listing_id){

                            $modelDetail = array(
                                'title' => $product_name,
                                'description' => $productDetail['description'],
                                'store_type_listing_id' => $store_type_listing_id,
                                'sku' => $sku,
                                'store_sku' => $store_id,
                                'price' => $product_prices[$key],
                                'sync_status' => 1,
                                'store_product_id' => $store_product_ids[$key],
                                'generation_type_id' => $generation_type_id,
                                'overwrite_title' => 1,
                                'overwrite_price' => $product_price_overwrites[$key],
                                'status' => 1,
                                /*'model_name' => $model->name,
                                'brand_name' => $model->brand_name,
                                'device_series_id' => $model->device_series_id,
                                'device_type_id' => $model->device_type_id,
                                'device_type_name' => $model->device_type->name,
                                'device_code' => $model->code,
                                'device_rating_id' => $model->rating_id,*/
                                'created_by' => $created_by
                            );

                            $item = ListingItem::create($modelDetail);
                            $listing_item_id = $item->id;
                            $modelNames = array();
                            if($listing_item_id){
                                if (isset($product_models[$key]) && is_array($product_models[$key])) {

                                    if(count($product_models[$key] ?? []) > 0){
                                        $limit = count($product_models[$key]);
                                        $upcCodes = UPCCodes::where('store',$store_id)->where('is_used',0)->orderBy('sequence_id','asc')->limit($limit)->get();
                                    }
                                    foreach ($product_models[$key] as $k=>$model) {
                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key],6);
                                        if(str_contains($product, '[^]')){
                                            $product = str_replace('[^]', $nsku, $product);
                                        }
                                        $modelNames[] = $model->name;
                                        $itemDetail = array(
                                            'sku' => $nsku,
                                            'title' => $product,
                                            'upc' => isset($upcCodes[$k]['upc_code']) ? $upcCodes[$k]['upc_code'] : '',
                                            'device_id' => $model->id,
                                            'listing_item_id' => $listing_item_id,
                                            'store_sku' => $store_id,
                                            'created_by' => $created_by
                                        );

                                        $rec = ListingItemDetail::create($itemDetail);

                                        if($rec){
                                            $record = UPCCodes::find($upcCodes[$k]['id']);
                                            $record->update(['is_used'=>1]);
                                        }

                                        $itemLink = array(
                                            'item_id' => $listing_item_id,
                                            'listing_id' => $listing_id,
                                            'sync_status' => 6,
                                            'old_price' => $product_prices[$key],
                                            'created_by' => $created_by
                                        );

                                        ListingItemLink::create($itemLink);
                                    }
                                    /*echo '<pre>';
                                    print_r($modelDetail);
                                    echo '</pre>';die;*/
                                }
                            }
                        }
                        $summary_products[$key]['models'] = implode(',',$modelNames);

                    }

                    $view = view('listing.listing_summary', compact('summary_products'))->render();

                    return response()->json([
                        'success' => true,
                        'summary' => $view,
                        'message' => 'Product listing created successfully!'
                    ]);
                }

                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong! Please try again!'
                ]);


            }
        }

        return response()->json(['error' => true,'message' => 'Something went wrong! Please try again!']);
    }

}
