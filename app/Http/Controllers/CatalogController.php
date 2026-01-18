<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Jobs\SubmitShopifyProductsJob;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\Interfaces;
use App\Models\ListingRequirement;
use App\Models\Manufacturer;
use App\Models\ProductChannelDetail;
use App\Models\ProductColor;
use App\Models\ProductCondition;
use App\Models\ProductInterface;
use App\Models\ProductKeyFeatures;
use App\Models\ProductSearchTerm;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\ProductTags;
use App\Models\ProductTargetAudience;
use App\Models\ProductTitleKeyword;
use App\Models\ProductImages;
use App\Models\ProductType;
use App\Models\ProductImageAlternative;
use App\Models\ShippingPackage;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Models\SysFile;
use App\Utils\ListingUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class CatalogController extends Controller
{

    private $perPage = 10;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        session()->forget('pro_id');
        /*$productTypes = ProductType::orderBy('name')->get();*/
        $productTypes = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        $manufacturers = Manufacturer::orderBy('name')->get();
        $colors = ProductColor::orderBy('name')->get();
        $productConditions = ProductCondition::all();
        $targetAudience = ProductTargetAudience::all();
        $shippingCarrierPackage = ShippingPackage::orderBy('name')->get();
        $interfaces = Interfaces::all();
        $devices = Device::orderBy('name')->get();
        $perPage = $this->perPage;

        return view('catalog.list', compact('perPage', 'productTypes', 'manufacturers', 'productConditions', 'targetAudience', 'colors', 'interfaces', 'devices', 'shippingCarrierPackage'));
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

        $query = Catalog::with('Manufacturer', 'productType', 'interfaces.interfaces', 'pinterface.interfaces')->select('*');
        /*$perPage = $request->input('perPage', $this->perPage);*/

        // Filter by product title
        QueryFilters::applyFilter($query, $request, 'products.product_title', 'name_operator', 'name');

        // Filter by product title
        QueryFilters::applyFilter($query, $request, 'products.product_sku', 'product_sku_operator', 'product_sku');

        // Filter by product description
        QueryFilters::applyFilter($query, $request, 'products.product_description', 'product_description_operator', 'product_description');

        // Filter by shippin package id
        QueryFilters::applyFilter($query, $request, 'products.shipping_package_id', 'shipping_package_id_operator', 'shipping_package_id');

        // Filter by product type
        if ($request->has('product_type') && $request->product_type != '') {
            $all_product_ids[] = $request->product_type;
            $product_sub_types = ProductType::select('id')->where('parent_id', $request->product_type)->get()->toArray();
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

            if (!empty($all_product_ids)) {
                $query->whereIn('products.product_type', $all_product_ids);
            }
        }
        //QueryFilters::applyFilter($query, $request, 'product_type', 'product_type_operator', 'product_type');

        // Filter by color
        QueryFilters::applyFilter($query, $request, 'products.product_color', 'product_color_operator', 'product_color');

        // Filter by configurable
        if ($request->has('configurable') && $request->configurable != '') {
            if ($request->configurable) {
                $query->where('products.configurable', 0);
                $query->orWhere('products.configurable', NULL);
            }
        }


        // Filter by manufacturer
        if ($request->has('manufacturer_id') && $request->manufacturer_id != '') {
            $query->where('products.manufacturer_id', $request->manufacturer_id);
        }

        // Filter by product condition
        if ($request->has('product_condition') && $request->product_condition != '') {
            $query->where('products.product_condition_id', $request->product_condition);
        }

        // Filter by product_weight
        if ($request->has('product_weight') && $request->product_weight != '') {
            $query->where('products.product_weight', $request->product_weight);
        }

        // Filter by product_weight
        if ($request->has('package_weight') && $request->package_weight != '') {
            $query->where('products.package_weight', $request->package_weight);
        }

        // Filter by target audience
        if ($request->has('target_aud') && $request->target_aud != '') {
            $query->where('products.target_aud', $request->target_aud);
        }

        // Filter by dimension width
        if ($request->has('dimensions_width') && $request->dimensions_width != '') {
            $query->where('products.dimensions_width', $request->dimensions_width);
        }

        // Filter by dimension height
        if ($request->has('dimensions_height') && $request->dimensions_height != '') {
            $query->where('products.dimensions_height', $request->dimensions_height);
        }
        // Filter by dimension length
        if ($request->has('dimensions_length') && $request->dimensions_length != '') {
            $query->where('products.dimensions_length', $request->dimensions_length);
        }

        // Filter by package dimension width
        if ($request->has('package_dimensions_width') && $request->package_dimensions_width != '') {
            $query->where('products.package_dimensions_width', $request->package_dimensions_width);
        }

        // Filter by package dimension height
        if ($request->has('package_dimensions_height') && $request->package_dimensions_height != '') {
            $query->where('products.package_dimensions_height', $request->package_dimensions_height);
        }
        // Filter by package dimension length
        if ($request->has('package_dimensions_length') && $request->package_dimensions_length != '') {
            $query->where('products.package_dimensions_length', $request->package_dimensions_length);
        }

        if ($request->has('interface') && $request->interface != '') {
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            $query->where('product_interface.interface_id', $request->interface);
            QueryFilters::applyFilter($query, $request, 'product_interface.interface_id', 'interface_operator', 'interface');
        }

        if ($request->has('device') && $request->device != '') {
            if (!$request->has('interface') || $request->interface == '') {
                $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'products.product_id');
            }
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            QueryFilters::applyFilter($query, $request, 'device_interface.device_id', 'device_operator', 'device');
        }


        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->where('products.created_at', $request->created_at);
        }

        // Filter by price range
        if ($request->has('price_min') && $request->price_min != '') {
            $query->where('products.product_price', '>=', $request->price_min);
        }

        if ($request->has('price_max') && $request->price_max != '') {
            $query->where('products.product_price', '<=', $request->price_max);
        }

        // Filter by product status
        if ($request->has('status') && $request->status != '') {
            $query->where('products.product_status', $request->status);
        }

        if ($columnName != '') {
            switch (strtolower($columnName)) {
                case 'product_id':
                    $query->orderBy('products.product_id', $columnSortOrder);
                    break;
                case 'product_sku':
                    $query->orderBy('products.product_sku', $columnSortOrder);
                    break;
                case 'product_type':
                    $query->orderBy('products.product_type', $columnSortOrder);
                    break;
                case 'product_title':
                    $query->orderBy('products.product_title', $columnSortOrder);
                    break;
                case 'product_status':
                    $query->orderBy('products.product_status', $columnSortOrder);
                    break;
                case 'configurable':
                    $query->orderBy('products.configurable', $columnSortOrder);
                    break;
                case 'product_price':
                    $query->orderBy('products.product_price', $columnSortOrder);
                    break;
                case 'manufacture_name':
                    $query->orderBy('products.manufacturer_id', $columnSortOrder);
                    break;
                case 'product_color':
                    $query->orderBy('products.product_color', $columnSortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('products.created_at', $columnSortOrder);
                    break;

            }
        }

        $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();
        $query->whereNotIn('products.product_type', $productTypes);

//        $query->where('oem_generic',2);
        $query->whereNull('products.parent_id');
        //$query->where('products.product_status','!=','discontinued');
        ///echo $query->toSql();die;
        /*$productsRecord = $query->paginate($perPage); // Paginate results
        $products = $query->skip($start)->take($perPage)->get(); // Paginate results
        $totalRecordwithFilter = $productsRecord->total();*/
        $totalRecords = $query->count();
        $products = $query->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$products)) {
            foreach ($products as $key => $product) {
                if ($product->configurable == 1) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }

                $data[] = array(
                    "product_id" => $product->product_id . '<input type="checkbox" class="pro-id" name="product[' . $key . '][product_id]" value="' . $product->product_id . '" style="visibility:hidden;">',
                    "product_image_url" => ($product->image) ? '<div class="main-image"><img src="' . asset('storage/' . $product->image->getFileUrl()) . '" alt="' . $product->image . '" class="mainImage" width="70" height="70" data-product-id="' . $product->product_id . '"></div>' : '',
                    "product_sku" => '<span class="prVal">' . $product->product_sku . '</span><input type="text" class="form-control bulk-edit" name="product[' . $key . '][product_sku]" value="' . $product->product_sku . '" disabled>',
                    "product_type" => $product->productType->name,
                    "product_title" => '<span class="prVal">' . $product->product_title . '</span><textarea class="form-control bulk-edit" name="product[' . $key . '][product_title]" rows="3" disabled>' . $product->product_title . '</textarea>',
                    "product_status" => '<span class="prVal">' . $product->product_status . '</span><select class="form-control bulk-edit" name="product[' . $key . '][product_status]" disabled>
                        <option value="active"> Active </option>
                        <option value="discontinued"> Discontinued </option>
                        <option value="out_of_stock"> Out of stock </option></select>',
                    /*"configurable" => '<input type="checkbox" class="configurable" data-id="'.$product->product_id.'" '.$checked.'>',*/
                    "marketplace_price" => $product->product_marketplace_price,
                    "product_price" => '<span class="prVal">' . $product->product_price . '</span><input type="text" class="form-control bulk-edit" name="product[' . $key . '][product_price]" value="' . $product->product_price . '" disabled>',
                    "interface" => (isset($product->pinterface)) ? $product->pinterface->interfaces->name : '',
                    "manufacture_name" => isset($product->Manufacturer->name) ? $product->Manufacturer->name : '',
                    "product_color" => isset($product->color) ? $product->color->name : '',
                    "created_at" => date('Y-m-d H:i:s', strtotime($product->created_at)),
                    "action" => '<a href="' . route('catalog.edit', $product->product_id) . '" class="btn btn-primary">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                                </a>
                                <button type="button" class="btn btn-danger delete-btn" data-module="catalog" data-id="' . $product->product_id . '"> <i class="fas fa-fw fa-trash"></i></button>'
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

    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product.*.product_sku' => 'required|string',
            'product.*.product_title' => 'required|string',
            'product.*.product_price' => 'required|decimal:2',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $products = $request->input('product');

        foreach ($products as $proData) {
            Catalog::where('product_id', $proData['product_id'])->update([
                'product_sku' => $proData['product_sku'],
                'product_title' => $proData['product_title'],
                'product_price' => $proData['product_price'],
            ]);
        }

        return redirect()->back()->with('success', 'Products updated successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //session()->forget('pro_id');

        if (!session()->has('pro_id')) {
            $record = Catalog::create(['product_status' => 'draft']);
            session(['pro_id' => $record->product_id]);
            $pro_id = $record->product_id;
        } else {
            $pro_id = session('pro_id');
        }
        $pageAt = 'Add';
        $product = Catalog::findOrFail($pro_id); // Retrieve the product by ID
        $keyFeatures = ProductKeyFeatures::where('product_id', $pro_id)->orderBy('sort_order')->get();
        $searchTerms = ProductSearchTerm::where('product_id', $pro_id)->orderBy('sort_order')->get();
        $titleKeywords = ProductTitleKeyword::where('product_id', $pro_id)->orderBy('priority')->get();
        $productImages = ProductImages::where('product_id', $pro_id)->get();
        $productAltImages = ProductImageAlternative::where('product_id', $pro_id)->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::with('storeType')->get();

        $store_details = [];
        if (count($stores ?? []) > 0) {
            foreach ($stores as $key => $store) {
                Log::info('store_type_id: ' . $store->store_type_id);
                $store_details[$key]['store_detail'] = $store;
                $storeCategories = StoreTypeCategory::select('store_type_category.*')
                    ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                    ->where('store_type.id', $store->store_type_id)
                    ->get();
                $store_details[$key]['store_category'] = $storeCategories;
            }
        }
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::with('children')->where('parent_id', NULL)->get();
        $productConditions = ProductCondition::all();
        $interfaces = Interfaces::all();
        $productInterfaces = ProductInterface::where('product_id', $pro_id)->get();
        $productInterfaces = $productInterfaces->pluck('interface_id')->values()->all();
        $targetAudience = ProductTargetAudience::all();
        $storeTypes = StoreType::all();
        $colors = ProductColor::all();
        $specifications = ProductSpecification::where('product_id', $pro_id)->orderBy('created_at')->get();
        $specificationInfo = ProductSpecificationInfo::all();
        $shippingCarrierPackage = ShippingPackage::orderBy('name')->get();
        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id', 'product_channel_detail.status', 'store_type.name')
            ->leftJoin('store_type', 'store_type.id', '=', 'product_channel_detail.store_type_id')
            ->where('product_id', $pro_id)->get();
        return view('catalog.update', compact('product', 'keyFeatures', 'searchTerms', 'productImages', 'productTags', 'pageAt', 'store_details', 'manufacturers', 'productTypes', 'productConditions', 'titleKeywords', 'interfaces', 'productInterfaces', 'targetAudience', 'storeTypes', 'productStoreType', 'colors', 'productAltImages', 'specifications', 'specificationInfo', 'shippingCarrierPackage'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_title' => 'required|string',
            'product_type' => 'required|string',
            'manufacturer_id' => 'required|integer',
            'product_price' => 'required|decimal:2',
            'product_sku' => 'required|string|max:225',
            'product_description' => 'nullable|string'
        ]);

        // save the data to the database
        try {

            Catalog::create($request->all());

            return redirect()->route('catalog.list')->with('success', 'Product created successfully!');

        } catch (QueryException $e) {
            // Handle the exception and show an error message
            Log::error('Error saving user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create product. Please try again.');
        }

        // redirect to the brands list

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = $request->input('product_id');
        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        try {
            $product = Catalog::findOrFail($id);
            $product->update($request->all());
            return response()->json(['success' => true, 'message' => 'Data updated successfully!']);

        } catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Data updated successfully!']);
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
        $pageAt = 'Edit';
        $product = Catalog::findOrFail($id); // Retrieve the product by ID
        $keyFeatures = ProductKeyFeatures::where('product_id', $id)->orderBy('sort_order')->get();
        $searchTerms = ProductSearchTerm::where('product_id', $id)->orderBy('sort_order')->get();
        $titleKeywords = ProductTitleKeyword::where('product_id', $id)->orderBy('priority')->get();
        $productImages = ProductImages::where('product_id', $id)->orderBy('sort_order', 'asc')->get();
        $productAltImages = ProductImageAlternative::where('product_id', $id)->orderBy('sort_order', 'asc')->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::with('storeType')->get();

        $store_details = [];
        if (count($stores ?? []) > 0) {
            foreach ($stores as $key => $store) {
                Log::info('store_type_id: ' . $store->store_type_id);
                /* check store active for product */
//                $prochannel = DB::table('product_channel_detail')->where('store_type_id',$store->store_type_id)->where('product_id',$id)->first();
//                if((!empty($prochannel) && $prochannel->status)) {
                $store_details[$key]['store_detail'] = $store;
                $store_details[$key]['store_product'] = StoreProduct::where('product_id', $id)->where('store_id', $store->id)->first();
                $storeCategories = StoreTypeCategory::select('store_type_category.*')
                    ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                    ->where('store_type.id', $store->store_type_id)
                    ->get();
                $store_details[$key]['store_category'] = $storeCategories;
//                }
            }
        }
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::with('children')->where('parent_id', NULL)->get();
        $productConditions = ProductCondition::all();
        $storeProducts = StoreProduct::where('product_id', $id)->get();
        $productStore = $storeProducts->pluck('store_id', 'store_type_category_id')->all();
        $productStoreCategory = $storeProducts->pluck('store_type_category_id')->values()->all();
        $interfaces = Interfaces::all();
        $productInterfaces = ProductInterface::where('product_id', $id)->get();
        $productInterfaces = $productInterfaces->pluck('interface_id')->values()->all();
        $targetAudience = ProductTargetAudience::all();
        $storeTypes = StoreType::orderBy('name')->get();
        $colors = ProductColor::all();
        $specifications = ProductSpecification::where('product_id', $id)->orderBy('created_at')->get();
        //$specificationInfo = ProductSpecificationInfo::where('product_type_id',$product->product_type)->get();
        $shippingCarrierPackage = ShippingPackage::orderBy('name')->get();
        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id', 'product_channel_detail.status', 'store_type.name')
            ->leftJoin('store_type', 'store_type.id', '=', 'product_channel_detail.store_type_id')
            ->where('product_id', $id)->get();

        return view('catalog.update', compact('product', 'keyFeatures', 'searchTerms', 'productImages', 'productTags', 'pageAt', 'store_details', 'manufacturers', 'productTypes', 'productConditions', 'productStore', 'productStoreCategory', 'titleKeywords', 'interfaces', 'productInterfaces', 'targetAudience', 'storeTypes', 'productStoreType', 'colors', 'productAltImages', 'specifications', 'shippingCarrierPackage'));
    }

    /**
     * Save the records from different tab.
     */
    public function saveTabData(Request $request)
    {
        $productId = $request->input('product_id');
        $tabId = $request->input('tab');
        $request->except('product_id', 'tab');

        Log::info("$request->sdoc_enable");

        // Logic to handle saving data based on $tabId
        if ($tabId == 1) {


            $validator = Validator::make(request()->all(), [
                'product_title' => 'required|string',
                'product_type' => 'required|string',
                'manufacturer_id' => 'required|string',
                'product_price' => 'required|decimal:2',
                'product_sku' => 'required|string|max:225',
                'product_description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Catalog::findOrFail($productId); // Retrieve the product by ID
            $product->update($request->all());
            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);


        } elseif ($tabId == 2) {

            try {
                $product = Catalog::findOrFail($productId); // Retrieve the product by ID
                $product->update($request->all());
                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            } catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        } elseif ($tabId == 3) {

        } elseif ($tabId == 4) {

            $data = [];
            for ($i = 0; $i < count($request->interfaces); $i++) {
                $data[] = array(
                    'id' => (string)Str::uuid(),
                    'product_id' => $productId,
                    'interface_id' => $request->interfaces[$i]
                );
            }

            ProductInterface::where('product_id', $productId)->delete();
            ProductInterface::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

        } elseif ($tabId == 5) {

            try {
                $data = [];
                for ($i = 0; $i < count($request->features); $i++) {
                    $data[] = array(
                        'id' => (string)Str::uuid(),
                        'product_id' => $productId,
                        'value' => $request->features[$i],
                        'sort_order' => $request->feature_sort_order[$i],
                    );
                }
                ProductKeyFeatures::where('product_id', $productId)->delete();
                ProductKeyFeatures::insert($data);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            } catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        } elseif ($tabId == 6) {
            $data = [];
            for ($i = 0; $i < count($request->search_term); $i++) {
                $data[] = array(
                    'id' => (string)Str::uuid(),
                    'product_id' => $productId,
                    'term' => $request->search_term[$i],
                    'sort_order' => $request->search_sort_order[$i],
                );
            }
            ProductSearchTerm::where('product_id', $productId)->delete();
            ProductSearchTerm::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        } elseif ($tabId == 8) {
            $data = [];
            for ($i = 0; $i < count($request->tags); $i++) {
                $data[] = array(
                    'id' => (string)Str::uuid(),
                    'product_id' => $productId,
                    'name' => $request->tags[$i]
                );
            }
            //ProductTags::where('product_id', $productId)->delete();
            ProductTags::upsert($data, ['product_id', 'name'], ['name']);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        } elseif ($tabId == 9) {
            unset($request['tab']);
            $request['overwrite_title'] = $request->has('overwrite_title') ? 1 : 0;
            $request['update_title'] = $request->has('update_title') ? 1 : 0;
            $request['overwrite_description'] = $request->has('overwrite_description') ? 1 : 0;
            $request['update_description'] = $request->has('update_description') ? 1 : 0;
            $request['overwrite_price'] = $request->has('overwrite_price') ? 1 : 0;
            $request['overwrite_compare_at_price'] = $request->has('overwrite_compare_at_price') ? 1 : 0;
            $request['overwrite_fulfillment_by_channel_price'] = $request->has('overwrite_fulfillment_by_channel_price') ? 1 : 0;
            $request['overwrite_wholesale_price'] = $request->has('overwrite_wholesale_price') ? 1 : 0;
            $request['overwrite_msrp'] = $request->has('overwrite_msrp') ? 1 : 0;

            $created_by = auth()->user()->name ?? 'admin';
            $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

            $product = StoreProduct::where(['product_id' => $productId, 'store_id' => $request->input('store_id')])->first();
            $productArr = isset($product) ? $product->toArray() : [];
            //print_r($productArr);die;
            if (!empty($productArr)) {
                $product->update($request->all());
            } else {
                StoreProduct::create($request->all());
            }

            /*StoreProduct::upsert($request->input(), ['product_id','store_id'], ['store_category_id','title','active','price','msrp','description','sales_count','overwrite_compare_at_price','wholesale_price','compare_at_price','overwrite_wholesale_price','overwrite_description','overwrite_title','overwrite_msrp','overwrite_price','created_by','updated_by']);*/

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
            /*echo '<pre>';
            print_r($request->input());
            echo '</pre>';exit;*/
        } elseif ($tabId == 11) {
            try {
                $product = Catalog::findOrFail($productId);
                $product->update($request->all());

                $data = [];
                for ($i = 0; $i < count($request->title_keyword); $i++) {
                    $data[] = array(
                        'id' => (string)Str::uuid(),
                        'product_id' => $productId,
                        'keyword' => $request->title_keyword[$i],
                        'priority' => $request->title_priority[$i],
                    );
                }
                ProductTitleKeyword::where('product_id', $productId)->delete();
                ProductTitleKeyword::insert($data);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!!']);

            } catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        } elseif ($tabId == 12) {

            try {
                $data = [];
                for ($i = 0; $i < count($request->specification ?? []); $i++) {
                    $data[] = array(
                        'id' => (string)Str::uuid(),
                        'product_id' => $productId,
                        'name' => $request->specification[$i],
                        'value' => $request->specification_value[$i],
                        'store_type_id' => $request->specification_store_type_id
                    );
                }
                ProductSpecification::where(['product_id' => $productId, 'store_type_id' => $request->specification_store_type_id])->delete();
                ProductSpecification::insert($data);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            } catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }
    }

    /**
     * Submit the records from different tab.
     */
    public function finalize(Request $request)
    {
        $productId = $request->input('product_id');

        $validator = Validator::make(request()->all(), [
            'product_title' => 'required|string|max:30',
            'product_type' => 'required|string',
            'manufacturer_id' => 'required|string',
            'product_price' => 'required|decimal:2',
            'product_sku' => 'required|string|max:225',
            'product_description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Catalog::findOrFail($productId); // Retrieve the product by ID
        $product->update($request->all());

        session()->flash('success', 'Product added successfully!');
        return response()->json(['success' => true, 'message' => 'Data saved successfully']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {

        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()->route('catalog.list')->with('error', 'Password is incorrect');
        }

        // Find the user by id
        $product = Catalog::findOrFail($id);

        if ($product) {
            $product->delete(); // Delete the product
            return redirect()->route('catalog.list')->with('success', 'Product deleted successfully.');

            // Return a success response
            //session()->flash('success', 'Product deleted successfully!');
            //return response()->json(['success' => 'Product deleted successfully!']);
        }
    }

    /**
     * Upload product images.
     */
    public function uploadImage(Request $request)
    {
        $productId = $request->input('product_id');
        // Validate the request
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,JPG,PNG,JPEG|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $imageNames = [];
        $thumbnailNames = [];
        $imagedata = [];
        // Store the uploaded image and create a thumbnail
        if ($request->file('images')) {

            foreach ($request->file('images') as $key => $image) {
                $extension = $image->getClientOriginalExtension();
                $imageName = time() . '_' . uniqid() . '.' . $extension;
                $storagePath = public_path('storage/product_images/' . $imageName);
                /*$image->move(public_path('storage/product_images/'), $imageName);*/
                $image->move(public_path('storage/product_images/'), $imageName);

                $fileSize = filesize($storagePath);
                $filedata = [
                    'name' => $imageName,
                    'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                    'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                    'location' => 'product_images/',
                    'created_by' => 'admin',
                ];

                $imageFile = SysFile::create($filedata);

                $imageNames[] = $imageName;
                $imagedata[] = array(
                    'id' => (string)Str::uuid(),
                    'product_id' => $productId,
                    'image' => $imageFile->id,
                    //'thumbnail_image' => $imageName,
                    'sort_order' => (int)$key + 1,
                );

                if ($key == 0) {
                    Catalog::where('product_id', $productId)->update(['product_image_url' => $imageFile->id]);
                }

                // Create thumbnail
                /*$thumbnailPath = public_path('storage/product_images/thumbnails');
                if (!file_exists($thumbnailPath)) {
                    mkdir($thumbnailPath, 0755, true);
                }

                $thumbnailImage = Image::make(public_path('storage/product_images/thumbnails' . $imageName));
                $thumbnailImage->resize(150, 150); // Set thumbnail size
                $thumbnailImage->save($thumbnailPath . '/' . $imageName);
                $thumbnailNames[] = 'thumbnails/' . $imageName;*/
            }
            //print_r($imagedata);die;
            $images = ProductImages::upsert($imagedata, ['product_id', 'image'], ['image']);

            return response()->json([
                'success' => 'Image uploaded successfully!',
                'image_name' => $imageName,
                //'thumbnail_name' => 'thumbnails/' . $imageName,
            ]);
        }

        return response()->json(['error' => 'Image upload failed.'], 500);
    }

    /* set default image*/
    public function setDefaultImage(Request $request)
    {

        $imageId = $request->input('image_id');
        $productId = $request->input('product_id');

        try {

            ProductImages::where('product_id', $productId)->update(['main_image' => 0]);
            ProductImages::where('id', $imageId)->update(['main_image' => 1]);

            $imageDetail = ProductImages::find($imageId);
            Catalog::where('product_id', $productId)->update(['product_image_url' => $imageDetail->image]);

            return response()->json(['success' => true, 'message' => 'Image set default successfully']);

        } catch (QueryException $e) {

            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /* set image sort order*/
    public function setImageOrder(Request $request)
    {
        $imageId = $request->input('image_id');
        $sort_order = $request->input('sort_order');

        try {

            ProductImages::where('id', $imageId)->update(['sort_order' => $sort_order]);
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        } catch (QueryException $e) {

            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    public function updateImageOrder(Request $request)
    {
        $order = $request->order;

        try {
            foreach ($order as $position => $id) {
                ProductImages::where('id', $id)->update(['sort_order' => ($position + 1)]);
            }
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        } catch (QueryException $e) {

            return response()->json(['errors' => $e->getMessage()], 422);
        }

    }

    public function getStoreDetail(Request $request)
    {
        $store_id = $request->input('store_id');
        $product_id = $request->input('product_id');
        if ($store_id) {

            $storeDetail = Store::with('storeType')->where('id', $store_id)->first();

            $storeProductDetail = StoreProduct::with('store','store.storeType')->where(['store_id' => $store_id, 'product_id' => $product_id])->first();

            if($storeProductDetail){
                if(!$storeProductDetail->title && (isset($storeDetail->storeType->code) && $storeDetail->storeType->code == 'shopify')){
                    $product_title = $this->generateProductTitle($storeProductDetail,$storeDetail);
                    $storeProductDetail->title = $product_title;
                }
            }

            if (!$storeProductDetail) {
                $storeProductDetail = Catalog::where(['product_id' => $product_id])->first();

                if((isset($storeDetail->storeType->code) && $storeDetail->storeType->code == 'shopify')){
                    $product_title = $this->generateProductTitle($storeProductDetail,$storeDetail);
                    $storeProductDetail->title = $product_title;
                }
            }

            //print_r($storeProductDetail);die;
            return response()->json(['success' => true, 'data' => $storeProductDetail]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    private function generateProductTitle($productDetail,$store){

        $limit = $store->random_title_keyword_level;
        $reverse = true;
        if (!isset($limit)) {
            $limit = 6;
            $reverse = false;
        } else {
            $limit++;
        }

        $keywords = ProductTitleKeyword::select('product_id', 'keyword')
            ->where('product_id', $productDetail->product_id)
            ->orderBy('priority', 'asc')->limit($limit)->get()->toArray();

        Log::info('Keywords: ' . json_encode($keywords));
        // take the first one and reverse the remaining array

        $title = '';
        if (count($keywords) > 0) {
            $firstKeyword = array_shift($keywords);
            $title .= $firstKeyword['keyword'];
            if ($reverse) {
                $reversedKeywords = array_reverse($keywords);
                $title .= ', ';
            } else {
                $title .= ' ';
                $reversedKeywords = $keywords;
            }
            foreach ($reversedKeywords as $keyword) {
                $title .= trim($keyword['keyword']) . ' ';
            }
        } else {
            $title = $productDetail->product_title;
        }
        return trim($title) . ' - ' . $store->short_code . $productDetail->product_sku;
    }

    public function getProductChannel(Request $request)
    {
        $product_channel_id = $request->input('id');

        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id', 'product_channel_detail.store_type_id', 'product_channel_detail.status', 'store_type.name')
            ->leftJoin('store_type', 'store_type.id', '=', 'product_channel_detail.store_type_id')
            ->where('product_channel_detail.id', $product_channel_id)->first();

        return response()->json(['success' => true, 'data' => $productStoreType]);

    }

    public function saveProductChannel(Request $request)
    {

        try {
            $store_type_id = $request->input('store_type_id');
            $product_id = $request->input('product_id');
            $status = $request->input('status');
            ProductChannelDetail::updateOrCreate([
                'store_type_id' => $store_type_id,
                'product_id' => $product_id,
            ],[
                'store_type_id' => $store_type_id,
                'product_id' => $product_id,
                'status' => $status
            ]);

            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        } catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    public function deleteProductChannel(Request $request)
    {

        $product_channel_id = $request->input('id');
        DB::table('product_channel_detail')->where('id', $product_channel_id)->delete();
        return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
    }

    public function getImages(Request $request)
    {

        $product_id = $request->productId;
        $pimages = [];
        if ($product_id) {
            $images = ProductImages::where('product_id', $product_id)->orderBy('sort_order', 'asc')->get();
            if (!empty((array)$images)) {
                foreach ($images as $img) {
                    if ($img->imageObj) {
                        $pimages[] = asset('storage/' . $img->imageObj->getFileUrl());
                    }
                }
            }
        }
        return response()->json($pimages);
    }

    public function deleteImage(Request $request)
    {
        $image_id = $request->image_id;

        if ($image_id) {
            $productImg = ProductImages::findOrFail($image_id);
            $productImg->delete();

            // Return a success response
            return response()->json(['success' => 'Image deleted successfully!']);
        }

        // If the user doesn't exist, return an error
        return response()->json(['error' => 'Image not found!'], 404);
    }

    /**
     * Upload product images.
     */
    public function uploadAlternativeImage(Request $request)
    {
        $productId = $request->input('product_id');
        // Validate the request
        $validator = Validator::make($request->all(), [
            'alternate_images.*' => 'required|image|mimes:jpeg,png,jpg,gif,JPG,PNG,JPEG|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $imageNames = [];
        $thumbnailNames = [];
        $imagedata = [];
        // Store the uploaded image and create a thumbnail
        if ($request->file('alternate_images')) {

            foreach ($request->file('alternate_images') as $key => $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('storage/product_additional_images/'), $imageName);
                $imageNames[] = $imageName;
                $imagedata[] = array(
                    'id' => (string)Str::uuid(),
                    'product_id' => $productId,
                    'image' => $imageName,
                    //'thumbnail_image' => $imageName,
                    'sort_order' => (int)$key + 1,
                    'version_number' => (int)$key + 1,
                );

                // Create thumbnail
                /*$thumbnailPath = public_path('storage/product_images/thumbnails');
                if (!file_exists($thumbnailPath)) {
                    mkdir($thumbnailPath, 0755, true);
                }

                $thumbnailImage = Image::make(public_path('storage/product_images/thumbnails' . $imageName));
                $thumbnailImage->resize(150, 150); // Set thumbnail size
                $thumbnailImage->save($thumbnailPath . '/' . $imageName);
                $thumbnailNames[] = 'thumbnails/' . $imageName;*/
            }
            //print_r($imagedata);die;
            $images = ProductImageAlternative::upsert($imagedata, ['product_id', 'image'], ['image']);

            return response()->json([
                'success' => 'Image uploaded successfully!',
                'image_name' => $imageName,
                //'thumbnail_name' => 'thumbnails/' . $imageName,
            ]);
        }

        return response()->json(['error' => 'Image upload failed.'], 500);
    }

    public function deleteAlternativeImage(Request $request)
    {
        $image_id = $request->image_id;

        if ($image_id) {
            $productImg = ProductImageAlternative::findOrFail($image_id);
            $productImg->delete();

            // Return a success response
            return response()->json(['success' => 'Image deleted successfully!']);
        }

        // If the user doesn't exist, return an error
        return response()->json(['error' => 'Image not found!'], 404);
    }

    public function updateAlternativeImageOrder(Request $request)
    {
        $order = $request->order;

        try {
            foreach ($order as $position => $id) {
                ProductImageAlternative::where('id', $id)->update(['sort_order' => ($position + 1)]);
            }
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        } catch (QueryException $e) {

            return response()->json(['errors' => $e->getMessage()], 422);
        }

    }

    public function getSpecification(Request $request)
    {

        $store_type_id = $request->input('store_type_id');
        $product_id = $request->input('product_id');
        $categoryId = $request->input('category_id');
        if (isset($storetype) && in_array($storetype->code, ['temu', 'amazon', 'walmart'])) {
            $storeIds = Store::where('store_type_id', $store_type_id)->pluck('id')->toArray();
            $firstStoreProduct = StoreProduct::where('product_id', $product_id)
                ->whereNotNull('store_type_category_id')
                ->whereIn('store_id', $storeIds)
                ->first();

            if (!isset($firstStoreProduct)) {
                return response()->json([
                    'html' => '<p>No category found for the selected store type. Please select category in stores tab</p>',
                ]);
            }
            $categoryId = $firstStoreProduct->store_type_category_id;
        }

        $specifications = ProductSpecification::where('store_type_id', $store_type_id)->where('product_id', $product_id)->orderBy('created_at', 'asc')->get();
        /*echo '<pre>';
        print_r($specifications);
        echo '</pre>';die;*/
        $specificationArr = [];
        if (!empty($specifications)) {
            foreach ($specifications as $key => $spec) {
                $specificationArr[$key] = $spec;
                $field_option = ProductSpecifcationUniqueInfo::where('field_name', $spec->name)->first();
                $field = $valoption = '';
                if (!empty($field_option)) {
                    $field = 'select';
                    $valoption = ListingUtility::getSpecValues($field_option, $categoryId, $store_type_id);
                }
                $specificationArr[$key]->field = $field;
                $specificationArr[$key]->valoption = $valoption;
            }
        }

        //$specificationArr = (object)$specificationArr;
        /*echo '<pre>';
        print_r((object)$specificationArr);
        echo '</pre>';die;*/


        $storetype = StoreType::where('id', $store_type_id)->first();
        if (isset($storetype) && in_array($storetype->code, ['temu', 'amazon', 'walmart'])) {
            Log::info('No category for store type code: ' . $storetype->code);
            $category_id = null;
        } else {
            $info_category = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'cea');
            })->orderBy('name', 'asc')->get();
            $info_category = $info_category->pluck('id')->values()->all();

            if (!isset($categoryId) && $categoryId == '') {
                $product_detail = Catalog::findOrFail($product_id);
                $category = ProductType::with('ancestors')->find($product_detail->product_type);
                $category_id = $this->getAncestors($category, $info_category);
            } else {
                $category = ProductType::with('ancestors')->find($categoryId);
                $category_id = $this->getAncestors($category, $info_category);
            }
        }
        Log::info('categoryId: ' . $category_id);


        $specificationInfo = ProductSpecificationInfo::where('product_specification_info.store_type_id', $store_type_id)
            ->when(isset($category_id) && !empty($category_id), function ($query) use ($category_id) {
                Log::info('category_id in when: ' . $category_id);
                $query->join('product_specification_info_category_link', 'product_specification_info_category_link.specification_info_id', '=', 'product_specification_info.id');
                $query->where('product_specification_info_category_link.product_type_id', $category_id);
            })
            ->orderBy('product_specification_info.field_local_name')->get();

        return response()->json([
            'html' => view('catalog.specification', compact('specificationInfo', 'specificationArr'))->render(),
        ]);

    }

    public function getRequiredSpecification(Request $request)
    {
        $store_type_id = $request->input('store_type_id');
        $product_id = $request->input('product_id');
        $categoryId = $request->input('category_id');
        $specifications = $request->input('specifications', []);

        if (!isset($specifications)) {
            $specifications = [];
        }

        $categoryCode = null;
        $category_id = null;

        $storetype = StoreType::where('id', $store_type_id)->first();
        if (isset($storetype) && in_array($storetype->code, ['temu', 'walmart', 'amazon'])) {
            $storeIds = Store::where('store_type_id', $store_type_id)->pluck('id')->toArray();
            $firstStoreProduct = StoreProduct::where('product_id', $product_id)
                ->whereNotNull('store_type_category_id')
                ->whereIn('store_id', $storeIds)
                ->first();

            if (!isset($firstStoreProduct)) {
                return response()->json([
                    'html' => '',
                    'message' => '<p>No category found for the selected store type. Please select category in stores tab</p>',
                ]);
            }
            $categoryId = $firstStoreProduct->store_type_category_id;
            $category_id = null;

            $storeCategory = StoreTypeCategory::where('id', $categoryId)->first();
            if (isset($storeCategory) && in_array($storetype->code, ['temu', 'walmart'])) {
                $categoryCode = $storeCategory->code;
            } else if (isset($storeCategory) && $storetype->code == 'amazon') {
                $categoryCode = $storeCategory->marketplace_code;
            }
        } else {
            return response()->json([
                'html' => '',
                'message' => 'No specification required for this store type.',
            ]);
        }

        $requirements = ListingRequirement::where('type', 'key_value')
            ->where('store_type_id', $store_type_id)
            ->when(isset($categoryCode), function ($query) use ($categoryCode) {
                $query->where('category', $categoryCode);
            })
            ->pluck('key_value')->toArray();

        $specificationArr = [];
        if (!empty($requirements)) {
            foreach ($requirements as $key => $spec) {
                Log::info('Spec: ' . $spec);
                if (in_array($spec, $specifications)) {
                    continue;
                }
                $specificationArr[$key] = (object)['name' => $spec];
                $field_option = ProductSpecifcationUniqueInfo::where('field_name', $spec)->first();
                $field = $valoption = '';
                if (!empty($field_option)) {
                    $field = 'select';
                    $valoption = ListingUtility::getSpecValues($field_option, $categoryId, $store_type_id);
                }
                $specificationArr[$key]->field = $field;
                $specificationArr[$key]->valoption = $valoption;
            }
        }

        if (empty($specificationArr)) {
            return response()->json([
                'html' => '',
                'message' => 'No additional specification required for this category.',
            ]);
        }

        $specificationInfo = ProductSpecificationInfo::where('product_specification_info.store_type_id', $store_type_id)
            ->when(isset($category_id) && !empty($category_id), function ($query) use ($category_id) {
                Log::info('category_id in when: ' . $category_id);
                $query->join('product_specification_info_category_link', 'product_specification_info_category_link.specification_info_id', '=', 'product_specification_info.id');
                $query->where('product_specification_info_category_link.product_type_id', $category_id);
            })
            ->orderBy('product_specification_info.field_local_name')->get();

        Log::info('Specification Info: ' . json_encode($specificationInfo));

        return response()->json([
            'html' => view('catalog.specification_options', compact('specificationInfo', 'specificationArr'))->render(),
        ]);
    }

    private function getAncestors($category, $info_category)
    {
        if (isset($category) && $category->parent) {
            //echo $category->parent->name . "\n";
            if (in_array($category->parent->id, $info_category)) {
                return $category->parent->id;
            }
            $this->getAncestors($category->parent, $info_category);
        } else {
            return null;
        }
    }

    public function getSpecificationOption(Request $request)
    {
        $store_type_id = $request->input('store_type_id');
        $product_id = $request->input('product_id');
        $categoryId = $request->input('category_id');

        $info_category = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        $info_category = $info_category->pluck('id')->values()->all();

        if (!isset($categoryId) && $categoryId == '') {
            $product_detail = Catalog::findOrFail($product_id);
            $category = ProductType::with('ancestors')->find($product_detail->product_type);
            $category_id = $this->getAncestors($category, $info_category);
        } else {
            $category = ProductType::with('ancestors')->find($categoryId);
            $category_id = $this->getAncestors($category, $info_category);
        }
        $storetype = StoreType::where('id', $store_type_id)->first();
        if (isset($storetype) && in_array($storetype->code, ['temu', 'walmart'])) {
            Log::info('No category for store type code: ' . $storetype->code);
            $category_id = null;
        }
        Log::info('categoryId: ' . $category_id);

        $specificationInfo = ProductSpecificationInfo::where('product_specification_info.store_type_id', $store_type_id)
            ->when(isset($category_id) && !empty($category_id), function ($query) use ($category_id) {
                Log::info('category_id in when: ' . $category_id);
                $query->join('product_specification_info_category_link', 'product_specification_info_category_link.specification_info_id', '=', 'product_specification_info.id');
                $query->where('product_specification_info_category_link.product_type_id', $category_id);
            })
            ->orderBy('product_specification_info.field_local_name')->get();

        $option = '<option value=""></option>';
        $field = $valoption = '';
        if (!empty($specificationInfo)) {
            foreach ($specificationInfo as $spec) {
                $option .= '<option value="' . $spec->field_local_name . '">' . $spec->field_local_name . '</option>';

                $field_option = ProductSpecifcationUniqueInfo::where('field_name', $spec->name)->first();

                if (!empty($field_option)) {
                    $field = 'select';
                    $optionvalueArr = ListingUtility::getSpecValues($field_option, $categoryId, $store_type_id);

                    if (!empty($optionvalueArr)) {
                        $valoption = '<option value=""></option>';
                        foreach ($optionvalueArr as $val) {
                            $valoption .= '<option value="' . trim($val->id) . '">' . trim($val->field_value) . '</option>';
                        }
                    }
                } else {
                    $field = 'input';
                    $valoption = '';
                }

                /*$field_option = ProductSpecificationOption::where('field_local_name',$spec->field_local_name)->first();
                $field = $valoption = '';
                if(!empty($field_option)){
                    $optionvalue = $field_option->field_option;
                    if(str_contains($optionvalue,',')){
                        $field = 'select';
                        $optionvalueArr = explode(',',$optionvalue);
                        if(!empty($optionvalueArr)){
                            $valoption = '<option value=""></option>';
                            foreach($optionvalueArr as $val){
                                $valoption .= '<option value="'.trim($val).'">'.trim($val).'</option>';
                            }
                        }

                    }else{
                        $field = 'input';
                        $valoption = $optionvalue;
                    }
                }*/
            }
        }

        return response()->json([
            'option' => $option,
            'valueoption' => $valoption,
            'field' => $field
        ]);
    }

    public function getFieldOption(Request $request)
    {

        $field_name = $request->input('field_name');
        $product_id = $request->input('product_id');
        $store_type_id = $request->input('store_type_id');
        $categoryId = null;
        if (isset($storetype) && in_array($storetype->code, ['temu', 'walmart', 'amazon'])) {
            $storeIds = Store::where('store_type_id', $store_type_id)->pluck('id')->toArray();
            $firstStoreProduct = StoreProduct::where('product_id', $product_id)
                ->whereNotNull('store_type_category_id')
                ->whereIn('store_id', $storeIds)
                ->first();

            if (!isset($firstStoreProduct)) {
                return response()->json([
                    'html' => '',
                    'message' => '<p>No category found for the selected store type. Please select category in stores tab</p>',
                ]);
            }
            $categoryId = $firstStoreProduct->store_type_category_id;
        }
        $field = $option = '';
        $firstField = ProductSpecifcationUniqueInfo::where('field_name', $field_name)->first();
        if (isset($firstField)) {
            $field_options = ListingUtility::getSpecValues($firstField, $categoryId, $store_type_id);

            if (!empty($field_options)) {
                $field = 'select';
                $option = '<option value=""></option>';
                foreach ($field_options as $key => $field_option) {
                    $option .= '<option value="' . trim($field_option->id) . '">' . trim($field_option->field_value) . '</option>';
                }
            }

            return response()->json([
                'option' => $option,
                'field' => $field
            ]);
        } else {
            return response()->json([
                'option' => '',
                'field' => ''
            ]);
        }
    }

    public function getProductInterface(Request $request)
    {

        $product_type = $request->input('product_type');
        if (!empty($product_type)) {

            $productTypes = ProductType::select('id')->where('id', function ($subquery) use ($product_type) {
                $subquery->select('parent_id')->from('product_type')->where('id', $product_type)->whereNotNull('parent_id');
            })->get()->toArray();

            /*echo '<pre>';
            print_r($productTypes);
            echo '</pre>';die;*/


            $option = '<option value="">--</option>';
            $interface = Interfaces::select('id', 'name')->whereIn('product_type_id', $productTypes)->orderBy('name', 'asc')->get()->toArray();
            if (!empty($interface)) {
                foreach ($interface as $iface) {
                    $option .= '<option value="' . $iface['id'] . '">' . $iface['name'] . '</option>';
                }
            }

            return response()->json([
                'success' => true,
                'option' => $option
            ]);

        } else {
            return response()->json([
                'success' => false,
                'option' => []
            ]);
        }
    }

    public function updateProductSpecification(Request $request)
    {
        $productIds = $request->input('product_ids');
        $categoryIds = $request->input('category_ids');
        $specifications = $request->input('specifications');
        $specification_values = $request->input('specifications_values');
        $store_id = $request->input('store_id');

        //echo $store_id;die;

        $store = Store::with('storeType')->where('id', $store_id)->first();

        try {
            $data = [];
            for ($i = 0; $i < count($specifications); $i++) {
                if ($productIds) {
                    foreach ($productIds as $key => $productId) {

                        $category_id = $categoryIds[$key];

                        $currentCategory = StoreTypeCategory::where('id', $category_id)->first();
                        if ('Amazon' == $store->storeType->name) {
                            $issues = ListingUtility::validate($currentCategory->marketplace_code, $productId, $store->storeType->id);
                        } else {
                            $issues = ListingUtility::validate($currentCategory->store_type_code, $productId, $store->storeType->id);
                        }

                        if ($issues) {
                            foreach ($issues as $issue) {
                                if (isset($issue['spec']['field']) && $issue['spec']['field'] == $specifications[$i]) {
                                    $exists = ProductSpecification::where(['product_id' => $productId, 'name' => $specifications[$i], 'store_type_id' => $store->storeType->id])->exists();
                                    if (!$exists && $specification_values[$i] != '') {
                                        $data[] = array(
                                            'id' => (string)Str::uuid(),
                                            'product_id' => $productId,
                                            'name' => $specifications[$i],
                                            'value' => $specification_values[$i],
                                            'store_type_id' => $store->storeType->id ?? null
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            /*echo '<pre>';
            print_r($data);
            echo '</pre>';die;*/
            if ($data) {
                ProductSpecification::insert($data);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()]);
        }
    }

    public function updateMarketPlace(Request $request)
    {
        // store_id , $product_id
        $store_id = $request->input('store_id');
        $product_id = $request->input('product_id');

        $productData = Catalog::select('*')->with('productType', 'productKeyFeatures', 'productColor')->where('product_id', $product_id)->first();
        $storeData = Store::with('storeType')->where('id', $store_id)->first();
        $submitJob = new SubmitShopifyProductsJob();
        $submitJob->getPublications($storeData);
        $submitJob->updateProduct($productData, $storeData);
        return response()->json(['success' => true]);
    }
}
