<?php

namespace App\Http\Controllers;

use App\Constants\FeedTypes;
use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Helpers\QueryFilters;
use App\Jobs\FeedGenerationJob;
use App\Jobs\InsertListingItemDetailJob;
use App\Jobs\ListingItemImageCreate;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DeviceSeries;
use App\Models\DevicesLinkedDevice;
use App\Models\DeviceTypes;
use App\Models\GoodUpc;
use App\Models\Interfaces;
use App\Models\Listing;
use App\Models\ListingGenerationType;
use App\Models\ListingGenerationTypeTemplate;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\ListingItemHistory;
use App\Models\ListingItemLink;
use App\Models\Manufacturer;
use App\Models\OrderItem;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StoreManufactureLink;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use App\Services\Generator\TitleGenerator;
use App\Utils\ListingUtility;
use App\Utils\Spintax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;

class ListingController extends Controller
{
    private $perPage = 20;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /*Bus::chain([
            new FeedGenerationJob('2e63e6ca-36a6-452a-b0bc-d41bd9f08a54'),
        ])->dispatch();*/
        // get the data in the pagination format for datatable to display
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $device_types = DeviceTypes::Where('status', 1)->get();
        $product_types = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->get();
        $manufacturers = Manufacturer::all();
        $devices = Device::all();
        $brands = Brand::all();
        $code = '';
        if(isset($_GET['code']) && $_GET['code'] != ''){
            $code = $_GET['code'];
        }

        return view('listing.list', compact('stores', 'device_types', 'product_types', 'manufacturers', 'devices', 'brands','code'))->with('perPage', $this->perPage);
    }

    public function suspended_list()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $device_types = DeviceTypes::Where('status', 1)->get();
        $product_types = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->get();
        $manufacturers = Manufacturer::all();
        $devices = Device::all();
        $brands = Brand::all();

        return view('listing.suspended_list', compact('stores', 'device_types', 'product_types', 'manufacturers', 'devices', 'brands'))->with('perPage', $this->perPage);
    }

    public function inactive()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $device_types = DeviceTypes::Where('status', 1)->get();
        $product_types = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->get();
        $manufacturers = Manufacturer::all();
        $devices = Device::all();
        $brands = Brand::all();

        return view('listing.inactive', compact('stores', 'device_types', 'product_types', 'manufacturers', 'devices', 'brands'))->with('perPage', $this->perPage);
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

        // add select
        $query = ListingItem::select('listing_item.*','saleCount','lastSaleDate')->with([
            'storeProduct:id,store_id,product_id',
            'storeProduct.store.storeType:id,name',
            'storeProduct.store:id,name,store_type_id',
            'storeProduct.product:product_id,product_status,product_type,product_code,product_sku,dimensions_width,product_color',
            'storeProduct.product.color:id,name',
            'device.deviceType:id,name',
            'generationType:id,name',
            'manufacturer:id,name'
        ]);//->addSelect([
//            'saleCount' => OrderItem::selectRaw('SUM(
//                COALESCE(shipped_quantity, 0)
//              - COALESCE(canceled_quantity, 0)
//              - COALESCE(refund_quantity, 0)
//            )')->whereColumn('order_item.sku', 'listing_item.sku')->limit(1)
//        ])
            /*->withSum(['orderItems as saleCount' => function ($query) {
            $query->whereNotNull('shipped_quantity')->where(function ($q) {
                $q->whereNull('canceled_quantity')
                    ->orWhere('canceled_quantity', 0);
            })->where(function ($q) {
                $q->whereNull('refund_quantity')
                    ->orWhere('refund_quantity', 0);
            });
        }], 'quantity')*/
//        ->addSelect([
//            'lastSaleDate' => OrderItem::selectRaw('MAX(created_at)')
//                ->whereColumn('order_item.sku', 'listing_item.sku')
//                ->whereNotNull('shipped_quantity')
//                ->where(function($q){
//                    $q->whereNull('canceled_quantity')->orWhere('canceled_quantity', 0);
//                })
//                ->where(function($q){
//                    $q->whereNull('refund_quantity')->orWhere('refund_quantity', 0);
//                })
//                ->limit(1)
//        ]);
        /*->withMax(['orderItems as lastSaleDate' => function ($query) {
            $query->whereNotNull('shipped_quantity')->whereNull('canceled_quantity')->orWhere('canceled_quantity', 0)->whereNull('refund_quantity')->orWhere('refund_quantity', 0);
        }], 'created_at');*/

        $query->leftJoin(DB::raw('(
                    SELECT oi.sku,
                           SUM(COALESCE(oi.shipped_quantity, 0) - COALESCE(oi.canceled_quantity, 0) - COALESCE(oi.refund_quantity, 0)) AS saleCount,
                           MAX(CASE
                                    WHEN oi.shipped_quantity IS NOT NULL
                                         AND (oi.canceled_quantity IS NULL OR oi.canceled_quantity = 0)
                                         AND (oi.refund_quantity IS NULL OR oi.refund_quantity = 0)
                                    THEN oi.created_at
                                    ELSE NULL
                               END) AS lastSaleDate
                    FROM order_item oi
                    GROUP BY oi.sku
                ) AS oi'), 'oi.sku', '=', 'listing_item.sku');

        if ($request->has('store_id') && $request->store_id != '') {

//            $query->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
//                ->where('store_product.store_id', $request->store_id);

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
        QueryFilters::applyFilter($query, $request, 'listing_item.sku', 'sku_operator', 'sku');

        // Filter by title
        QueryFilters::applyFilter($query, $request, 'listing_item.title', 'title_operator', 'title');

        // Filter by price
        QueryFilters::applyFilter($query, $request, 'listing_item.price', 'price_operator', 'price');

        // Filter by ID
        QueryFilters::applyFilter($query, $request, 'listing_item.id', 'id_operator', 'id');

        // Filter by sales count
        QueryFilters::applyFilter($query, $request, 'listing_item.sales_count', 'sales_count_operator', 'sales_count');

        // Filter by manufacturer
        QueryFilters::applyFilter($query, $request, 'listing_item.manufacturer_id', 'manufacturer_operator', 'manufacturer');

        // Filter by sync message
        QueryFilters::applyFilter($query, $request, 'listing_item.sync_message', 'sync_message_operator', 'sync_message');

        // Filter by upc
        QueryFilters::applyFilter($query, $request, 'listing_item.upc', 'upc_operator', 'upc');

        // Filter by upc
        QueryFilters::applyFilter($query, $request, 'listing_item.store_sku', 'store_sku_operator', 'store_sku');

        // Filter by sync_status
        QueryFilters::applyFilter($query, $request, 'listing_item.sync_status', 'sync_status_operator', 'sync_status');


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

        // Filter by created date
        if ($request->has('item_created') && $request->item_created != '') {
            $query->where('listing_item.created_at', $request->item_created);
        }

        // Filter by last sale date
        if ($request->has('last_sale_date') && $request->last_sale_date != '') {
            $query->where('listing_item.last_sale_date', $request->last_sale_date);
        }

        // Filter by product status
        if ($request->has('listing_status') && $request->listing_status != '') {
            $query->where('listing_item.status', $request->listing_status);
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

            //$totalRecords = $query->count();
            $totalRecords = (clone $query)->toBase()->getCountForPagination();
            $sortableColumns = [
                'sku' => 'sku',
                'sales_count' => 'saleCount',
                'last_sale_date' => 'lastSaleDate',
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
            }else{
                $query->orderBy($columnName, $columnSortOrder);
            }
        }

        //$productsRecord = $query->paginate($perPage); // Paginate results
//        echo $query->toSql();die;
        //$products = $query->skip($start)->take($perPage)->get(); // Paginate results

        /*echo '<pre>';
        print_r($products);
        echo '</pre>';die;*/
        /*$totalRecordwithFilter = $productsRecord->total();*/

        /*$products = $query->skip($start)
            ->take($perPage)
            ->get();*/
        $products = $query->paginate($perPage, ['*'], 'page', floor($start/$perPage)+1);
        $totalRecordwithFilter = $totalRecords;
//        echo $query->toSql();die;
        //dd(DB::getQueryLog());

        $data = [];
        if (!empty((array)$products)) {
            foreach ($products as $product) {

                if (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) {
                    $productcode = $product->storeProduct->product->product_sku;
                } else {
                    $productcode = '';
                };

                $data[] = array(
                    "sku" => $product->sku . '<input type="checkbox" class="proList" name="listing_item[]" data-sku="' . $product->sku . '" data-code="' . $productcode . '" value="' . $product->id . '" style="visibility:hidden;">',
                    "sales_count" => (isset($product->saleCount)) ? $product->saleCount : 0,
                    "last_sale_date" => isset($product->lastSaleDate) ? $product->lastSaleDate : '',
                    "store_sku" => $product->store_sku,
                    "product_sku" => (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) ? $product->storeProduct->product->product_sku : '',
                    "store_name" => ((!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->store)) ? $product->storeProduct->store->name : '') . ((!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->store) && !empty((array)$product->storeProduct->store->storeType)) ? ' - ' . $product->storeProduct->store->storeType->name : ''),
                    "title" => strlen($product->title) > 50 ? substr($product->title, 0, 50) . "..." : $product->title,
                    "status" => ListingStatus::getListingStatusName($product->status),
                    "product_size" => (!empty((array)$product->storeProduct) && !empty((array)$product->storeProduct->product)) ? $product->storeProduct->product->dimensions_width : '',
                    "product_color" => (isset($product->storeProduct->product) && !empty((array)$product->storeProduct->product->color) && !empty((array)$product->storeProduct->product->color)) ? $product->storeProduct->product->color->name : '',
                    "sync_status" => ListingSyncStatus::getListingSyncStatusName($product->sync_status),
                    "generation_type_name" => (!empty($product->generationType)) ? $product->generationType->name : '',
                    "price" => $product->price,
                    "qty" => $product->quantity,
                    "upc" => $product->upc,
                    "manufacturer" => (isset($product->manufacturer)) ? $product->manufacturer->name : '',
                    "created_at" => date('Y-m-d H:i:s', strtotime($product->created_at)),
                    "title_url" => ListingUtility::generateTitleUrl($product),
                    "sync_count" => $product->sync_count,
                    "listing_id" => $product->id
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
        $manufacturers = Manufacturer::orderBy('name', 'asc')->get();
        $productTypes = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        $deviceTypes = DeviceTypes::all();
        $listingGenerationTypes = ListingGenerationType::orderBy('code', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $deviceRating = DeviceRating::orderBy('value', 'asc')->get();
        $carriers = Carrier::orderBy('name', 'asc')->get();
        return view('listing.create', compact('pageAt', 'stores', 'manufacturers', 'productTypes', 'deviceTypes', 'brands', 'listingGenerationTypes', 'deviceRating', 'carriers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageAt = 'Edit';
        $stores = Store::all();
        $manufacturers = Manufacturer::all();
        $productDetail = ListingItem::with('storeProduct', 'storeProduct.product', 'store', 'store.storeType', 'generationType')
            //->leftJoin('listing_item_link','listing_item.id', '=', 'listing_item_link.item_id')
            /*->leftJoin('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
            ->leftJoin('products', 'products.product_id', '=', 'store_product.product_id')
            ->leftJoin('store', 'store.id', '=', 'store_product.store_id')
            ->leftJoin('store_type', 'store.store_type_id', '=', 'store_type.id')
            ->leftJoin('listing_generation_type', 'listing_generation_type.code', '=', 'listing_item.generation_type_id')*/
            ->where('id', $id)
            ->first();
        $store_type_listing_id = $productDetail->store_type_listing_id;
        $storeTypeDetail = [];
        if ($store_type_listing_id != '') {
            $storeTypeDetail = StoreTypeListing::findOrFail($store_type_listing_id);
        }
        $productVariants = ListingItemDetail::with('device', 'device.brand', 'carrier')
            //->leftJoin('device', 'device.id', '=', 'listing_item_detail.device_id')
            //->leftJoin('brands', 'brands.id', '=', 'device.brand_id')
            ->where('listing_item_id', $productDetail->id)
            ->orderBy('created_at', 'ASC')
            ->get();

        $feedHistory = ListingItemLink::with('feed')
            ->where('item_id', $productDetail->id)
            ->orderBy('created_at', 'ASC')
            ->get();

        $listingHistory = ListingItemHistory::where('id', $productDetail->id)
            ->orderBy('created_at', 'ASC')
            ->get();

        /*echo '<pre>';
        print_r($productVariants);
        echo '</pre>';die;*/

        /*$productDetail = StoreTypeListing::select('store_type_listing.*','products.product_sku','products.product_description','products.manufacturer_id','products.dimensions_width','products.product_color','store.name as store_name','store.marketplace_name')
            ->leftJoin('products','products.product_id', '=', 'store_type_listing.item_id')
            ->leftJoin('store','store.id', '=', 'store_type_listing.store_id')
            ->where('listing_item.id',$id)
            ->first();*/

        return view('listing.update', compact('manufacturers', 'pageAt', 'stores', 'productDetail', 'storeTypeDetail', 'productVariants', 'feedHistory', 'listingHistory'));
    }

    public function saveListingData(Request $request)
    {

        $listing_item_id = $request->input('listing_item_id');
        $tabId = $request->input('tab');

        if ($tabId == 1) {

            $updateRecords = [
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'store_url' => $request->input('store_url'),
                'store_sku' => $request->input('store_sku'),
                'price' => $request->input('price'),
                'overwrite_price' => ($request->has('overwrite_price')) ? 1 : 0,
                'overwrite_msrp' => ($request->has('overwrite_msrp')) ? 1 : 0,
                'overwrite_title' => ($request->has('overwrite_title')) ? 1 : 0,
                'overwrite_description' => ($request->has('overwrite_description')) ? 1 : 0,
            ];

            $listingItem = ListingItem::findOrFail($listing_item_id);
            $listingItem->update($updateRecords);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        }
    }

    public function getProducts(Request $request)
    {

//        $query = StoreProduct::select('store_product.id', 'store_product.product_id', 'store_product.title', 'store_product.price', 'store_product.created_at', 'product_type.name as ProductType')->with('product');
//        $query->leftJoin('products', 'products.product_id', '=', 'store_product.product_id');
//        $query->leftJoin('product_type', 'products.product_type', '=', 'product_type.id');

        //DB::enableQueryLog();
        $query = Catalog::with('StoreProduct', 'image', 'productType', 'interfaces.interfaces', 'pinterface.interfaces')->select('products.product_id', 'product_sku', 'product_title', 'product_price', 'product_qty', 'products.created_at', 'product_type', 'product_image_url')->where('products.product_status', 1);


        // Handle Only Active Products (Remove the bundled products)
        if ($request->has('exclude_oem')) {
            $query->where('products.oem_generic', 2);
        }

//        $query->where('products.product_status',1);

        // getting only product which are not listed
        if ($request->has('never_listed_product')) {
            $query->whereNotExists(function ($subquery) use ($request) {
                $subquery->select(DB::raw(1))
                    ->from('listing_item')
                    ->join('store_product', 'listing_item.store_product_id', '=', 'store_product.id')
                    ->whereColumn('store_product.product_id', 'products.product_id')
                    ->whereNull('listing_item.parent_id')
                    ->whereNotIn('listing_item.status', ['2', '3']);

                if ($request->has('store_id') && $request->input('store_id') != '') {
                    $subquery->where('store_product.store_id', $request->input('store_id'));
                }
            });
            /*$query->where('(SELECT count(li) FROM listing_item li left join store_product sp ON(sp.id = li.store_product_id) left join products p ON(p.product_id = sp.product_id) left join store s ON (sp.store_id = s.id) where p.id');*/
        }

        // getting only products which have additional images
        if ($request->has('additional_image_available')) {
            $query->leftJoin('product_image_alternatives', 'product_image_alternatives.product_id', '=', 'store_product.product_id');
            $query->whereNotNull('product_image_alternatives.id');
        }

        if (!$request->has('bundle_product')) {
            $query->whereNotIn('products.product_type', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'ceabundle');
            });
        }

        // Filter by product store
        if ($request->has('store_id') && $request->input('store_id') != '') {
            /*$query->where('store_product.store_id', $request->input('store_id'))->OrWhereNull('store_product.store_id');*/
            /*$query->where('store_product.store_id', $request->input('store_id'));*/
        }
        // Filter by product code
        if ($request->has('product_code') && $request->product_code != '') {
            $query->whereIn('products.product_sku', $request->product_code);
        }
        // Filter by manufacturer
        if ($request->has('manufacturer') && $request->manufacturer != '') {
            $query->whereIn('products.manufacturer_id', $request->manufacturer);
        }

        // Filter by interface
        if ($request->has('interface') && $request->interface != '') {
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            $query->whereIn('product_interface.interface_id', $request->interface);
        }

        if ($request->has('device') && $request->device != '') {
            if(!$request->has('interface') || $request->interface == ''){
                $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            }
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            $query->whereIn('device_interface.device_id', $request->device);
        }

        if ($request->has('device_series') && $request->device_series != '') {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $request->device_series)->get();
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            $query->whereIn('device_interface.device_id', $devices);
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
        } else {
            $product_sub_types = ProductType::select('id', 'code')->whereIn('parent_id', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'cea');
            })->get()->toArray();
            if (!empty($product_sub_types)) {
                foreach ($product_sub_types as $stype) {
                    if ($stype['code'] === 'ceabundle') continue;
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
        if (!empty($all_product_ids)) {
            $query->whereIn('products.product_type', $all_product_ids);
        }
        //$query->limit(10);
        $products = $query->distinct()->get();
        //dd(DB::getQueryLog());
        //echo $query->toSql();die;
        /*echo '<pre>';
        print_r($products);
        echo '</pre>';die;*/

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
        $store_id = $request->input('store_id');
        //echo $term;die;
        $data = [];
        switch ($type) {

            case 'product_code':
                $product_code = $request->input('product_code');
                if (!empty($product_code)) {
                    $query = Catalog::select('products.product_sku');
                    if ($store_id != '') {
                        $query->leftJoin('store_product', 'store_product.product_id', '=', 'products.product_id');
                        $query->where('store_product.store_id', $store_id);
                    }
                    $query->WhereLike('product_sku', '%' . $term . '%');
                    $query->whereNotIn('product_sku', $product_code);
                    $product = $query->get();
                    /*$product = Catalog::select('product_id', 'product_sku')->where->WhereLike('product_sku', '%' . $term . '%')->whereNotIn('product_sku', $product_code)->get();*/
                } else {
                    $query = Catalog::select('products.product_sku');
                    if ($store_id != '') {
                        $query->leftJoin('store_product', 'store_product.product_id', '=', 'products.product_id');
                        $query->where('store_product.store_id', $store_id);
                    }
                    $query->WhereLike('product_sku', '%' . $term . '%');
                    $query->orderBy('product_sku', 'asc');
                    $product = $query->get();
                    /*$product = Catalog::select('product_id', 'product_sku')->WhereLike('product_sku', '%' . $term . '%')->get();*/
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
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id', $device_series)->orderBy('name', 'asc')->get();
                } else {
                    $deviceSeries = DeviceSeries::select('id', 'name')->WhereLike('name', '%' . $term . '%')->orderBy('name', 'asc')->get();
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
                if (!empty($device)) {
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->whereNotIn('id', $device)->orderBy('name', 'asc')->get();
                } else {
                    $devices = Device::select('id', 'name')->WhereLike('name', '%' . $term . '%')->orderBy('name', 'asc')->get();
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
                'success' => false,
                'option' => []
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
        $store = null;
        $step = $request->input('step');
        $view = '';
        if (!empty($product_ids)) {
            /* check product exist in store product or not*/
            $productDetails = Catalog::select('product_id', 'product_type')->whereIn('product_id', $product_ids)->get();
            if ($productDetails) {
                $created_by = auth()->user()->name ?? 'admin';
                foreach ($productDetails as $pid) {
                    $product_exist = StoreProduct::where('product_id', $pid->product_id)->where('store_id', $store_id)->count();
                    if (!$product_exist) {
                        $sPr = [
                            'product_id' => $pid->product_id,
                            'title' => $pid->product_title,
                            'store_type_category_id' => $pid->product_type,
                            'price' => $pid->product_marketplace_price,
                            'msrp' => $pid->product_msrp,
                            'description' => $pid->product_description,
                            'store_id' => $store_id,
                            'created_by' => $created_by,
                            'updated_by' => $created_by
                        ];
                        StoreProduct::create($sPr);
                    }
                }
            }

            /* check product exist in store product or not*/
            $query = StoreProduct::select('store_product.id', 'store_product.store_type_category_id', 'store_product.product_id', 'store_product.title', 'store_product.price','store_product.overwrite_price', 'store_product.created_at', 'product_type.name as ProductType')->with('Product');
            $query->leftJoin('products', 'products.product_id', '=', 'store_product.product_id');
            $query->leftJoin('product_type', 'products.product_type', '=', 'product_type.id');
            $query->whereIn('store_product.product_id', $product_ids);
            $query->where('store_product.store_id', $store_id);
            $products = $query->get();

            $storeTypeName = Store::select('store_type.name as storeTypeName')
                ->leftJoin('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store.id', $store_id)
                ->first();

            $storeCategories = [];
            $cat_option = '';
            if ($store_id) {
                $store = Store::with('storeType')->find($store_id);
                if ($store && $store->storeType->name === 'Magento') {
                    $storeCategories = StoreCategory::where('store_id', $store_id)->orderBy('name', 'asc')->get();
                } else {
                    //if the storetype code is amazon then fetch all the categories of specific marketplace codes
                    $storeCategories = StoreTypeCategory::select('store_type_category.*')
                        ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                        ->leftJoin('store', 'store_type.id', '=', 'store.store_type_id')
                        ->when($store->storeType->name === 'Amazon', function ($query) use ($store) {
                            $qualifiedcategoryCodes = ['ELECTRONIC_CABLE', 'SCREEN_PROTECTOR', 'HEADPHONES', 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'CELL_PHONE_HOLSTER', 'CHARGING_ADAPTER', 'POWER_BANK', 'CAMERA_TRIPOD', 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'CELLULAR_PHONE_CASE'];
                            return $query->whereIn('store_type_category.marketplace_code', $qualifiedcategoryCodes);
                        })
                        ->where('store.id', $store_id)
                        ->orderBy('store_type_category.name', 'asc')
                        ->get();
                }


                if(count($storeCategories ?? []) > 0){
                    foreach($storeCategories as $key2=>$sCat){
                        if($storeTypeName->storeTypeName === 'Magento'){
                            $cat_option .= '<option value="'.$sCat->id.'">'.$sCat->name.'</option>';
                        }else{
                            $cat_option .= '<option value="'.$sCat->id.'">'.$sCat->name.'</option>';
                        }
                    }
                }
            }



            if ($step == 2) {
                $issues = [];
                foreach ($products as $storeProduct) {
                    $categoryId = $storeProduct->store_type_category_id;
                    if (isset($categoryId)) {
                        Log::info("Category ID: " . $categoryId);
                        $currentCategory = StoreTypeCategory::where('id', $categoryId)->first();
                        if (isset($currentCategory)) {
                            if ('Amazon' == $store->storeType->name) {
                                $issues[$storeProduct->product_id] = ListingUtility::validate($currentCategory->marketplace_code, $storeProduct->product_id, $store->storeType->id);
                            } else {
                                $issues[$storeProduct->product_id] = ListingUtility::validate($currentCategory->store_type_code, $storeProduct->product_id, $store->storeType->id);
                            }
                        }
                    }
                }
                $view = view('listing.selected_product_list', compact('products', 'storeCategories', 'storeTypeName', 'issues'))->render();
            } else if ($step == 3) {
                $view = view('listing.select_device_product_list', compact('products', 'storeCategories', 'storeTypeName'))->render();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => $view,
                    'storeCategories' => $cat_option,
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
        $spintax = new Spintax();

        if (!empty($product_ids)) {
            $short_keyword = [];
            $long_keyword = [];
            $sKeyword = $lKeyword = $pro_ids = $shortFlag = $longFlag = [];
            foreach ($product_ids as $pid) {
                $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $pid)->orderBy('priority', 'asc')->get()->toArray();

                if (!empty($keywords)) {

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
                    }


                }
            }

            /*echo '<pre>';
            print_r($pro_ids);
            print_r($long_keyword);
            print_r($long_keyword);
            echo '</pre>';die;*/

            if (!empty($pro_ids)) {
                foreach ($pro_ids as $id) {
                    if (!empty($short_keyword)) {
                        $temporaryText = implode(' ', $short_keyword[$id]);
                        $temporaryText = $spintax->process($temporaryText);
                        $sKeyword[$id] = $temporaryText;
                    }

                    if (!empty($short_keyword)) {
                        $temporaryText = implode(' ', $long_keyword[$id]);
                        $temporaryText = $spintax->process($temporaryText);
                        $lKeyword[$id] = $temporaryText;
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

        $product_ids = (array) $request->input('product_id');
        $device_type = (array) $request->input('device_type');
        $brand = (array) $request->input('brand');
        $devices = (array) $request->input('devices');
        $deviceSeries = (array) $request->input('device_series');
        $rating = (array) $request->input('rating');
        $popularity = (array) $request->input('popularity');
        $carriers = (array) $request->input('carriers');
        $is_selected = $request->input('is_selected');
        $include_carrier = $request->input('include_carrier');
        $generate_type_name = $request->input('generate_type_name');
        $selected_device = $request->input('selected_device');

        if (empty($product_ids)) {
            return response()->json(['error' => true]);
        }

        if ($product_ids) {
            /*$field = 'products.product_title';
            $field2 = 'products.product_title';
            if ($include_carrier == 1 || $generate_type_name == 'Carrier Group') {
                $field = 'carrier.name as carrier_name';
                $field2 = 'carrier.id as carrier_id';
            }*/
            $query = Device::select('device.*', 'product_interface.product_id', 'products.product_sku','products.product_title')->with(['brand', 'rating', 'deviceType', 'deviceSeries']);
            $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');
            $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
            $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
            $query->leftJoin('products', 'product_interface.product_id', '=', 'products.product_id');

            if ($include_carrier || $generate_type_name == 'Carrier Group') {
                $query->leftJoin('device_carrier', 'device_carrier.device_id', '=', 'device.id');
                $query->leftJoin('carrier', 'carrier.id', '=', 'device_carrier.carrier_id');
                $query->addSelect('carrier.name as carrier_name', 'carrier.id as carrier_id');
                if ($carriers) {
                    $query->whereIn('device_carrier.carrier_id', $carriers);
                }
                $query->whereNotNull('carrier.id');
            }

            $query->whereIn('product_interface.product_id', $product_ids);

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
            if ($deviceSeries) {
                $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $deviceSeries)->pluck('device_id')->toArray();
                $query->whereIn('device.id', $devices);
            }

            $query->orderBy('brands.name')->orderBy('device.name');


            $devicesList = $query->get();
            //echo '<pre>';
            //print_r($devices);
            // echo '</pre>';die;
            //echo $query->toSql();die;
            $count = $devicesList->count();


            $view = view('listing.target_product_listing', compact('devicesList', 'selected_device', 'is_selected'))->render();

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
        $typeName = $request->input('typeName');
        $store_type = $request->input('store_type');
        if ($generate_type) {
            if ($typeName == 'Free Group') {
                $templates = ListingGenerationTypeTemplate::where('generation_type_id', $generate_type)->whereLike('used_by_store', '%' . $store_type . '%')->get();
            } else {
                $templates = ListingGenerationTypeTemplate::where('generation_type_id', $generate_type)->get();
            }
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
            $product_ids = (array) $request->input('product_id');
            $store_id = $request->input('store_id');
            $title_templates = (array) $request->input('title_template');
            $compress = $request->input('compress');
            $include_carrier = $request->input('include_carrier');
            $generate_type = $request->input('generate_type');
            $generate_type_name = $request->input('generate_type_name');
            $brand_seperator = $request->input('brand_seperator');
            $group_seperator = $request->input('group_seperator');
            $device_seperator = $request->input('device_seperator');
            $selected_device = $request->input('selected_device');
            $selected_carrier = $request->input('selected_carrier');
            $product_details = $request->input('product_detail');
            $store_type = $request->input('store_type');
            // $use_store_as_brand = $request->input('use_store_as_brand');
//            Log::info(' product_details :: ' . json_encode($product_details));
            /*echo '<pre>';
            print_r($product_ids);
            print_r($product_details);
            echo '</pre>';*/

            if ($store_id && $product_ids) {
                $productsArray = [];
                //$products = StoreProduct::with('product', 'product.productType')->where('store_id', $store_id)->whereIn('product_id', $product_ids)->get();
                foreach ($product_ids as $key => $product_id) {

                    $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
                    $max_title_length = $store_type_detail->max_title_length;
                    //$products = StoreProduct::getStoreProducts($product_ids);
                    $product = StoreProduct::with('product', 'product.productType')->where('store_id', $store_id)->where('product_id', $product_id)->first();
//                    echo $query2->toSql();die;
//                    Log::info(' products :: ' . json_encode($products));

                    /*echo '<pre>';
                    print_r($products);die;*/
                    /*if (count($products ?? []) > 0) {*/
                    if (!empty($product)) {

                        /*if($products[$key]->product_id != $product_id) continue;

                        $product = $products[$key];*/

                        //foreach ($products as $key => $product) {
                            $store_product_id = trim($product->id);
                            Log::info(' store_product_id : ' . $store_product_id);
                            if (array_key_exists($store_product_id, $product_details)) {
                                $pDetail = $product_details[$store_product_id];
                                //Log::info('Product Detail:'. json_encode($pDetail));
                                if(empty($pDetail)){
                                    Log::info('SKIP Product Detail:');
                                    continue;
                                }
                                /*echo '<pre>';
                                print_r($product_details[$store_product_id]);
                                echo '</pre>';*/
                                $product_short_keyword = isset($pDetail['short_keyword']) ? $pDetail['short_keyword'] : '';
                                $product_long_keyword = isset($pDetail['long_keyword']) ? $pDetail['long_keyword'] : '';
                                $product_title = $pDetail['title'];
                                /*$product_price = $pDetail['price'];
                                $product_price_overwrite = $pDetail['price_overwrite'];
                                $product_store_type_category_id = $pDetail['store_type_category_id'];*/
                                $title_template = $title_templates[$key];
                                if ($title_template) {
                                    if (str_contains($title_template, '#unique_code')) {
                                        $title_template = str_replace('#unique_code', '[^]', $title_template);
                                    }

                                    /*if ($title_template) {
                                        $titleGenerator = new TitleGenerator(0, $max_title_length, []);
                                        $title_template = $titleGenerator->getProductTitle($title_template, $product_title, $product_short_keyword, $product_long_keyword);
                                    }*/

                                }

                                $devices = $this->getDevicesFromRequest($request, $product);
                                /*echo '<pre>';
                                print_r($devices);
                                echo '<pre>';die;*/

                                if ($generate_type_name) {
                                    switch (trim(strtolower($generate_type_name))) {
                                        case 'combined devices':
                                            $productsArray[] = $this->generateProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type);
                                            break;
                                        case 'free group':
                                            $prevBrand = null;
                                            $flag = false;
                                            $brandName = null;
                                            foreach ($devices as $key => $val) {
                                                $brandName = $val->brand_name;
                                                if ($prevBrand != NULL && $brandName != $prevBrand && str_contains($title_template, '#device_series')) {
                                                    $flag = true;
                                                    break;
                                                }
                                                $prevBrand = $brandName;
                                            }
                                            if ($flag) {
                                                continue 2;
                                            }
                                            if (strtolower($store_type) == 'ebay' && (strtolower($brandName) !='google')) {
                                                $productsArray[] = $this->generateFreeGroupProductTitlesEbay($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type);
                                            } else {
                                                $productsArray[] = $this->generateFreeGroupProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type);
                                            }
                                            break;
                                        case 'single device':
                                            $productsArray[] = $this->generateSingleDeviceProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type);
                                            break;
                                        case 'brand gruop':
                                            $productsArray[] = $this->generateBrandGroupProductTitles($title_template, $product, $devices, $max_title_length, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type);
                                            break;
                                        case 'carrier group':
                                            $productsArray[] = $this->generateCarrierGroupProductTitles($title_template, $product, $devices, $generate_type, $pDetail, $store_product_id, $store_type);
                                        case 'all carriers':
                                            $productsArray[] = $this->generateAllCarrierProductTitles($title_template, $product, $devices, $generate_type, $pDetail, $store_product_id, $store_type);
                                            break;
                                    }
                                }

                                if (!empty($productsArray)) {
                                    foreach ($productsArray as $k => $product) {
                                        if (!empty($product)) {
                                            foreach ($product as $key => $pro) {
                                                if (isset($pro['title'])) {

                                                    $titleGenerator = new TitleGenerator(0, $max_title_length, []);
                                                    $newTitle = $titleGenerator->getProductTitle($pro['title'], $product_title, $product_short_keyword, $product_long_keyword);
                                                    $productsArray[$k][$key]['title'] = $newTitle;
                                                }

                                            }
                                        }
                                    }
                                }

                            }
                        //}
                    }
                }

                /*echo '<pre>';
                print_r($productsArray);
                echo '</pre>';die;*/

                if (!empty($productsArray)) {
                    $view = view('listing.target_listing', compact('productsArray', 'max_title_length'))->render();

                    $models = $modelDetail = array();
                    $totalCount = 0;
                    foreach ($productsArray as $k => $product) {
                        if (!empty($product)) {
                            foreach ($product as $key => $pro) {
                                if (isset($pro['model'])) {
                                    $totalCount++;
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
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function generateFinalTitle($title, $product_title, $product_short_keyword, $product_long_keyword, $max_title_length){

        $titleGenerator = new TitleGenerator(0, $max_title_length, []);
        $newTitle = $titleGenerator->getProductTitle($title, $product_title, $product_short_keyword, $product_long_keyword);

        return $newTitle;
    }

    public function getDevicesFromRequest(Request $request, $product)
    {
        $device_type = $request->input('device_type');
        $brand = $request->input('brand');
        $device = $request->input('devices');
        $deviceSeries = $request->input('device_series');
        $rating = $request->input('rating');
        $popularity = $request->input('popularity');
        $carriers = $request->input('carriers');
        $include_carrier = $request->input('include_carrier');
        $generate_type_name = $request->input('generate_type_name');
        $selected_device = $request->input('selected_device');
        $selected_carrier = $request->input('selected_carrier');
        $store_type = $request->input('store_type');

        $field = 'brands.order_no';
        $field2 = 'brands.notes2';

        if ($include_carrier == 1 || $generate_type_name == 'Carrier Group') {
            $field = 'carrier.name as carrier_name';
            $field2 = 'carrier.id as carrier_id';
        }

        $query = Device::select('device.*', 'brands.name as brand_name', $field, $field2)->with('deviceType', 'deviceSeries', 'carriers');
        $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');
        $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
        $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');

        if ($include_carrier == 1 || strtolower($generate_type_name) == 'carrier group') {
            $query->leftJoin('device_carrier', 'device_carrier.device_id', '=', 'device.id');
            $query->leftJoin('carrier', 'carrier.id', '=', 'device_carrier.carrier_id');
            if ($carriers) {
                $query->whereIn('device_carrier.carrier_id', $carriers);
            }
            $query->whereNotNull('carrier.id');

            if (isset($selected_carrier[$product->product_id]) && !empty($selected_carrier[$product->product_id])) {
                $query->whereIn('device_carrier.carrier_id', $selected_carrier[$product->product_id]);
            }
        }
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
        if ($deviceSeries) {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $deviceSeries)->get();
            $query->whereIn('device.id', $devices);
        }
        if ($include_carrier == 1 || strtolower($generate_type_name) == 'carrier group') {

            $query->orderBy('carrier.name', 'asc');
        }
        $query->orderBy('brand_name', 'asc');
        $query->orderBy('device.name', 'asc');
        if (strtolower($store_type) == 'ebay') {

            $query->limit(250);
        }
        return $query->get();
    }


    private function generateProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $device_seperator; // Separator between device names
        $lastBrand = null;
        $currentBrand = null;
        $prevBrand = null;
        $currentMod = null;
        $lastModel = null;
        $cnt = 0;

        foreach ($devices as $key => $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }
            $brandName = $device->brand_name;
            $currentMod = $device->name;
            $parts = explode(' ', $device->name, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? '';

            if ($compress) {
                if ($prevBrand != null && $brandName != $prevBrand) {
                    $nameToAdd = $brand_seperator . ' ' . $brandName . ' ' . $currentMod;
                } else {
                    if ($brand === $lastBrand) {
                        $parts2 = explode(' ', $model, 2);
                        if (isset($parts2[1])) {
                            if ($parts2[0] == $lastModel) {
                                $model = $parts2[1];
                            }
                            //$model = $parts2[1];
                        }
                        $lastModel = $parts2[0];
                        $nameToAdd = $model;
                    } else {
                        /*$brandparts = explode(' ', $brand, 2);
                        if(isset($brandparts[1])) {
                            //$brand = $brandparts[1];
                        }*/
                        $lastModel = $model;
                        if ($key == 0) {
                            $nameToAdd = $currentBrand;
                        } else {
                            $nameToAdd = $brand_seperator . ' ' . $currentBrand;
                        }

                        $nameToAdd = $currentMod;
                    }
                }
                //$nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $currentMod;
            }

            //echo $nameToAdd.'<br>';

            $tempDevices = array_merge($currentDevices, [str_replace($device->brand_name . ' ', '', $nameToAdd)]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [str_replace($device->brand_name . ' ', '', $currentBrand)]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);


            $tempModels = array_merge($currentModels, [$currentMod]);
            $tempModelString = implode($device_seperator, $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $tempTitle);


            /*// Check if adding the device exceeds the character limit
            $tempModels = array_merge($currentModels, [str_replace($device->brand_name.' ','',$nameToAdd)]);
            $tempModelString = implode($brand_seperator . ' ', $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator . ' ', $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $tempTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator . ' ', $tempDevices);
            $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);*/

            /*$sku = StoreTypeListing::generateUniqueAlphabeticSku($product->product_sku);*/

            // Check if adding the device exceeds the character limit
            //echo strlen($tempTitle) .'>'. $charLimit.'<br>';
            if (strlen($tempTitle) > $charLimit) {

                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);

                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $currentModels = [$currentMod];
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;

            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                //$currentTitle .= ($currentTitle === $baseTitle ? ' ' : ' '.$separator.' ') . $nameToAdd;
                $currentModels[] = str_replace($device->brand_name . ' ', '', $nameToAdd);
                if (empty($currentDevices) && strtolower($device->brand_name) != 'apple') {
                    $currentDevices[] = $device->brand_name . ' ' . $nameToAdd;
                } else {
                    $currentDevices[] = $nameToAdd;
                }
                //$currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
            $prevBrand = $brandName;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            /*$titles[$cnt]['title'] = rtrim($currentTitle, ' '.$separator.' ');*/
            //$titles[$cnt]['title'] = str_replace('#model_series', implode($separator.' ', $currentModels), $baseTitle);
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
        }
        /*echo '<pre>';
        print_r($titles);die;*/
        return $titles;
    }

    private function generateSingleDeviceProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k => $device) {
            $currentBrand = $device->brand_name . ' ' . $device->name;
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#devices', $currentBrand, $tempTitle);
            $tempTitle = str_replace('#model_series', $device->name, $tempTitle);

            if (strlen($tempTitle) > $charLimit) {
                continue;
            }

            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product->product_sku;
            $titles[$k]['product_id'] = $product->product_id;
            $titles[$k]['price'] = $pDetail['price'];
            $titles[$k]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$k]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$k]['generate_type'] = $generate_type;
            $titles[$k]['store_product_id'] = $store_product_id;
            $titles[$k]['brand_id'] = $device->brand_id;
            $titles[$k]['device_id'] = $device->id;
            $titles[$k]['model'][] = $device->name;
            $titles[$k]['model_detail'][] = $device;
        }
        return $titles;
    }

    private function generateFreeGroupProductTitles_old($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $separator = $device_seperator; // Separator between device names
        foreach ($devices as $k => $device) {

            $currentBrand = $device->brand_name . ' ' . $device->name;
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }

            $tempTitle = str_replace('#device_series', $currentBrand, $baseTitle);
            $tempTitle = str_replace('#devices', $currentBrand, $tempTitle);
            $tempTitle = str_replace('#model_series', $currentBrand, $tempTitle);

            if (strlen($tempTitle) > $charLimit) {
                continue;
            }

            $titles[$k]['title'] = $tempTitle;
            $titles[$k]['product_code'] = $product->product->product_sku;
            $titles[$k]['product_id'] = $product->product_id;
            $titles[$k]['price'] = $pDetail['price'];
            $titles[$k]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$k]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$k]['generate_type'] = $generate_type;
            $titles[$k]['store_product_id'] = $store_product_id;
            $titles[$k]['brand_id'] = $device->brand_id;
            $titles[$k]['device_id'] = $device->id;
            $titles[$k]['model'][] = $device->name;
            $titles[$k]['model_detail'][] = $device;
        }
        return $titles;
    }

    private function generateFreeGroupProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $brand_seperator; // Separator between device names
        $lastBrand = null;
        $currentBrand = null;
        $prevBrand = null;
        $currentMod = null;
        $lastModel = null;
        $cnt = 0;

        foreach ($devices as $key => $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            /*if (strtolower($store_type) == 'ebay' && strtolower($device->brand_name) == 'apple') {*/

            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }

            $brandName = $device->brand_name;
            $currentMod = $device->name;
            $parts = explode(' ', $device->name, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? '';

            if ($compress) {
                if ($prevBrand != null && $brandName != $prevBrand) {
                    $nameToAdd = $brand_seperator . ' ' . $brandName . ' ' . $currentMod;
                } else {
                    if ($brand === $lastBrand) {
                        $parts2 = explode(' ', $model, 2);
                        if (isset($parts2[1])) {
                            if ($parts2[0] == $lastModel) {
                                $model = $parts2[1];
                            }
                            //$model = $parts2[1];
                        }
                        $lastModel = $parts2[0];
                        $nameToAdd = $model;
                    } else {
                        /*$brandparts = explode(' ', $brand, 2);
                        if(isset($brandparts[1])) {
                            //$brand = $brandparts[1];
                        }*/
                        $lastModel = $model;
                        if ($key == 0) {
                            $nameToAdd = $currentBrand;
                        } else {
                            $nameToAdd = $brand_seperator . ' ' . $currentBrand;
                        }

                        $nameToAdd = $currentMod;
                    }
                }
                //$nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $currentMod;
            }

            //echo $nameToAdd.'<br>';

            $tempDevices = array_merge($currentDevices, [str_replace($device->brand_name . ' ', '', $nameToAdd)]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [str_replace($device->brand_name . ' ', '', $currentBrand)]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);


            $tempModels = array_merge($currentModels, [$currentMod]);
            $tempModelString = implode($device_seperator, $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $tempTitle);

            // Check if adding the device exceeds the character limit
            //echo strlen($tempTitle) .'>'. $charLimit.'<br>';
            if (strlen($tempTitle) > $charLimit) {

                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);

                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $currentModels = [$currentMod];
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;

            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                //$currentTitle .= ($currentTitle === $baseTitle ? ' ' : ' '.$separator.' ') . $nameToAdd;
                $currentModels[] = str_replace($device->brand_name . ' ', '', $nameToAdd);
                if (empty($currentDevices) && strtolower($device->brand_name) != 'apple') {
                    $currentDevices[] = $brandName . ' ' . $nameToAdd;
                } else {
                    $currentDevices[] = $nameToAdd;
                }
                //$currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
            $prevBrand = $brandName;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            /*$titles[$cnt]['title'] = rtrim($currentTitle, ' '.$separator.' ');*/
            //$titles[$cnt]['title'] = str_replace('#model_series', implode($separator.' ', $currentModels), $baseTitle);
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
        }
        /*echo '<pre>';
        print_r($titles);die;*/
        return $titles;
    }

    private function generateFreeGroupProductTitlesEbayBKP1($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $brand_seperator; // Separator between device names
        $lastBrand = null;
        $lastModel = null;
        $currentBrand = null;
        $currentMod = null;
        $allDevice = [];
        $cnt = 0;

        foreach ($devices as $key => $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }

            $currentMod = $device->name;
            $parts = explode(' ', $device->name, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? '';

            // Use only the model if the brand is the same as the last one
            if ($compress) {
                if ($brand === $lastBrand) {
                    // Match model suffix like 'Plus', 'Ultra', etc.
                    preg_match('/\s+(Plus|Ultra|Pro|FE|4G|5G)$/i', $currentMod, $matches);
                    $suffix = $matches[1] ?? '';

                    // Extract model number only (S20, S21, etc.)
                    preg_match('/S\d{2}/i', $currentMod, $modelMatch);
                    $modelNumber = $modelMatch[0] ?? $currentMod;
                    //echo $suffix.'<br>';
                    if ($suffix) {
                        /*if($lastModel == $suffix){
                            $nameToAdd = $modelNumber;
                        }else{
                            $nameToAdd = $modelNumber.' '.$suffix;
                        }*/
                        $nameToAdd = $modelNumber;
                        $lastModel = $suffix;
                    } else {
                        $nameToAdd = $currentMod;
                    }
                } else {
                    if ($key == 0) {
                        $parts2 = explode(' ', $model, 2);
                        if (isset($parts2[0])) {
                            $modelname = $parts2[0];
                        }
                        /*$nameToAdd = $device->brand_name.' '.$brand.' '.$modelname;*/
                        $nameToAdd = $currentBrand;
                    } else {
                        //$nameToAdd = $brand_seperator.' '.$currentBrand;
                        $nameToAdd = $currentMod;
                    }
                    //$nameToAdd = $currentMod;
                }
                //$nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $currentBrand;
            }

            //echo '<pre>';
            //print_r($currentDevices);
            //echo '</pre>';

            //echo $nameToAdd;
            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $tempDevices = array_merge($currentDevices, [$nameToAdd]);
                $tempDeviceString = implode($device_seperator, $tempDevices);
                $tempTitle = str_replace('#devices', $tempDeviceString, $baseTitle);
            }

            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $tempDevices = array_merge($currentDevices, [$currentBrand]);
                $tempDeviceString = implode($device_seperator, $tempDevices);
                $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);
            }


            $tempModels = array_merge($currentModels, [str_replace($device->brand_name . ' ', '', $currentBrand)]);
            $tempModelString = implode($device_seperator, $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $tempTitle);

            if (strlen($tempTitle) > $charLimit || (!str_contains($baseTitle, '#device_series'))) {
                //$cnt++;
                //continue;
            }

            $titles[$cnt]['title'] = $tempTitle;
            $titles[$cnt]['product_code'] = $product->product->product_sku;
            $titles[$cnt]['product_id'] = $product->product_id;
            $titles[$cnt]['price'] = $pDetail['price'];
            $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$cnt]['generate_type'] = $generate_type;
            $titles[$cnt]['store_product_id'] = $store_product_id;
            $titles[$cnt]['brand_id'] = $device->brand_id;
            $titles[$cnt]['device_id'] = $device->id;
            $titles[$cnt]['model'][] = $device->name;
            $titles[$cnt]['model_detail'][] = $device;
            $currentModels[] = str_replace($device->brand_name . ' ', '', $nameToAdd);

            /*if(empty($currentDevices)){
                $currentDevices[] = $nameToAdd;
            }else {
                $currentDevices[] = $nameToAdd;
            }*/
            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $currentDevices[] = $nameToAdd;
            }
            // Update the last brand
            $lastBrand = $brand;
            $allDevice[] = $currentBrand;
        }

        /*echo '<pre>';
        print_r($currentDevices);
        echo '</pre>';die;*/

        // Add the last title
        if (!empty($currentTitle)) {
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
        }

        return $titles;
    }

    private function compressModelsBySuffix($devices): string {
        $modelGroups = [];

        foreach ($devices as $device) {
            $currentBrand = $device->brand_name . ' ' . $device->name;
            $parts = explode(' ', $currentBrand);
            if (count($parts) >= 2) {
                $suffix = array_pop($parts); // Last word as suffix
                $base = array_pop($parts);   // Second-last word as model (e.g., S20, S21)
            } else {
                $suffix = '';
                $base = $currentBrand;
            }

            $modelGroups[$suffix][] = $base;
        }

        // Build output strings per suffix group
        $resultParts = [];
        foreach ($modelGroups as $suffix => $models) {
            $uniqueModels = array_unique($models);
            $joined = implode('/', $uniqueModels);
            $resultParts[] = $suffix ? "$joined $suffix" : $joined;
        }

        return implode(', ', $resultParts);
    }

    private function groupDeviceModels($devices, $deviceSeparator = '/')
    {
        $grouped = [];

        foreach ($devices as $device) {
            $fullName = trim($device->name);
            $brand = $device->brand_name;

            $parts = explode(' ', $fullName);
            $prefix = $brand === 'Apple' ? 'iPhone' : $parts[0];
            $model = $parts[1] ?? '';
            $suffix = implode(' ', array_slice($parts, 2)) ?: '__base__';

            $groupKey = strtolower($brand) === 'apple' ? 'iPhone' : "$brand $prefix";

            $grouped[$groupKey][$suffix][] = $model;
        }

        $result = [];

        foreach ($grouped as $groupLabel => $suffixes) {
            $allModels = [];
            $allSuffixes = [];

            foreach ($suffixes as $suffix => $models) {
                $models = array_unique($models);
                sort($models, SORT_NATURAL);

                foreach ($models as $m) {
                    if (is_numeric($m)) {
                        $allModels[] = $m;
                    } else {
                        $allSuffixes[] = ucwords(strtolower($m));
                    }
                }

                if ($suffix !== '__base__') {
                    $allSuffixes[] = ucwords(strtolower($suffix));
                }
            }

            $allModels = array_unique($allModels);
            $allSuffixes = array_unique($allSuffixes);

            $combined = array_merge($allModels, $allSuffixes);
            $groupText = implode($deviceSeparator, $combined);

            $result[] = $groupLabel . ' ' . $groupText;
        }

        return $result;
    }


    /*private function groupDeviceModelsOld($devices, $deviceSeparator='/'): array
    {
        $grouped = [];

        foreach ($devices as $key=>$device) {
            $fullName = $device->name;
            //Log::info('Device :: ' . $fullName);
            $brand = $device->brand_name;
            if($brand === 'Apple') {
                $parts = explode(' ', $fullName, 3);
            }else{
                $parts = explode(' ', $fullName);
            }

            $prefix = $brand === 'Apple' ? 'iPhone' : $parts[0];
            //$prefix = $parts[0];
            $model = $parts[1] ?? '';
            $suffix = implode(' ', array_slice($parts, 2)) ?: '__base__';

            $groupKey = $brand . ' ' . $prefix;
            if(strtolower($brand) == 'apple'){
                $groupKey = $prefix;
            }
            //Log::info('Grouped cook' . $brand . $prefix);

            //Log::info('Model' . json_encode($model));

            $grouped[$groupKey][$suffix][] = $model;
        }

        //Log::info("grouped" . json_encode($grouped));


        $result = [];

        foreach ($grouped as $groupLabel => $suffixes) {
            //Log::info("Suffixes" . json_encode($suffixes));
            $cnt = 0;

            foreach ($suffixes as $suffix => $models) {
                if($cnt > 0){
                    $bpart = explode(' ',$groupLabel);
                    $groupLabel = array_pop($bpart);
                }

                sort($models, SORT_NATURAL);
                $lastModel = null;
                $newmodels = [];
                //print_r($models);
                foreach($models as $key=>$model){
                    if($key == 0){
                        $lastModel = $model;
                    }
                    if(str_contains($model,$lastModel)){
                        $gpart = explode(' ',$model);
                        $model = array_pop($gpart);
                    }
                    $newmodels[] = $model;
                    $lastModel = $model;
                }

                $uniqueModels = array_unique($newmodels);
                $groupText = implode($deviceSeparator, $uniqueModels);
                //Log::info("Group Label - " . $groupLabel);
                $full = $groupLabel . ' ' . $groupText;
                //echo $full;
                if ($suffix !== '__base__') {
                    $full .= ' ' . $suffix;
                }
                //Log::info('Full' . json_encode($full));
                $result[] = trim($full);
                $cnt++;
            }

        }

        return $result;
    }*/

    private function generateFreeGroupProductTitlesEbay($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $brand_seperator; // Separator between device names
        $lastBrand = null;
        $lastModel = null;
        $currentBrand = null;
        $currentMod = null;
        $suffix = null;
        $allDevice = [];
        $cnt = 0;

        foreach ($devices as $key => $device) {
            $titles[$cnt]['title'] = $baseTitle;
            $titles[$cnt]['product_code'] = $product->product->product_sku;
            $titles[$cnt]['product_id'] = $product->product_id;
            $titles[$cnt]['price'] = $pDetail['price'];
            $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$cnt]['generate_type'] = $generate_type;
            $titles[$cnt]['store_product_id'] = $store_product_id;
            $titles[$cnt]['brand_id'] = $device->brand_id;
            $titles[$cnt]['device_id'] = $device->id;
            $titles[$cnt]['model'][] = $device->name;
            $titles[$cnt]['model_detail'][] = $device;
        }

        $deviceSeries = $this->groupDeviceModels($devices,$device_seperator);

        // Add the last title
        if (!empty($currentTitle)) {
            $titles[$cnt]['title'] = str_replace('#device_series', implode($brand_seperator, $deviceSeries), $baseTitle);
        }

        return $titles;
    }

    private function generateFreeGroupProductTitlesEbayBKP($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $currentModels = [];
        $currentDevices = [];
        $currentTitle = $baseTitle;
        $separator = $brand_seperator; // Separator between device names
        $lastBrand = null;
        $lastModel = null;
        $currentBrand = null;
        $currentMod = null;
        $suffix = null;
        $allDevice = [];
        $cnt = 0;

        foreach ($devices as $key => $device) {
            // Split the device into brand and model
            $currentBrand = $device->brand_name . ' ' . $device->name;
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }

            $currentMod = $device->name;
            $parts = explode(' ', $device->name, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? '';

            // Use only the model if the brand is the same as the last one
            if ($compress) {
                if ($brand === $lastBrand) {
                    $parts2 = explode(' ', $model, 2);

                    if (isset($parts2[1])) {
                        //echo $parts2[0] .'=='. $lastModel.'<br>';
                        if ($parts2[0] == $lastModel) {
                            $model = $suffix = $parts2[1];
                        }else{
                            $model = $parts2[0];
                        }
                        //$model = $parts2[1];
                    }
                    //echo $model.'<br>';
                    $lastModel = $parts2[0];
                    /*$parts2 = explode(' ', $model, 2);
                    if (isset($parts2[1])) {
                        $model = $parts2[1];
                    }*/
                    //echo count($devices) .'=='. ($key + 1).'</br>';
                    $nameToAdd = $model;
                } else {
                    if ($key == 0) {
                        $nameToAdd = $currentBrand;
                    } else {
                        //$nameToAdd = $brand_seperator.' '.$currentBrand;
                        $nameToAdd = $currentMod;
                    }
                    //$nameToAdd = $currentMod;
                }
                //$nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $currentBrand;
            }

            //echo '<pre>';
            //print_r($currentDevices);
            //echo '</pre>';

            //echo $nameToAdd;
            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $tempDevices = array_merge($currentDevices, [$nameToAdd]);
                $tempDeviceString = implode($device_seperator, $tempDevices);
                $tempTitle = str_replace('#devices', $tempDeviceString, $baseTitle);
            }

            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $tempDevices = array_merge($currentDevices, [$currentBrand]);
                $tempDeviceString = implode($device_seperator, $tempDevices);
                $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);
            }


            $tempModels = array_merge($currentModels, [str_replace($device->brand_name . ' ', '', $currentBrand)]);
            $tempModelString = implode($device_seperator, $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $tempTitle);

            if (strlen($tempTitle) > $charLimit || (!str_contains($baseTitle, '#device_series'))) {
                //$cnt++;
                //continue;
            }

            $titles[$cnt]['title'] = $tempTitle;
            $titles[$cnt]['product_code'] = $product->product->product_sku;
            $titles[$cnt]['product_id'] = $product->product_id;
            $titles[$cnt]['price'] = $pDetail['price'];
            $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$cnt]['generate_type'] = $generate_type;
            $titles[$cnt]['store_product_id'] = $store_product_id;
            $titles[$cnt]['brand_id'] = $device->brand_id;
            $titles[$cnt]['device_id'] = $device->id;
            $titles[$cnt]['model'][] = $device->name;
            $titles[$cnt]['model_detail'][] = $device;
            $currentModels[] = str_replace($device->brand_name . ' ', '', $nameToAdd);

            /*if(empty($currentDevices)){
                $currentDevices[] = $nameToAdd;
            }else {
                $currentDevices[] = $nameToAdd;
            }*/
            if (!in_array($currentBrand, $currentDevices) && !in_array($currentBrand, $allDevice)) {
                $currentDevices[] = $nameToAdd;
            }
            // Update the last brand
            $lastBrand = $brand;
            $allDevice[] = $currentBrand;
        }

        /*echo '<pre>';
        print_r($currentDevices);
        echo '</pre>';die;*/

        // Add the last title
        if (!empty($currentTitle)) {
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($device_seperator, $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
        }

        return $titles;
    }

    private function generateBrandGroupProductTitles($baseTitle, $product, $devices, $charLimit, $brand_seperator, $device_seperator, $compress, $generate_type, $pDetail, $store_product_id, $store_type)
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
            if (strtolower($device->brand_name) == 'apple') {
                $currentBrand = $device->name;
            }
            $parts = explode(' ', $device->name, 2);
            $brand = $parts[0];
            $model = $parts[1] ?? ''; // If no model, treat as empty

            // Use only the model if the brand is the same as the last one
            if ($compress) {
                if ($brand === $lastBrand) {
                    $parts2 = explode(' ', $model, 2);
                    if (isset($parts2[1])) {
                        $model = $parts2[1];
                    }
                    $nameToAdd = $model;
                } else {
                    $nameToAdd = $currentBrand;
                }
                //$nameToAdd = ($brand === $lastBrand) ? $model : $currentBrand;
            } else {
                $nameToAdd = $device->name;
            }

            // Check if adding the device exceeds the character limit
            $tempModels = array_merge($currentModels, [$nameToAdd]);
            $tempModelString = implode($brand_seperator . ' ', $tempModels);
            $tempTitle = str_replace('#model_series', $tempModelString, $baseTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#devices', $tempDeviceString, $tempTitle);

            $tempDevices = array_merge($currentDevices, [$currentBrand]);
            $tempDeviceString = implode($device_seperator, $tempDevices);
            $tempTitle = str_replace('#device_series', $tempDeviceString, $tempTitle);

            // Check if adding the device exceeds the character limit
            if ($brand !== $lastBrand || strlen($tempTitle) > $charLimit) {
                // Save the current title and start a new one
                //$titles[$cnt]['title'] = rtrim($currentTitle, $separator); // Remove trailing separator
                if (!empty($currentDevices)) {
                    $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($separator . ' ', $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
                }
                $cnt++;
                //$currentTitle = $baseTitle . ' ' . $nameToAdd;
                $currentDevices = [$currentBrand];
                $currentModels = [$currentBrand];
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
            } else {
                // Append the device name
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
                $currentModels[] = $nameToAdd;
                if (empty($currentDevices) && strtolower($device->brand_name) != 'apple') {
                    $currentDevices[] = $device->brand_name . ' ' . $nameToAdd;
                } else {
                    $currentDevices[] = $nameToAdd;
                }
                //$currentDevices[] = $nameToAdd;
            }

            // Update the last brand
            $lastBrand = $brand;
        }

        // Add the last title
        if (!empty($currentTitle)) {
            $titles[$cnt]['title'] = str_replace(['#model_series', '#devices', '#device_series'], [implode($separator . ' ', $currentModels), implode($device_seperator, $currentDevices), implode($device_seperator, $currentDevices)], $baseTitle);
        }

        return $titles;
    }

    private function generateCarrierGroupProductTitles($baseTitle, $product, $devices, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $lastCarrier = null;
        $cnt = -1;

        foreach ($devices as $device) {
            $currentCarrier = $device->carrier_name;

            if ($currentCarrier !== $lastCarrier) {
                $cnt++;

                $titles[$cnt]['title'] = str_replace('#carrier', $currentCarrier, $baseTitle);

                // Store general product details
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
            } else {
                $titles[$cnt]['product_code'] = $product->product->product_sku;
                $titles[$cnt]['product_id'] = $product->product_id;
                $titles[$cnt]['price'] = $pDetail['price'];
                $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
                $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
                $titles[$cnt]['generate_type'] = $generate_type;
                $titles[$cnt]['store_product_id'] = $store_product_id;
                $titles[$cnt]['brand_id'] = $device->brand_id;
                $titles[$cnt]['device_id'] = $device->id;
                $titles[$cnt]['model'][] = $device->name;
                $titles[$cnt]['model_detail'][] = $device;
            }
            $lastCarrier = $currentCarrier;
        }

        return $titles;
    }

    private function generateAllCarrierProductTitles($baseTitle, $product, $devices, $generate_type, $pDetail, $store_product_id, $store_type)
    {
        $titles = [];
        $lastCarrier = null;
        $cnt = 0;

        foreach ($devices as $device) {
            $currentCarrier = $device->carrier_name;

            $titles[$cnt]['title'] = str_replace('#carrier', $currentCarrier, $baseTitle);

            $titles[$cnt]['product_code'] = $product->product->product_sku;
            $titles[$cnt]['product_id'] = $product->product_id;
            $titles[$cnt]['price'] = $pDetail['price'];
            $titles[$cnt]['price_overwrite'] = $pDetail['price_overwrite'];
            $titles[$cnt]['store_type_category_id'] = $pDetail['store_type_category_id'];
            $titles[$cnt]['generate_type'] = $generate_type;
            $titles[$cnt]['store_product_id'] = $store_product_id;
            $titles[$cnt]['brand_id'] = $device->brand_id;
            $titles[$cnt]['device_id'] = $device->id;
            $titles[$cnt]['model'][] = $device->name;
            $titles[$cnt]['model_detail'][] = $device;
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
                    $product_titles = (array) $request->input('final_product_title');
                    $product_prices = (array) $request->input('final_product_price');
                    $product_price_overwrites = (array) $request->input('final_product_price_overwrite');
                    //$store_type_category_id =  $request->input('final_store_type_category_id');
                    $product_codes = (array) $request->input('final_product_code');
                    $store_product_ids = (array) $request->input('final_store_product_id');
                    $product_ids = (array) $request->input('final_product_id');
                    $brand_id = (array) $request->input('final_brand_id');
                    $device_id = (array) $request->input('final_device_id');
                    $include_carrier = $request->input('include_carrier');
                    $brand_combo = $request->input('brand_combo');
                    $carrier_combo = $request->input('carrier_combo');
                    $store_id = $request->input('store_id');
                    $store_type = $request->input('store_type');
                    $manufacturer_id = $request->input('manufacturer_id');
                    $store_detail = Store::where('id', $store_id)->first();
                    $product_models = $request->input('product_models');
                    if ($product_models) {
                        $product_models = json_decode($product_models);
                    }
                    /*echo '<pre>';
                    print_r($product_titles);
                    print_r($product_models);
                    echo '<pre>';die;*/
                    $created_by = auth()->user()->name ?? 'admin';

                    Log::info('Start save listing ' . date('Y-m-d H:i:s'));

                    $listingArr = [
                        'store_id' => $store_id,
                        'generation_type_id' => $generation_type_id,
                        'feed_type' => 1,
                        'status' => 1,
                        'chained' => 1,
                        'include_carriers' => $include_carrier,
                        'brand_combo' => $brand_combo,
                        'carrier_combo' => $carrier_combo,
                        'created_by' => $created_by,
                        'updated_by' => $created_by
                    ];

                    if (isset($manufacturer_id) && $manufacturer_id != '' && $manufacturer_id != 'use-store') {
                        $listingArr['manufacturer_id'] = $manufacturer_id;
                    }

                    //Log::info(' Listing :: ' . json_encode($listingArr));
                    $lisrecord = Listing::create($listingArr);
                    $listing_id = $lisrecord->id;
                    Log::info(' Listing ID :: ' . $listing_id);

                    $summary_products = [];
                    if ($listing_id && !empty($product_titles)) {

                        /* fetch all upc code at once*/
                        $upcCodes = $this->fetchUpcCode($store_detail, $store_id, count($product_titles));

                        Log::info('Fetch UPC ' . date('Y-m-d H:i:s'));

                        /* fetch all sku for all product code for listing*/
                        $jobs = [];
                        foreach ($product_titles as $key => $product) {

                            $skus = ListingItem::Where('sku', 'like', "{$product_codes[$key]}-%")->whereNotIn('status',[3])->pluck('sku')->toArray();

                            $detailSkus = ListingItemDetail::where('sku', 'like', "{$product_codes[$key]}-%")->where(function ($q){
                                $q->whereNull('kicked');
                                $q->orWhere('kicked', 0);

                            })->pluck('sku')->toArray();

                            $skuCache = array_flip(array_merge($skus, $detailSkus));

                            Log::info('Count ::' . count($skuCache));
                            Log::info('Fetch all sku ' . date('Y-m-d H:i:s'));

                            /*$existingSkus = ListingItem::whereLike('sku',$product_codes[$key].'-%')->pluck('sku')->toArray();
                            $detailSkus = ListingItemDetail::whereLike('sku',$product_codes[$key].'-%')->pluck('sku')->toArray();
                            $skuCache = array_flip(array_merge($existingSkus, $detailSkus));*/

                            //Log::info('Title :: ' . $key);
                            if ($store_detail->sku_generation_type) {
                                switch ($store_detail->sku_generation_type) {
                                    case 2:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku('', 6,$skuCache);
                                        break;
                                    default:
                                        $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6, $skuCache);
                                        break;
                                }
                            } else {
                                $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6, $skuCache);
                            }

                            $upc = $upcCodes[$key]['upc_code'] ?? null;
                            //Log::info(' UPC Code :: ' . json_encode($upcCodes));

                            $productDetail = StoreProduct::with('product', 'product.productType')->where('id', $store_product_ids[$key])->first();
                            $productDetail = $productDetail->toArray();
                            /*$product_name = $product;
                            if (str_contains($product, '[^]')) {
                                $product_name = str_replace('[^]', $sku, $product);
                            }*/
                            $product_name = str_contains($product, '[^]') ? str_replace('[^]', $sku, $product) : $product;
                            $summary_products[$key]['title'] = $product_name;
                            $summary_products[$key]['sku'] = $sku;
                            $summary_products[$key]['product_type'] = $productDetail['product']['product_type']['name'];
                            $summary_products[$key]['product_code'] = $productDetail['product']['product_sku'];
                            //$upc_code = $upcCodes->upc_code;
                            //$upc_id = $upcCodes->upc_code;
                            $listingProductArr = [
                                'product_name' => $product_name,
                                'description' => ($productDetail['description'] != '') ? $productDetail['description'] : $productDetail['product']['product_description'],
                                'product_category' => $productDetail['product']['product_type']['name'],
                                'sku' => $sku,
                                'price' => $product_prices[$key],
                                'item_id' => $product_ids[$key],
                                'upc' => $upc,
                                'store_id' => $store_id,
                                'has_offers' => 0,
                                'status' => 1,
                                'created_by' => $created_by,
                                'updated_by' => $created_by
                            ];
                            // log the time required to create store type listing
                            Log::info('Before Store Type Listing ' . date('Y-m-d H:i:s'));
                            //Log::info(' Store Type Listing :: ' . json_encode($listingProductArr));
                            $record = StoreTypeListing::create($listingProductArr);
                            $store_type_listing_id = $record->id;
                            //Log::info(' Store Type Listing ID :: ' . $store_type_listing_id);

                            if ($store_type_listing_id) {

                                $modelDetail = array(
                                    'title' => $product_name,
                                    'description' => ($productDetail['description'] != '') ? $productDetail['description'] : $productDetail['product']['product_description'],
                                    'store_type_listing_id' => $store_type_listing_id,
                                    'sku' => $sku,
                                    'store_sku' => '',
                                    'price' => $product_prices[$key],
                                    'quantity' => ($store_detail->default_listing_quantity) ? $store_detail->default_listing_quantity : 10,
                                    'sync_status' => ListingSyncStatus::Synchronizing,
                                    'store_product_id' => $store_product_ids[$key],
                                    'manufacturer_id' => ($productDetail['manufacturer_id'] != '') ? $productDetail['manufacturer_id'] : $productDetail['product']['manufacturer_id'],
                                    'brand_id' => $brand_id[$key],
                                    'device_id' => $device_id[$key],
                                    'generation_type_id' => $generation_type_id,
                                    'overwrite_title' => 1,
                                    'overwrite_price' => $product_price_overwrites[$key],
                                    'mpn' => $this->generateMPN($store_detail->short_code),
                                    'status' => ListingStatus::Active,
                                    'upc' => $upc,
                                    'created_by' => $created_by
                                );

                                //Log::info(' Listing Item :: ' . json_encode($modelDetail));
                                $item = ListingItem::create($modelDetail);
                                /*if ($item) {
                                    UPCCodes::where('upc_code', $upc)->update(['is_used' => 1]);
                                }*/

                                Log::info('Store listing Item' . date('Y-m-d H:i:s'));
                                $listing_item_id = $item->id;
                                $modelNames = [];
                                if ($listing_item_id) {
                                    $product_key = $product_ids[$key];
                                    //Log::info(' Product Models :: ' . json_encode($product_models->$product_key));
                                    $product_model = $product_models->$product_key;
                                    if (isset($product_model) && is_array($product_model)) {
                                        //Log::info(' Models :: ' . count($product_model));
                                        $itemDetail = [];
                                        foreach ($product_model as $k => $models) {

                                            if (!is_array($models) || empty($models)) continue;
                                            //Log::info(' Models :: ' . count($models));
                                            foreach ($models as $j => $model) {

                                                if (!$model) continue;
                                                //Log::info(' Model :: ' . json_encode($model));

                                                /*if ($store_type != '') {
                                                    switch (strtolower($store_type)) {
                                                        case 'walmart':
                                                            //case 'shopify':
                                                            $newupcCodes = $this->generateUpcCode($store_detail, $store_id);
                                                            break;
                                                        default:
                                                            $newupcCodes = $upcCodes;
                                                            break;
                                                    }
                                                }*/

                                                /*if(isset($models[0])) {
                                                    $model = $models[0];
                                                }else{
                                                    $model = $models;
                                                }*/
                                                if ($store_detail->sku_generation_type) {
                                                    switch ($store_detail->sku_generation_type) {
                                                        case 2:
                                                            $nsku = StoreTypeListing::generateUniqueAlphabeticSku('', 6, $skuCache);
                                                            break;
                                                        default:
                                                            $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6, $skuCache);
                                                            break;
                                                    }
                                                } else {
                                                    $nsku = StoreTypeListing::generateUniqueAlphabeticSku($product_codes[$key], 6, $skuCache);
                                                }

                                                /*if (str_contains($product, '[^]')) {
                                                    $product = str_replace('[^]', $nsku, $product);
                                                }*/

                                                $product = str_contains($product, '[^]') ? str_replace('[^]', $sku, $product) : $product;

                                                $modelNames[] = $model->name;
                                                $itemDetail[] = array(
                                                    'id' => (string)Str::uuid(),
                                                    'sku' => $nsku,
                                                    'title' => $product,
                                                    'upc' => $upc,
                                                    'device_id' => $model->id,
                                                    'listing_item_id' => $listing_item_id,
                                                    'device_carrier_id' => $model->carrier_id ?? null,
                                                    'created_by' => $created_by,
                                                    'created_at' => now(),
                                                    'updated_at' => now()
                                                );

                                                //Log::info(' Listing Item Detail :: ' . json_encode($itemDetail));
                                                //$rec = ListingItemDetail::create($itemDetail);
                                                //Log::info(' Listing Item Detail ID :: ' . $rec->id);



                                                /*if ($rec && isset($upc)) {
                                                    UPCCodes::where('upc_code', $upc)->update(['is_used' => 1]);
                                                }*/
                                            }

                                            Log::info('Store Listing Item Detail ' . date('Y-m-d H:i:s'));
                                        }

                                        foreach (array_chunk($itemDetail, 1000) as $chunk) {

                                            //InsertListingItemDetailJob::dispatch($chunk);
                                            $jobs[] = new InsertListingItemDetailJob($chunk);
                                            //DB::table('listing_item_detail')->insert($chunk);
                                        }

                                        if (isset($upc)) {
                                            UPCCodes::where('upc_code', $upc)->update(['is_used' => 1]);
                                        }

                                        $itemLink = array(
                                            'item_id' => $listing_item_id,
                                            'listing_id' => $listing_id,
                                            'sync_status' => ListingSyncStatus::Synchronizing,
                                            'old_price' => $product_prices[$key],
                                            'created_by' => $created_by
                                        );

                                        //Log::info(' Listing Item Link :: ' . json_encode($itemLink));
                                        ListingItemLink::create($itemLink);
                                        //Log::info(' Listing Item Link created successfully!');
                                    }
                                }
                            }

                            Log::info('Before Store Type Listing ' . date('Y-m-d H:i:s'));
                            Log::info(' Listing Product created successfully!');
                            $summary_products[$key]['models'] = implode(', ', array_unique($modelNames));
                        }
                        DB::commit();

                        $view = view('listing.listing_summary', compact('summary_products'))->render();

                        Bus::chain(array_merge(
                            $jobs,
                            [
                                new ListingItemImageCreate($listing_id),
                                new FeedGenerationJob($listing_id),
                            ]
                        ))->dispatch();

                        return response()->json([
                            'success' => true,
                            'summary' => $view,
                            'message' => 'Product listing created successfully!'
                        ]);
                    }

                    DB::rollBack();
                    return response()->json([
                        'error' => true,
                        'message' => 'Listind id or product titles are missing!'
                    ]);
                }

                DB::rollBack();
                return response()->json([
                    'error' => true,
                    'message' => 'Missing final product titles!'
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }


        return response()->json(['error' => true, 'message' => 'Something went wrong! Please try again!']);
    }


    protected function fetchUpcCode($store_detail, $store_id = '', $limit = 1)
    {
        if ($store_detail->upc_usage_type) {
            switch ($store_detail->upc_usage_type) {
                case 2:
                    $upcCodes = GoodUpc::select('id','upc_code')->where('is_used', 0)->orderBy('upc', 'asc')->limit($limit)->get()->toArray();
                    break;
                default:
                    $upcCodes = UPCCodes::select('id','upc_code')->where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get()->toArray();
                    break;
            }
        } else {
            $upcCodes = UPCCodes::select('id','upc_code')->where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit($limit)->get()->toArray();
        }

        return $upcCodes;
    }

    private function generateUpcCode($store_detail, $store_id = '')
    {
        if ($store_detail->upc_usage_type) {
            switch ($store_detail->upc_usage_type) {
                case 0:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();
                    break;
                case 1:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();
                    break;
                case 2:
                    $upcCodes = GoodUpc::where('is_used', 0)->orderBy('upc', 'asc')->limit(1)->first();
                    break;
                case 3:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();
                    break;
                default:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();
                    break;
            }
        } else {
            $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->limit(1)->first();
        }

        return $upcCodes;
    }

    private function generateMPN($suffix = '')
    {
        $randomPart = strtoupper(Str::random(6)); // 6 random alphanumeric characters
        //$suffix = 'GI'; // Fixed suffix
        if ($suffix != '') {
            return "{$randomPart}-{$suffix}";
        } else {
            return "{$randomPart}";
        }
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

                        // Insert everything from listing_item to listing_item_history
                        $itemRecord = ListingItem::find($list->id);
                        if ($itemRecord) {
                            ListingItemHistory::createBackup($itemRecord);
                        }
                        $itemRecord->price = $updated_price;
                        $itemRecord->updated_by = $created_by;
                        $itemRecord->update();
//                        $clonedRecord = $itemRecord->replicate();
                        //$clonedRecord->store_sku = NULL;
//                        $clonedRecord->submit_count = 0;
//                        $clonedRecord->price = $updated_price;
//                        $clonedRecord->created_by = $created_by;
//                        $clonedRecord->updated_by = $created_by;
//                        $clonedRecord->save();
//                        $listing_item_id = $clonedRecord->id;
//                        $itemDetails = ListingItemDetail::where('listing_item_id', $list->id)->get();

                        if (!empty((array)$itemRecord)) {
//                            foreach ($itemDetails as $item_detail) {
//                                $itemDetail = ListingItemDetail::find($item_detail->id);
//                                $itemclonedRecord = $itemDetail->replicate();
//                                $itemclonedRecord->listing_item_id = $listing_item_id;
//                                $itemclonedRecord->created_by = $created_by;
//                                $itemclonedRecord->updated_by = $created_by;
//                                $itemclonedRecord->save();
//                            }
                            $itemLink = array(
                                'item_id' => $itemRecord->id,
                                'listing_id' => $listing_id,
                                'sync_status' => ListingSyncStatus::Synchronized,
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
                Log::info('Inside Inventory Sync ');
                $listingDetails = ListingItem::with('storeProduct.store:id')->whereIn('id', $selected_listing)->get();
                if (!empty((array)$listingDetails)) {
                    Log::info('Listing Info Found');
                    $listing_id = '';
                    foreach ($listingDetails as $key => $list) {
                        $created_by = auth()->user()->name ?? 'admin';
                        if ($key == 0) {
                            $listingArr = [
                                'store_id' => $list->storeProduct->store->id,
                                'generation_type_id' => $list->generation_type_id,
                                'feed_type' => FeedTypes::INVENTORY,
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
                        if ($itemRecord) {
                            ListingItemHistory::createBackup($itemRecord);
                        }
                        $itemRecord->quantity = $updated_inventory;
                        $itemRecord->update();

                        if (!empty((array)$itemRecord)) {
                            $itemLink = array(
                                'item_id' => $itemRecord->id,
                                'listing_id' => $listing_id,
                                'sync_status' => ListingSyncStatus::Synchronized,
                                'old_price' => $itemRecord->price,
                                'created_by' => $created_by
                            );
                            ListingItemLink::create($itemLink);
                            FeedGenerationJob::dispatch($listing_id);
                        }

//                        $listingItemLink = ListingItemLink::where('listing_id', $list->id)->get();
//                        Log::info("Item Item Link");
//                        if (!empty((array)$listingItemLink)) {
//                            foreach ($listingItemLink as $item) {
//                                Log::info("Inside Item " . json_encode($item));
//                                $itemRecord = ListingItem::find($item->item_id);
//
//                                $clonedRecord = $itemRecord->replicate();
//                                $clonedRecord->quantity = $updated_inventory;
//                                $clonedRecord->save();
//                                $listing_item_id = $clonedRecord->id;
//
//                                $itemDetails = ListingItemDetail::where('listing_item_id', $item->item_id)->get();
//
//                                if (!empty((array)$itemDetails)) {
//                                    foreach ($itemDetails as $item_detail) {
//                                        $itemDetail = ListingItemDetail::find($item_detail->id);
//                                        $itemclonedRecord = $itemDetail->replicate();
//                                        $itemclonedRecord->listing_item_id = $listing_item_id;
//                                        $itemclonedRecord->save();
//                                    }
//
//                                    $itemLink = array(
//                                        'item_id' => $listing_item_id,
//                                        'listing_id' => $listing_id,
//                                        'sync_status' => ListingSyncStatus::Synchronized,
//                                        'old_price' => $itemRecord->price,
//                                        'created_by' => $created_by
//                                    );
//
//                                    ListingItemLink::create($itemLink);
//                                    FeedGenerationJob::dispatch($listing_id);
//
//                                }
//                            }
//                        }
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

    public function updateStoreProduct(Request $request)
    {

        $id = $request->input('id');
        $category_id = $request->input('category_id');
        $store_id = $request->input('store_id');
        $product = StoreProduct::findOrFail($id); // Retrieve the product by ID
        $product->update(['store_type_category_id' => $category_id]);

        $store = Store::findOrFail($store_id);
        $currentCategory = StoreTypeCategory::where('id', $category_id)->first();
        if ('Amazon' == $store->storeType->name) {
            $issues = ListingUtility::validate($currentCategory->marketplace_code, $product->product_id, $store->storeType->id);
        } else {
            $issues = ListingUtility::validate($currentCategory->store_type_code, $product->product_id, $store->storeType->id);
        }

        $table_html = view('listing.error_tables', ['issues' => $issues, 'product' => $product])->render();

        return response()->json([
            'success' => true,
            'issues' => $issues,
            'issue_table_html' => $table_html
        ]);
    }

    public function updateAllStoreProduct(Request $request)
    {

        $store_product_ids = $request->input('store_product_ids');
        $category_id = $request->input('category_id');
        $store_id = $request->input('store_id');
        $issues = $table_html = [];
        if(!empty($store_product_ids)){
            foreach($store_product_ids as $id) {
                $product = StoreProduct::findOrFail($id); // Retrieve the product by ID
                $product->update(['store_type_category_id' => $category_id]);

                $store = Store::findOrFail($store_id);
                $currentCategory = StoreTypeCategory::where('id', $category_id)->first();
                if ('Amazon' == $store->storeType->name) {
                    $issues[$product->product_id] = ListingUtility::validate($currentCategory->marketplace_code, $product->product_id, $store->storeType->id);
                } else {
                    $issues[$product->product_id] = ListingUtility::validate($currentCategory->store_type_code, $product->product_id, $store->storeType->id);
                }

                $table_html[$product->product_id] = view('listing.error_tables', ['issues' => $issues[$product->product_id],'product'=>$product])->render();
            }
        }




        return response()->json([
            'success' => true,
            'issues' => $issues,
            'issue_table_html' => $table_html
        ]);
    }

    public function endList(Request $request)
    {
        $created_by = auth()->user()->name ?? 'admin';
        $selected_listing = $request->input('selected_list');
        $selected_sku = $request->input('selected_sku');

        if (empty($selected_listing)) {
            return response()->json([
                'error' => true,
                'message' => 'No items selected.'
            ]);
        }

        $storeGroupedItems = [];

        foreach ($selected_listing as $key => $listing_item_id) {
            $listingItem = ListingItem::find($listing_item_id);
            if (!$listingItem) continue;

            $sku = $selected_sku[$key] ?? null;

            $listingDetail = ListingItemLink::select('listing_item_link.*')
                ->with('listing')
                ->leftJoin('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')
                ->where('listing_item_link.item_id', $listing_item_id)
                ->where('listing_item.sku', $sku)
                ->first();

            if ($listingDetail && $listingDetail->listing) {
                $store_id = $listingDetail->listing->store_id;

                $storeGroupedItems[$store_id][] = [
                    'item_id' => $listingDetail->item_id,
                    'listing' => $listingDetail->listing
                ];
            }
        }

        if (empty($storeGroupedItems)) {
            return response()->json([
                'error' => true,
                'message' => 'No valid listings found for items.'
            ]);
        }

        foreach ($storeGroupedItems as $store_id => $items) {
            if (empty($items)) continue;

            $firstListing = $items[0]['listing'];

            // Create one listing per store
            $listingArr = [
                'store_id' => $store_id,
                'generation_type_id' => $firstListing->generation_type_id,
                'feed_type' => FeedTypes::DELETE,
                'status' => 0,
                'chained' => 1,
                'include_carriers' => $firstListing->include_carriers,
                'brand_combo' => $firstListing->brand_combo,
                'carrier_combo' => $firstListing->carrier_combo,
                'created_by' => $created_by,
                'updated_by' => $created_by
            ];

            $listingRecord = Listing::create($listingArr);
            $listing_id = $listingRecord->id;

            foreach ($items as $item) {
                ListingItemLink::create([
                    'item_id' => $item['item_id'],
                    'listing_id' => $listing_id,
                    'sync_status' => ListingSyncStatus::Synchronized,
                    'created_by' => $created_by
                ]);
            }

            // Dispatch one job per store listing
            Bus::chain([
                new FeedGenerationJob($listing_id),
            ])->dispatch();
        }

        return response()->json([
            'success' => true
        ]);
    }


    public function endList_bkp(Request $request)
    {
        $created_by = auth()->user()->name ?? 'admin';
        $selected_listing = $request->input('selected_list');
        $selected_sku = $request->input('selected_sku');

        if (!empty($selected_listing)) {
            foreach ($selected_listing as $key=>$listing_item) {
                $listingItem = ListingItem::findOrFail($listing_item);
                $listingDetail = ListingItemLink::select('listing_item_link.*')->with('listing')->leftJoin('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')->where('listing_item_link.item_id', $listing_item)->where('listing_item.sku', $selected_sku[$key])->first();

                if ($listingDetail) {
                    Log::info('Listing Info Found');
                    $created_by = auth()->user()->name ?? 'admin';
                    $listingArr = [
                        'store_id' => $listingDetail->listing->store_id,
                        'generation_type_id' => $listingDetail->listing->generation_type_id,
                        'feed_type' => FeedTypes::DELETE,
                        'status' => 0,
                        'chained' => 1,
                        'include_carriers' => $listingDetail->listing->include_carriers,
                        'brand_combo' => $listingDetail->listing->brand_combo,
                        'carrier_combo' => $listingDetail->listing->carrier_combo,
                        'created_by' => $created_by,
                        'updated_by' => $created_by
                    ];


                    $listrecord = Listing::create($listingArr);
                    $listing_id = $listrecord->id;


                    if ($listing_id) {
                        $itemLink = array(
                            'item_id' => $listingDetail->item_id,
                            'listing_id' => $listing_id,
                            'sync_status' => ListingSyncStatus::Synchronized,
                            'created_by' => $created_by
                        );

                        //$listingItem->update(['status' => ListingStatus::Deleted, 'sync_status' => ListingSyncStatus::Unsynchronize]);
                        ListingItemLink::create($itemLink);

                        Bus::chain([
                            new FeedGenerationJob($listing_id),
                        ])->dispatch();
                    }



                }
            }

            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'error' => true
        ]);
    }

    public function reList(Request $request)
    {

        $selected_listing = $request->input('selected_list');
        $selected_sku = $request->input('selected_sku');

        if (!empty($selected_listing)) {
            try {
                Log::info('Inside Relist Sync ');
                foreach ($selected_listing as $key => $list) {

                    $listingDetail = ListingItemLink::select('listing_item_link.*')->with('listing')->leftJoin('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')->where('listing_item_link.item_id', $list)->where('listing_item.sku', $selected_sku[$key])->first();

                    if ($listingDetail) {
                        Log::info('Listing Info Found');
                        $created_by = auth()->user()->name ?? 'admin';
                        $listingArr = [
                            'store_id' => $listingDetail->listing->store_id,
                            'generation_type_id' => $listingDetail->listing->generation_type_id,
                            'feed_type' => FeedTypes::CREATE,
                            'status' => 0,
                            'chained' => 1,
                            'include_carriers' => $listingDetail->listing->include_carriers,
                            'brand_combo' => $listingDetail->listing->brand_combo,
                            'carrier_combo' => $listingDetail->listing->carrier_combo,
                            'created_by' => $created_by,
                            'updated_by' => $created_by,
                            're_list' => 1
                        ];

                        /*echo '<pre>';
                        print_r($listingArr);
                        echo '</pre>';die;*/

                        $listrecord = Listing::create($listingArr);
                        $listing_id = $listrecord->id;

                        /*$itemRecord = ListingItem::find($list->item_id);

                        $clonedRecord = $itemRecord->replicate();
                        $clonedRecord->save();
                        $listing_item_id = $clonedRecord->id;

                        $itemDetails = ListingItemDetail::where('listing_item_id', $list->item_id)->get();*/

                        if ($listing_id) {
                            /*foreach ($itemDetails as $item_detail) {
                                $itemDetail = ListingItemDetail::find($item_detail->id);
                                $itemclonedRecord = $itemDetail->replicate();
                                $itemclonedRecord->listing_item_id = $list->item_id;
                                $itemclonedRecord->save();
                            }*/

                            $itemLink = array(
                                'item_id' => $listingDetail->item_id,
                                'listing_id' => $listing_id,
                                'sync_status' => ListingSyncStatus::Synchronized,
                                'created_by' => $created_by
                            );

                            ListingItemLink::create($itemLink);
                            Bus::chain([
                                new ListingItemImageCreate($listing_id),
                                new FeedGenerationJob($listing_id),
                            ])->dispatch();
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Re-list successfully!'
                ]);

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

    public function checkUpcExist(Request $request)
    {

        $store_id = $request->input('store_id');

        if ($store_id) {

            $upc_count = UPCCodes::where('store', $store_id)->where('is_used', 0)->count();

            return response()->json([
                'success' => true,
                'upc_cnt' => $upc_count
            ]);
        }
        return response()->json([
            'error' => true,
            'message' => 'Something went wrong! Please try again!'
        ]);
    }

    public function getStoreManufacturer(Request $request)
    {
        $store_id = $request->input('store_id');

        if ($store_id != '') {

            $storeManufacturers = StoreManufactureLink::with('manufacturer')->where('store_id', $store_id)->get();

            $option = '<option value=""></option><option value="use-store">Use store as brand</option>';
            if (!empty($storeManufacturers)) {
                foreach ($storeManufacturers as $manufacturer) {
                    $option .= '<option value="' . $manufacturer->manufacturer->id . '">' . $manufacturer->manufacturer->name . '</option>';
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'option' => $option
                ]);
            }
        }

        return response()->json([
            'success' => false
        ]);
    }
}
