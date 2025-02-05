<?php

namespace App\Http\Controllers;

use App\Jobs\FeedGenerationJob;
use App\Jobs\ListingItemImageCreate;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DeviceSeries;
use App\Models\DeviceTypes;
use App\Models\GoodUpc;
use App\Models\Interfaces;
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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use App\Helpers\QueryFilters;

class ListingController extends Controller
{
    private $perPage = 20;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the data in the pagination format for datatable to display
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $device_types = DeviceTypes::Where('status', 1)->get();
        $product_types = ProductType::with('children')->where('parent_id', NULL)->get();
        $manufacturers = Manufacturer::all();
        $devices = Device::all();
        $brands = Brand::all();

        return view('listing.list', compact('stores', 'device_types', 'product_types', 'manufacturers', 'devices', 'brands'))->with('perPage', $this->perPage);
    }

    /**
     * Filter listing.
     */
    public function filter(Request $request)
    {

        //DB::enableQueryLog();
        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = ListingItem::with([
            'storeProduct:id,store_id,product_id',
            'storeProduct.store.storeType:id,name',
            'storeProduct.store:id,name,store_type_id',
            'storeProduct.product:product_id,product_status,product_type,product_code,product_sku,dimensions_width,product_color',
            'device.deviceType:id,name',
            'generationType:id,name'
        ]);

        if ($request->has('store_id') && $request->store_id != '') {
            $query->whereRelation('storeProduct', 'store_id', $request->store_id);
            /*$query->whereHas('storeProduct', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });*/
        }

        if ($request->has('product_status') && $request->product_status != '') {
            $query->whereRelation('storeProduct.product', 'product_status', $request->product_status);
            /*$query->whereHas('storeProduct.product', function ($q) use ($request) {
                $q->where('product_status', $request->product_status);
            });*/
        }

        // Filter by product type
        if ($request->has('product_type') && $request->product_type != '') {
            $query->whereRelation('storeProduct.product', 'product_type', $request->product_type);
            /*$query->whereHas('storeProduct.product', function ($q) use ($request) {
                $q->where('product_type', $request->product_type);
            });*/
        }

        // Filter by device type
        if ($request->has('device_type') && $request->device_type != '') {
            $query->whereRelation('device.deviceType', 'id', $request->device_type);
            /* $query->whereHas('device.deviceType', function ($q) use ($request) {
                 $q->where('id', $request->device_type);
             });*/

        }

        // Filter by product code
        QueryFilters::applyRelationFilter($query, $request, 'storeProduct.product', 'product_sku', 'product_code_operator', 'product_code');

        // Filter by store
        QueryFilters::applyRelationFilter($query, $request, 'storeProduct.store', 'name', 'store_operator', 'store');

        // Filter by sku
        QueryFilters::applyFilter($query, $request, 'sku', 'sku_operator', 'sku');

        // Filter by title
        QueryFilters::applyFilter($query, $request, 'title', 'title_operator', 'title');

        // Filter by price
        QueryFilters::applyFilter($query, $request, 'price', 'price_operator', 'price');

        // Filter by ID
        QueryFilters::applyFilter($query, $request, 'id', 'id_operator', 'id');

        // Filter by sales count
        QueryFilters::applyFilter($query, $request, 'sales_count', 'sales_count_operator', 'sales_count');

        // Filter by manufacturer
        QueryFilters::applyFilter($query, $request, 'manufacturer_id', 'manufacturer_operator', 'manufacturer');

        // Filter by sync message
        QueryFilters::applyFilter($query, $request, 'sync_message', 'sync_message_operator', 'sync_message');

        // Filter by upc
        QueryFilters::applyFilter($query, $request, 'upc', 'upc_operator', 'upc');

        // Filter by device
        if ($request->has('device') && $request->device != '') {
            $query->where('device_id', $request->device);
        }

        // Filter by brand
        if ($request->has('device_brand') && $request->device_brand != '') {
            $query->where('brand_id', $request->device_brand);
        }

        // Filter by qty
        if ($request->has('quantity') && $request->quantity != '') {
            $query->where('quantity', $request->quantity);
        }

        // Filter by created date
        if ($request->has('item_created') && $request->item_created != '') {
            $query->where('created_at', $request->item_created);
        }

        // Filter by last sale date
        if ($request->has('last_sale_date') && $request->last_sale_date != '') {
            $query->where('last_sale_date', $request->last_sale_date);
        }

        // Filter by product status
        if ($request->has('listing_status') && $request->listing_status != '') {
            $query->where('status', $request->listing_status);
        }

//        $query->groupBy('id','title','sku','store_sku','price','sales_count','last_sale_date','products.product_sku', 'products.dimensions_width', 'products.product_color', 'store.name', 'store.marketplace_name');

        if ($columnName != '') {
            switch (strtolower($columnName)) {
                case 'product_sku':

                    $query->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
                        ->join('products', 'products.product_id', '=', 'store_product.product_id')
                        ->orderBy('products.product_sku', $columnSortOrder);

                    /* $query->orderByRaw('(SELECT product_sku FROM products LEFT JOIN store_product ON(store_product.product_id = products.product_id) WHERE store_product.id = listing_item.store_product_id) '.$columnSortOrder);*/
                    break;
                case 'product_size':

                    $query->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
                        ->join('products', 'products.product_id', '=', 'store_product.product_id')
                        ->orderBy('products.dimensions_width', $columnSortOrder);

                    /*$query->orderByRaw('(SELECT dimensions_width FROM products LEFT JOIN store_product ON(store_product.product_id = products.product_id) WHERE store_product.id = listing_item.store_product_id) '.$columnSortOrder);*/
                    break;
                case 'product_color':
                    $query->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
                        ->join('products', 'products.product_id', '=', 'store_product.product_id')
                        ->orderBy('products.product_color', $columnSortOrder);

                    /*$query->orderByRaw('(SELECT product_color FROM products LEFT JOIN store_product ON(store_product.product_id = products.product_id) WHERE store_product.id = listing_item.store_product_id) '.$columnSortOrder);*/
                    break;
                case 'store_name':
                    $query->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
                        ->join('store', 'store_product.store_id', '=', 'store.id')
                        ->orderBy('store.name', $columnSortOrder);

                    /*$query->orderByRaw('(SELECT name FROM store LEFT JOIN store_product ON(store_product.store_id = store.id) LEFT JOIN listing_item ON(listing_item.store_product_id = store_product.store_id) WHERE store_product.id = listing_item.store_product_id) '.$columnSortOrder);*/
                    break;
                case 'generation_type_name':
                    $query->join('listing_generation_type', 'listing_generation_type.code', '=', 'listing_item.generation_type_id')
                        ->orderBy('listing_generation_type.name', $columnSortOrder);
                    /*$query->orderByRaw('(SELECT name FROM listing_generation_type WHERE listing_item.generation_type_id = listing_generation_type.code) '.$columnSortOrder);*/
                    break;
            }

            $sortableColumns = [
                'sku' => 'sku',
                'sale_count' => 'sale_count',
                'last_sale_date' => 'last_sale_date',
                'store_sku' => 'store_sku',
                'store_name' => 'store.name',
                'title' => 'title',
                'status' => 'status',
                'sync_status' => 'sync_status',
                'price' => 'price',
                'qty' => 'quantity',
                'created_at' => 'created_at',
            ];
            if (isset($sortableColumns[$columnName])) {
                $query->orderBy($sortableColumns[$columnName], $columnSortOrder);
            }
        }

        //$productsRecord = $query->paginate($perPage); // Paginate results
        //echo $query->toSql();die;
        //$products = $query->skip($start)->take($perPage)->get(); // Paginate results
        //dd(DB::getQueryLog());
        /*echo '<pre>';
        print_r($products);
        echo '</pre>';die;*/
        /*$totalRecordwithFilter = $productsRecord->total();*/
        $totalRecords = $query->count();
        $products = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$products)) {
            foreach ($products as $product) {

                if (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) {
                    $productcode = $product->storeProduct->product->product_sku;
                } else {
                    $productcode = '';
                };
                $data[] = array(
                    "sku" => $product->sku . '<input type="checkbox" class="proList" name="listing_item[]" data-code="' . $productcode . '" value="' . $product->id . '" style="visibility:hidden;">',
                    "sale_count" => ($product->sale_count) ? $product->sale_count : 0,
                    "last_sale_date" => $product->last_sale_date,
                    "store_sku" => $product->store_sku,
                    "product_sku" => (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) ? $product->storeProduct->product->product_sku : '',
                    "store_name" => ((!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->store)) ? $product->storeProduct->store->name : '') . ((!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->store) && !empty((array)$product->storeProduct->store->storeType)) ? ' - ' . $product->storeProduct->store->storeType->name : ''),
                    "title" => strlen($product->title) > 50 ? substr($product->title, 0, 50) . "..." : $product->title,
                    "status" => ($product->status == 1) ? 'Active' : 'Inactive',
                    "product_size" => (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) ? $product->storeProduct->product->dimensions_width : '',
                    "product_color" => (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) ? $product->storeProduct->product->product_color : '',
                    "sync_status" => '',
                    "generation_type_name" => (!empty($product->generationType)) ? $product->generationType->name : '',
                    "price" => $product->price,
                    "qty" => $product->quantity,
                    "created_at" => date('Y-m-d H:i:s', strtotime($product->created_at)),
                    /*"action" => '<a href="'.route('listing.edit', $product->id).'" class="btn btn-primary">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                                </a>',*/
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
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $pageAt = 'Add';
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        /*echo '<pre>';
        print_r($stores);die;*/
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::whereNull('parent_id')->get();
        $deviceTypes = DeviceTypes::all();
        $listingGenerationTypes = ListingGenerationType::orderBy('code', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $deviceRating = DeviceRating::orderBy('value', 'asc')->get();
        session()->forget('listing_product_details');
        return view('listing.create', compact('pageAt', 'stores', 'manufacturers', 'productTypes', 'deviceTypes', 'brands', 'listingGenerationTypes', 'deviceRating'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageAt = 'Edit';
        $stores = Store::all();
        $manufacturers = Manufacturer::all();
        $productDetail = ListingItem::select('listing_item.*', 'products.product_sku', 'products.product_description', 'products.manufacturer_id', 'products.dimensions_width', 'products.product_color', 'store.name as store_name', 'store.marketplace_name', 'listing_generation_type.name as generation_name', 'store_type.product_qty')
            //->leftJoin('listing_item_link','listing_item.id', '=', 'listing_item_link.item_id')
            ->leftJoin('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
            ->leftJoin('products', 'products.product_id', '=', 'store_product.product_id')
            ->leftJoin('store', 'store.id', '=', 'store_product.store_id')
            ->leftJoin('store_type', 'store.store_type_id', '=', 'store_type.id')
            ->leftJoin('listing_generation_type', 'listing_generation_type.code', '=', 'listing_item.generation_type_id')
            ->where('listing_item.id', $id)
            ->first();
        $store_type_listing_id = $productDetail->store_type_listing_id;
        $storeTypeDetail = [];
        if ($store_type_listing_id != '') {
            $storeTypeDetail = StoreTypeListing::findOrFail($store_type_listing_id);
        }
        $productVariants = ListingItemDetail::select('listing_item_detail.*', 'brands.name as brand_name', 'device.name as device_name')
            ->leftJoin('device', 'device.id', '=', 'listing_item_detail.device_id')
            ->leftJoin('brands', 'brands.id', '=', 'device.brand_id')
            ->where('listing_item_detail.listing_item_id', $productDetail->id)
            ->get();

        /*$productDetail = StoreTypeListing::select('store_type_listing.*','products.product_sku','products.product_description','products.manufacturer_id','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name')
            ->leftJoin('products','products.product_id', '=', 'store_type_listing.item_id')
            ->leftJoin('store','store.id', '=', 'store_type_listing.store_id')
            ->where('listing_item.id',$id)
            ->first();*/

        return view('listing.update', compact('manufacturers', 'pageAt', 'stores', 'productDetail', 'storeTypeDetail', 'productVariants'));
    }

    public function getProducts(Request $request)
    {

        $query = StoreProduct::select('store_product.id', 'store_product.product_id', 'store_product.title', 'store_product.price', 'store_product.created_at', 'product_type.name as ProductType')->with('product');
        $query->leftJoin('products', 'products.product_id', '=', 'store_product.product_id');
        $query->leftJoin('product_type', 'products.product_type', '=', 'product_type.id');


        // Handle Only Active Products (Remove the bundled products)
        if ($request->has('exclude_oem')) {
            $query->where('products.oem_generic');
        } else {
            $query->where('products.oem_generic', 2);
        }

        // getting only product which are not listed
        if ($request->has('never_listed_product')) {
            $query->leftJoin('listing_item', 'listing_item.store_product_id', '=', 'store_product.id');
            $query->where('listing_item.id');
        }

        // getting only products which have additional images
        if ($request->has('additional_image_available')) {
            $query->leftJoin('product_image_alternatives', 'product_image_alternatives.product_id', '=', 'store_product.product_id');
            $query->whereNotNull('product_image_alternatives.id');
        }

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

        // Filter by interface
        if ($request->has('interface') && $request->interface != '') {
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            $query->whereIn('product_interface.interface_id', $request->interface);
        }

        if ($request->has('device') && $request->device != '') {
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            $query->whereIn('device_interface.device_id', $request->device);
        }

        $all_product_ids = [];

        // Filter by product type and sub type
        if ($request->has('product_sub_sub_type') && $request->product_sub_sub_type != '') {
            $all_product_ids = array_merge($all_product_ids, $request->product_sub_sub_type);
        } else if ($request->has('product_sub_type') && $request->product_sub_type != '') {
            $all_product_ids = array_merge($all_product_ids, $request->product_sub_type);
            foreach ($request->product_sub_type as $sub_type) {
                $product_sub_types = ProductType::select('id')->where('parent_id', $sub_type)->get()->toArray();
                if (!empty($product_sub_types)) {
                    foreach ($product_sub_types as $stype) {
                        $all_product_ids[] = $stype['id'];
                    }
                }
            }
        } else if ($request->has('product_type') && $request->product_type != '') {
            $all_product_ids = array_merge($all_product_ids, $request->product_type);
            foreach ($request->product_type as $type) {
                $product_sub_types = ProductType::select('id')->where('parent_id', $type)->get()->toArray();
                if (!empty($product_sub_types)) {
                    foreach ($product_sub_types as $stype) {
                        $all_product_ids[] = $stype['id'];
                        $product_sub_sub_types = ProductType::select('id')->where('parent_id', $stype['id'])->get()->toArray();
                        if (!empty($product_sub_sub_types)) {
                            foreach ($product_sub_sub_types as $ssubtype) {
                                $all_product_ids[] = $ssubtype['id'];
                            }
                        }
                    }
                }
            }
        }
        if (!empty($all_product_ids)) {
            $query->whereIn('products.product_type', $all_product_ids);
        }

        $products = $query->distinct()->get();
        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('listing.search_product_list', compact('products'))->render(),
                'totalRecords' => $products->count()
            ]);
        }
    }

    public function search(Request $request)
    {

        $term = $request->input('term');
        $type = $request->input('type');
        //echo $term;die;
        $data = [];
        switch ($type) {

            case 'product_code':
                $product_code = $request->input('product_code');
                if (!empty($product_code)) {
                    $product = Catalog::select('product_id', 'product_sku')->WhereLike('product_sku', '%' . $term . '%')->whereNotIn('product_sku', $product_code)->get();
                } else {
                    $product = Catalog::select('product_id', 'product_sku')->WhereLike('product_sku', '%' . $term . '%')->get();
                }
                foreach ($product as $key => $value) {
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
                $device_series = $request->input('device_series');
                if (!empty($device_series)) {
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id', $device_series)->get();
                } else {
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->get();
                }
                foreach ($deviceSeries as $key => $value) {
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
                $device = $request->input('device');
                if (!empty($device_series)) {
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id', $device)->get();
                } else {
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->get();
                }
                foreach ($devices as $key => $value) {
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
                $template_type = $request->input('template_type');
                $templates = ListingGenerationTypeTemplate::where('generation_type_id', $template_type)->get();
                foreach ($templates as $key => $value) {
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
    public function getProductSubType(Request $request)
    {

        $product_types = $request->input('product_types');
        if (!empty($product_types)) {
            $option = '';
            $product_sub_types = ProductType::select('id', 'name')->WhereIn('parent_id', $product_types)->orderBy('name', 'asc')->get()->toArray();
            if (!empty($product_sub_types)) {
                foreach ($product_sub_types as $stype) {
                    $option .= '<option value="' . $stype['id'] . '">' . $stype['name'] . '</option>';
                }
            }

            $ioption = '';
            $interface = Interfaces::select('id', 'name')->WhereIn('product_type_id', $product_types)->orderBy('name', 'asc')->get()->toArray();
            if (!empty($interface)) {
                foreach ($interface as $iface) {
                    $ioption .= '<option value="' . $iface['id'] . '">' . $iface['name'] . '</option>';
                }
            }

            return response()->json([
                'success' => true,
                'option' => $option,
                'ioption' => $ioption
            ]);

        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    /**
     * Get Selected Products.
     */
    public function getSelectedProducts(Request $request)
    {

        $product_ids = $request->input('product_ids');
        $product_details = $request->input('product_detail');
        $store_id = $request->input('store_id');
        session(['listing_product_details' => $product_details]);
        $step = $request->input('step');
        $view = '';
        if (!empty($product_ids)) {
            $query = StoreProduct::select('store_product.id', 'store_product.store_type_category_id', 'store_product.product_id', 'store_product.title', 'store_product.price', 'store_product.created_at', 'product_type.name as ProductType')->with('Product');
            $query->leftJoin('products', 'products.product_id', '=', 'store_product.product_id');
            $query->leftJoin('product_type', 'products.product_type', '=', 'product_type.id');
            $query->whereIn('store_product.id', $product_ids);
            $products = $query->get();
            $storeCategories = [];
            if ($store_id) {
                $storeCategories = StoreTypeCategory::select('store_type_category.*')
                    ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                    ->leftJoin('store', 'store_type.id', '=', 'store.store_type_id')
                    ->where('store.id', $store_id)
                    ->get();
            }

            if ($step == 2) {
                $view = view('listing.selected_product_list', compact('products', 'storeCategories'))->render();
            } else if ($step == 3) {
                $view = view('listing.select_device_product_list', compact('products', 'storeCategories'))->render();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view
                ]);
            }

        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function getKeywords(Request $request)
    {
        $product_ids = $request->input('selected_product');
        $keyword_seperator = $request->input('keyword_seperator');
        $long_key_length = $request->input('long_key_length');
        $short_key_length = $request->input('short_key_length');
        $append_front = $request->input('append_front');

        if (!empty($product_ids)) {

            $keywords = ProductTitleKeyword::select('product_id', 'keyword')->whereIn('product_id', $product_ids)->orderBy('priority', 'asc')->get()->toArray();

            /*echo '<pre>';
            print_r($keywords);
            echo '</pre>';die;*/
            $short_keyword = [];
            $long_keyword = [];
            if (!empty($keywords)) {
                $sKeyword = $lKeyword = $pro_ids = $shortFlag = $longFlag = [];
                $sflag = $lflag = 0;
                foreach ($keywords as $key => $val) {
                    $pro_id = $val['product_id'];
                    if (!isset($shortFlag[$pro_id])) {
                        $shortFlag[$pro_id] = 0;
                    }
                    if (!isset($longFlag[$pro_id])) {
                        $longFlag[$pro_id] = $short_key_length;
                    }
                    if ((!array_key_exists($pro_id, $shortFlag) || $shortFlag[$pro_id] < $short_key_length) || !array_key_exists($pro_id, $short_keyword)) {
                        $short_keyword[$pro_id][] = trim($val['keyword']);
                        $shortFlag[$pro_id] = $shortFlag[$pro_id] + 1;
                    }
                    //echo $key .'>'. $short_key_length.'<br>';
                    if ($key >= $short_key_length) {
                        if (!is_numeric($long_key_length)) {
                            $long_key_length = 7;
                        }
                        if (((!array_key_exists($pro_id, $longFlag) || $longFlag[$pro_id] < ($long_key_length + $short_key_length))) || !array_key_exists($pro_id, $long_keyword)) {
                            $long_keyword[$pro_id][] = trim($val['keyword']);
                            $longFlag[$pro_id] = $longFlag[$pro_id] + 1;
                        }
                    }
                    if (!in_array($pro_id, $pro_ids)) {
                        $pro_ids[] = $pro_id;
                    }
                }//die;

                if (!empty($pro_ids)) {
                    foreach ($pro_ids as $id) {
                        if (!empty($short_keyword)) {
                            $sKeyword[$id] = implode(' ', $short_keyword[$id]);
                        }

                        if (!empty($short_keyword)) {
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

    public function getTitleKeywords(Request $request)
    {
        $product_ids = $request->input('selected_product');
        $title_length = $request->input('title_length');

        if (!empty($product_ids)) {

            $keywords = ProductTitleKeyword::select('product_id', 'keyword')->whereIn('product_id', $product_ids)->orderBy('priority', 'asc')->get()->toArray();
            if (!empty($keywords)) {
                $title_keyword = $tKeyword = $pro_ids = $Flag = [];

                foreach ($keywords as $key => $val) {
                    $pro_id = $val['product_id'];
                    if (!isset($shortFlag[$pro_id])) {
                        $shortFlag[$pro_id] = 0;
                    }
                    if (!isset($Flag[$pro_id])) {
                        $Flag[$pro_id] = 0;
                    }
                    if (((!array_key_exists($pro_id, $Flag) || $Flag[$pro_id] < $title_length)) || !array_key_exists($pro_id, $title_keyword)) {
                        $Flag[$pro_id] = $Flag[$pro_id] + 1;
                    }

                    if (!in_array($pro_id, $pro_ids)) {
                        $pro_ids[] = $pro_id;
                    }
                }
                if (!empty($pro_ids)) {
                    foreach ($pro_ids as $id) {
                        if (!empty($title_keyword)) {
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
    public function getCompatibleDevice(Request $request)
    {

        $product_id = $request->input('product_id');
        $device_type = $request->input('device_type');
        $brand = $request->input('brand');
        $devices = $request->input('devices');
        $rating = $request->input('rating');
        $popularity = $request->input('popularity');
        $is_selected = $request->input('is_selected');
        $selected_device = $request->input('selected_device');
        if ($product_id) {
            $query = Device::select('device.*', 'product_interface.product_id','products.product_sku')->with('brand', 'rating')->with('deviceType');
            $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');
            $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
            $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
            $query->leftJoin('products', 'product_interface.product_id', '=', 'products.product_id');

            $query->whereIn('product_interface.product_id', $product_id);
            if ($device_type) {
                $query->whereIn('device.device_type_id', $device_type);
            }
            if ($brand) {
                $query->whereIn('device.brand_id', $brand);
            }
            if ($rating) {
                $query->whereIn('device.rating_id', $rating);
            }
            if ($popularity) {
                $query->whereIn('device.rating_id', $popularity);
            }
            if ($devices) {
                $query->whereIn('device.id', $devices);
            }
            $query->orderBy('brands.name', 'asc');
            $query->orderBy('device.name', 'asc');


            $devices = $query->get();
            //echo $query->toSql();die;
            $count = $devices->count();


            $view = view('listing.target_product_listing', compact('devices', 'selected_device', 'is_selected'))->render();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view,
                    'total' => $count
                ]);
            }

        } else {
            return response()->json([
                'error' => true
            ]);
        }
    }

    public function getTitleTemplate(Request $request)
    {

        $generate_type = $request->input('type');
        if ($generate_type) {

            $templates = ListingGenerationTypeTemplate::where('generation_type_id', $generate_type)->get();
            $data = [];
            $option = '';
            if (!empty($templates)) {
                foreach ($templates as $key => $template) {
                    if ($template->default_template == 1) {
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

    public function generateProductListing(Request $request)
    {

        try {
            $product_ids = $request->input('product_id');
            $device_type = $request->input('device_type');
            $brand = $request->input('brand');
            $device = $request->input('devices');
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
            $product_details = (array)json_decode($product_details);
            /*echo '<pre>';
            print_r($product_ids);
            print_r($product_details);
            echo '</pre>';*/

            if ($store_id && $product_ids) {
                foreach ($product_ids as $index => $product_id) {

                    $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
                    $max_title_length = $store_type_detail->max_title_length;
                    //$products = StoreProduct::getStoreProducts($product_ids);
                    $products = StoreProduct::with('product', 'product.productType')->where('store_id', $store_id)->whereIn('product_id', $product_ids)->get();
                    //echo $query2->toSql();die;

                    /*echo '<pre>';
                    print_r($product_details);die;*/
                    if (count($products ?? []) > 0) {
                        $productsArray = [];
                        foreach ($products as $key => $product) {
                            $store_product_id = trim($product->id);
                            if (array_key_exists($store_product_id, $product_details)) {
                                $pDetail = $product_details[$store_product_id];
                                /*echo '<pre>';
                                print_r($product_details[$store_product_id]);
                                echo '</pre>';*/
                                $product_short_keyword = $pDetail->short_keyword;
                                $product_long_keyword = $pDetail->long_keyword;
                                $product_title = $pDetail->title;
                                $product_price = $pDetail->price;
                                $product_price_overwrite = $pDetail->price_overwrite;
                                $title_template = $title_templates[$key];
                                if ($title_template) {
                                    if (str_contains($title_template, '#short_keyword')) {
                                        $title_template = str_replace('#short_keyword', $product_short_keyword, $title_template);
                                    }
                                    if (str_contains($title_template, '#long_keyword')) {

                                        $title_template = str_replace('#long_keyword', $product_long_keyword, $title_template);
                                    }
                                    if (str_contains($title_template, '#unique_code')) {

                                        $title_template = str_replace('#unique_code', '[^]', $title_template);
                                    }
                                    if (str_contains($title_template, '#product')) {

                                        $title_template = str_replace('#product', $product_title, $title_template);
                                    }
                                }

                                $query = Device::select('device.*', 'brands.name as brand_name')->with('deviceType');
                                $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');
                                $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
                                $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
                                $query->where('product_interface.product_id', $product->product_id);
                                if (isset($selected_device[$product->product_id]) && !empty($selected_device[$product->product_id])) {
                                    $query->whereIn('device.id', $selected_device[$product->product_id]);
                                }
                                if ($device_type) {
                                    $query->whereIn('device.device_type_id', $device_type);
                                }
                                if ($brand) {
                                    $query->whereIn('device.brand_id', $brand);
                                }
                                if ($rating) {
                                    $query->whereIn('device.rating_id', $rating);
                                }
                                if ($popularity) {
                                    $query->whereIn('device.rating_id', $popularity);
                                }
                                if ($device) {
                                    $query->whereIn('device.id', $device);
                                }
                                $query->orderBy('brand_name', 'asc');
                                $query->orderBy('device.name', 'asc');
                                $devices = $query->get();

                                /*echo '<pre>';
                                print_r($devices);
                                echo '<pre>';die;*/

                                if ($generate_type_name) {
                                    switch (trim(strtolower($generate_type_name))) {
                                        case 'combined devices':
                                            $productsArray[] = $this->generateProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite);
                                            break;
                                        case 'free group':
                                            $productsArray[] = $this->generateFreeGroupProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite);
                                            break;
                                        case 'single device':
                                            $productsArray[] = $this->generateSingleDeviceProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite);
                                            break;
                                        case 'brand gruop':
                                            $productsArray[] = $this->generateBrandGroupProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite);
                                            break;
                                    }
                                }
                            } else {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'session value missing',
                                ]);
                            }
                        }
                        /*echo '<pre>';
                        print_r($productsArray);
                        echo '</pre>';*/
                    }
                }

                if (!empty($productsArray)) {
                    $view = view('listing.target_listing', compact('productsArray', 'max_title_length'))->render();

                    $models = $modelDetail = array();
                    $totalCount = 0;
                    foreach ($productsArray as $k => $product) {
                        if (!empty($product)) {
                            foreach ($product as $key => $pro) {
                                $totalCount++;
                                if (isset($pro['model'])) {
                                    $proid = $pro['product_id'];
                                    $models[$proid][$key] = $pro['model'];
                                    $modelDetail[$proid][$key] = $pro['model_detail'];
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
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'no title generated',
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'store id and product id missing',
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $device_seperator; // Separator between device names
        $lastBrand = null;
        $currentBrand = null;
        $cnt = 0;
        /*echo '<pre>';
        print_r($devices);*/
        foreach ($devices as $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            $parts = explode(' ', $currentBrand, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? ''; // If no model, treat as empty

            // Use only the model if the brand is the same as the last one
            if ($compress) {
                $nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $device->name;
            }

            //echo $nameToAdd.'<br>';

            // Check if adding the device exceeds the character limit
            $tempModels = array_merge($currentModels, [$nameToAdd]);
            $tempModelString = implode($brand_seperator . ' ', $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator . ' ', $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $tempTitle);

            /*$sku = StoreTypeListing::generateUniqueAlphabeticSku($product->product_sku);*/

            // Check if adding the device exceeds the character limit
            //echo strlen($tempTitle) .'>'. $charLimit.'<br>';
            if (strlen($tempTitle) > $charLimit) {

                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                $titles[$cnt]['title'] = str_replace(['#model_series', '#devices'], [implode($brand_seperator . ' ', $currentModels), implode($separator . ' ', $currentDevices)], $baseTitle);

                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $currentModels = [$currentBrand];
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;

            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                //$currentTitle .= ($currentTitle === $baseTitle ? ' ' : ' '.$separator.' ') . $nameToAdd;
                $currentModels[] = $nameToAdd;
                $currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            /*$titles[$cnt]['title'] = rtrim($currentTitle, ' '.$separator.' ');*/
            //$titles[$cnt]['title'] = str_replace('#model_series', implode($separator.' ', $currentModels), $baseTitle);
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices'], [implode($brand_seperator . ' ', $currentModels), implode($separator . ' ', $currentDevices)], $baseTitle);
        }
        /*echo '<pre>';
        print_r($titles);die;*/
        return $titles;
    }

    private function generateSingleDeviceProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite)
    {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k => $device) {
            $currentBrand = $device->brand_name . ' ' . $device->name;

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#devices', $currentBrand, $tempTitle);
            $tempTitle = str_replace('#model_series', $currentBrand, $tempTitle);
            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product->product_sku;
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

    private function generateFreeGroupProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite)
    {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k => $device) {
            $currentBrand = $device->brand_name . ' ' . $device->name;

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#devices', $currentBrand, $tempTitle);
            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product->product_sku;
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

    private function generateBrandGroupProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $product_price, $store_product_id, $product_price_overwrite)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $brand_seperator; // Separator between device names
        $lastBrand = null;
        $currentBrand = null;
        $cnt = 0;
        foreach ($devices as $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            $parts = explode(' ', $currentBrand, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? ''; // If no model, treat as empty

            // Use only the model if the brand is the same as the last one
            if ($compress) {
                $nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $device->name;
            }

            // Check if adding the device exceeds the character limit
            $tempModels = array_merge($currentModels, [$nameToAdd]);
            $tempModelString = implode($brand_seperator . ' ', $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator . ' ', $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $tempTitle);

            // Check if adding the device exceeds the character limit
            if ($brand !== $lastBrand || strlen($tempTitle) > $charLimit) {
                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                if (!empty($currentDevices)) {
                    $titles[$cnt]['title'] = str_replace(['#model_series', '#devices'], [implode($separator . ' ', $currentModels), implode($device_seperator . ' ', $currentDevices)], $baseTitle);
                }
                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $currentModels = [$currentBrand];
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $product_price;
                $titles[$cnt]['price_overwrite'] = $product_price_overwrite;
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                $currentModels[] = $nameToAdd;
                $currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices'], [implode($separator . ' ', $currentModels), implode($device_seperator . ' ', $currentDevices)], $baseTitle);
        }

        return $titles;
    }

    public function saveListingProducts(Request $request)
    {

        Log::info('Entering saveListingProducts ');
        if ($request->input()) {
            try {
                DB::beginTransaction();
                if (is_array($request->input('final_product_title'))) {
                    //$generate_type = $request->input('generate_type');
                    $generation_type_id = $request->input('generation_type_id');
                    $product_titles = $request->input('final_product_title');
                    $product_prices = $request->input('final_product_price');
                    $product_price_overwrites = $request->input('final_product_price_overwrite');
                    $product_codes = $request->input('final_product_code');
                    $store_product_ids = $request->input('final_store_product_id');
                    $product_ids = $request->input('final_product_id');
                    $store_id = $request->input('store_id');
                    $store_detail = Store::where('id', $store_id)->first();
                    $product_models = $request->input('product_models');
                    if ($product_models) {
                        $product_models = json_decode($product_models);
                    }
                    /*echo '<pre>';
                    print_r($product_models);
                    echo '<pre>';die;*/
                    $created_by = auth()->user()->name ?? 'admin';

                    $listingArr = [
                        'store_id' => $store_id,
                        'generation_type_id' => $generation_type_id,
                        'feed_type' => 1,
                        'status' => 0,
                        'chained' => 1,
                        'include_carriers' => 0,
                        'brand_combo' => 0,
                        'carrier_combo' => 0,
                        'created_by' => $created_by,
                        'updated_by' => $created_by
                    ];

                    Log::info(' Listing :: ' . json_encode($listingArr));
                    $lisrecord = Listing::create($listingArr);
                    $listing_id = $lisrecord->id;
                    Log::info(' Listing ID :: ' . $listing_id);

                    $summary_products = [];
                    if ($listing_id && !empty($product_titles)) {
                        foreach ($product_titles as $key => $product) {
                            if ($store_detail->sku_generation_type) {
                                switch ($store_detail->sku_generation_type) {
                                    case 1:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                        break;
                                    case 2:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku('', 6);
                                        break;
                                    case 3:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                        break;
                                    case 4:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                        break;
                                    case 'default':
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                        break;
                                }
                            } else {
                                $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                            }
                            if ($store_detail->upc_usage_type) {
                                switch ($store_detail->upc_usage_type) {
                                    case 0:
                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                                        break;
                                    case 1:
                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                                        break;
                                    case 2:
                                        $upcCodes = GoodUpc::where('is_used', 0)->orderBy('upc', 'asc')->first();
                                        break;
                                    case 3:
                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                                        break;
                                    case 'default':
                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                                        break;
                                }
                            } else {
                                $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                            }

                            $productDetail = StoreProduct::with('product', 'product.productType')->where('id', $store_product_ids[$key])->first();
                            $productDetail = $productDetail->toArray();
                            $product_name = $product;
                            if (str_contains($product, '[^]')) {
                                $product_name = str_replace('[^]', $sku, $product);
                            }
                            $summary_products[$key]['title'] = $product_name;
                            $summary_products[$key]['sku'] = $sku;
                            $summary_products[$key]['product_type'] = $productDetail['product']['product_type']['name'];
                            $summary_products[$key]['product_code'] = $productDetail['product']['product_sku'];
                            $listingProductArr = [
                                'product_name' => $product_name,
                                'description' => ($productDetail['description'] != '') ? $productDetail['description'] : $productDetail['product']['product_description'] ,
                                'product_category' => $productDetail['product']['product_type']['name'],
                                'sku' => $sku,
                                'price' => $product_prices[$key],
                                'item_id' => $product_ids[$key],
                                'upc' => $upcCodes->upc_code,
                                'store_id' => $store_id,
                                'has_offers' => 0,
                                'status' => 1,
                                'created_by' => $created_by,
                                'updated_by' => $created_by
                            ];
                            Log::info(' Store Type Listing :: ' . json_encode($listingProductArr));
                            $record = StoreTypeListing::create($listingProductArr);
                            $store_type_listing_id = $record->id;
                            Log::info(' Store Type Listing ID :: ' . $store_type_listing_id);

                            if ($store_type_listing_id) {

                                $modelDetail = array(
                                    'title' => $product_name,
                                    'description' => ($productDetail['description'] != '') ? $productDetail['description'] : $productDetail['product']['product_description'],
                                    'store_type_listing_id' => $store_type_listing_id,
                                    'sku' => $sku,
                                    'store_sku' => '',
                                    'price' => $product_prices[$key],
                                    'quantity' => ($store_detail->default_listing_quantity) ? $store_detail->default_listing_quantity : 10,
                                    'sync_status' => 1,
                                    'store_product_id' => $store_product_ids[$key],
                                    'manufacturer_id' => ($productDetail['manufacturer_id'] != '') ? $productDetail['manufacturer_id'] : $productDetail['product']['manufacturer_id'],
                                    'generation_type_id' => $generation_type_id,
                                    'overwrite_title' => 1,
                                    'overwrite_price' => $product_price_overwrites[$key],
                                    'status' => 1,
                                    'upc' => $upcCodes->upc_code,
                                    'created_by' => $created_by
                                );

                                Log::info(' Listing Item :: ' . json_encode($modelDetail));
                                $item = ListingItem::create($modelDetail);
                                if ($item) {
                                    $record = UPCCodes::find($upcCodes->id);
                                    $record->update(['is_used' => 1]);
                                }
                                $listing_item_id = $item->id;
                                $modelNames = array();
                                if ($listing_item_id) {
                                   $product_key = $product_ids[$key];
                                    Log::info(' Product Models :: ' . json_encode($product_models->$product_key));
                                    $product_model = $product_models->$product_key[$key];
                                    if (isset($product_model) && is_array($product_model)) {

                                        if (count($product_model ?? []) > 0) {
                                            $limit = count($product_model);
                                            if ($store_detail->upc_usage_type) {
                                                switch ($store_detail->upc_usage_type) {
                                                    case 0:
                                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get();
                                                        break;
                                                    case 1:
                                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get();
                                                        break;
                                                    case 2:
                                                        $upcCodes = GoodUpc::where('is_used', 0)->orderBy('upc', 'asc')->limit($limit)->get();
                                                        break;
                                                    case 3:
                                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get();
                                                        break;
                                                    case 'default':
                                                        $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get();
                                                        break;
                                                }
                                            } else {
                                                $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get();
                                            }
                                        }

                                        foreach ($product_model as $k => $model) {

                                            if ($store_detail->sku_generation_type) {
                                                switch ($store_detail->sku_generation_type) {
                                                    case 1:
                                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                                        break;
                                                    case 2:
                                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku('', 6);
                                                        break;
                                                    case 3:
                                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                                        break;
                                                    case 4:
                                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                                        break;
                                                    case 'default':
                                                        $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                                        break;
                                                }
                                            } else {
                                                $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6);
                                            }

                                            if (str_contains($product, '[^]')) {
                                                $product = str_replace('[^]', $nsku, $product);
                                            }
                                            $modelNames[] = $model->name;
                                            $itemDetail = array(
                                                'sku' => $nsku,
                                                'title' => $product,
                                                'upc' => isset($upcCodes[$k]['upc_code']) ? $upcCodes[$k]['upc_code'] : '',
                                                'device_id' => $model->id,
                                                'listing_item_id' => $listing_item_id,
                                                //'store_sku' => $store_id,
                                                'created_by' => $created_by
                                            );

                                            Log::info(' Listing Item Detail :: ' . json_encode($itemDetail));
                                            $rec = ListingItemDetail::create($itemDetail);
                                            Log::info(' Listing Item Detail ID :: ' . $rec->id);

                                            if ($rec) {
                                                $record = UPCCodes::find($upcCodes[$k]['id']);
                                                $record->update(['is_used' => 1]);
                                            }
                                        }

                                        $itemLink = array(
                                            'item_id' => $listing_item_id,
                                            'listing_id' => $listing_id,
                                            'sync_status' => 6,
                                            'old_price' => $product_prices[$key],
                                            'created_by' => $created_by
                                        );

                                        Log::info(' Listing Item Link :: ' . json_encode($itemLink));
                                        ListingItemLink::create($itemLink);
                                        Log::info(' Listing Item Link created successfully!');
                                    }
                                }
                            }
                            $summary_products[$key]['models'] = implode(',', $modelNames);
                        }
                        DB::commit();

                        $view = view('listing.listing_summary', compact('summary_products'))->render();

                        Bus::chain([
                            new ListingItemImageCreate($listing_id),
                            new FeedGenerationJob($listing_id),
                        ])->dispatch();

                        return response()->json([
                            'success' => true,
                            'summary' => $view,
                            'message' => 'Product listing created successfully!'
                        ]);
                    }

                    DB::rollBack();
                    return response()->json([
                        'error' => true,
                        'message' => 'Something went wrong! Please try again!'
                    ]);
                }

                DB::rollBack();
                return response()->json([
                    'error' => true,
                    'message' => 'Product title missing!'
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'error' => $e->getMessage(),
                ], 500);
            }
        }


        return response()->json(['error' => true, 'message' => 'Something went wrong! Please try again!']);
    }

    public function syncListingPrice(Request $request)
    {

        $selected_listing = $request->input('selected_list');
        $updated_price = $request->input('price');

        if (!empty($selected_listing)) {
            try {
                $listingDetails = ListingItem::with('storeProduct.store:id')->whereIn('id', $selected_listing)->get();
                /*echo '<pre>';
                print_r($listingDetails);
                echo '</pre>';die;*/
                if (!empty((array)$listingDetails)) {
                    $listing_id = '';
                    foreach ($listingDetails as $key => $list) {
                        $created_by = auth()->user()->name ?? 'admin';
                        if ($key == 0) {
                            $listingArr = [
                                'store_id' => $list->storeProduct->store->id,
                                'generation_type_id' => $list->generation_type_id,
                                'feed_type' => 3,
                                'status' => 0,
                                'chained' => 1,
                                'include_carriers' => 0,
                                'brand_combo' => 0,
                                'carrier_combo' => 0,
                                'created_by' => $created_by,
                                'updated_by' => $created_by
                            ];

                            $listrecord = Listing::create($listingArr);
                            $listing_id = $listrecord->id;
                        }

                        $itemRecord = ListingItem::find($list->id);
                        $clonedRecord = $itemRecord->replicate();
                        $clonedRecord->store_sku = NULL;
                        $clonedRecord->submit_count = 0;
                        $clonedRecord->price = $updated_price;
                        $clonedRecord->created_by = $created_by;
                        $clonedRecord->updated_by = $created_by;
                        $clonedRecord->save();
                        $listing_item_id = $clonedRecord->id;
                        $itemDetails = ListingItemDetail::where('listing_item_id', $list->id)->get();

                        if (!empty((array)$itemDetails)) {
                            foreach ($itemDetails as $item_detail) {
                                $itemDetail = ListingItemDetail::find($item_detail->id);
                                $itemclonedRecord = $itemDetail->replicate();
                                $itemclonedRecord->listing_item_id = $listing_item_id;
                                $itemclonedRecord->created_by = $created_by;
                                $itemclonedRecord->updated_by = $created_by;
                                $itemclonedRecord->save();
                            }
                            $itemLink = array(
                                'item_id' => $listing_item_id,
                                'listing_id' => $listing_id,
                                'sync_status' => 6,
                                'old_price' => $updated_price,
                                'created_by' => $created_by
                            );
                            ListingItemLink::create($itemLink);
                            FeedGenerationJob::dispatch($listing_id);
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Product price sync successfully!'
                    ]);
                }
            } catch (Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong! Please try again!'
        ]);
    }

    public function syncListingInventory(Request $request)
    {

        $selected_listing = $request->input('selected_list');
        $updated_inventory = $request->input('inventory');

        if (!empty($selected_listing)) {
            try {
                $listingDetails = Listing::whereIn('id', $selected_listing)->get();
                if (!empty((array)$listingDetails)) {
                    foreach ($listingDetails as $list) {
                        $created_by = auth()->user()->name ?? 'admin';
                        $listingArr = [
                            'store_id' => $list->store_id,
                            'generation_type_id' => $list->generation_type_id,
                            'feed_type' => 2,
                            'status' => 0,
                            'chained' => 1,
                            'include_carriers' => 0,
                            'brand_combo' => 0,
                            'carrier_combo' => 0,
                            'created_by' => $created_by,
                            'updated_by' => $created_by
                        ];

                        $listrecord = Listing::create($listingArr);
                        $listing_id = $listrecord->id;

                        $listingItemLink = ListingItemLink::where('listing_id', $list->id)->get();
                        if (!empty((array)$listingItemLink)) {
                            foreach ($listingItemLink as $item) {
                                $itemRecord = ListingItem::find($item->id);

                                $clonedRecord = $itemRecord->replicate();
                                $clonedRecord->quantity = $updated_inventory;
                                $clonedRecord->save();
                                $listing_item_id = $clonedRecord->id;

                                $itemDetails = ListingItemDetail::where('listing_item_id', $listing_item_id)->get();

                                if (!empty((array)$itemDetails)) {
                                    foreach ($itemDetails as $item_detail) {
                                        $itemDetail = ListingItemDetail::find($item_detail->id);
                                        $itemclonedRecord = $itemDetail->replicate();
                                        $itemclonedRecord->listing_item_id = $listing_item_id;
                                        $itemclonedRecord->save();
                                    }

                                    $itemLink = array(
                                        'item_id' => $listing_item_id,
                                        'listing_id' => $listing_id,
                                        'sync_status' => 6,
                                        'old_price' => $itemRecord->price,
                                        'created_by' => $created_by
                                    );

                                    ListingItemLink::create($itemLink);
                                    FeedGenerationJob::dispatch($listing_id);

                                }
                            }
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Product inventory sync successfully!'
                    ]);
                }
            } catch (Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong! Please try again!'
        ]);
    }
}
