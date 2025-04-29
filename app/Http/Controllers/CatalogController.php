<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\Catalog;
use App\Models\Interfaces;
use App\Models\Manufacturer;
use App\Models\ProductColor;
use App\Models\ProductCondition;
use App\Models\ProductInterface;
use App\Models\ProductKeyFeatures;
use App\Models\ProductSearchTerm;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationOption;
use App\Models\ProductTags;
use App\Models\ProductTargetAudience;
use App\Models\ProductTitleKeyword;
use App\Models\ProductImages;
use App\Models\ProductType;
use App\Models\ProductImageAlternative;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Models\SysFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class CatalogController extends Controller
{

    private $perPage = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        session()->forget('pro_id');
        $productTypes = ProductType::orderBy('name')->get();
        $manufacturers = Manufacturer::orderBy('name')->get();
        $colors = ProductColor::orderBy('name')->get();
        $productConditions = ProductCondition::all();
        $targetAudience = ProductTargetAudience::all();
        $perPage = $this->perPage;

        return view('catalog.list',compact('perPage','productTypes','manufacturers','productConditions','targetAudience','colors'));
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

        $query = Catalog::with('Manufacturer','productType')->select('*');
        /*$perPage = $request->input('perPage', $this->perPage);*/

        // Filter by product title
        QueryFilters::applyFilter($query, $request, 'product_title', 'name_operator', 'name');

        // Filter by product title
        QueryFilters::applyFilter($query, $request, 'product_sku', 'product_sku_operator', 'product_sku');

        // Filter by product type
        if($request->has('product_type') && $request->product_type != '') {
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
                $query->whereIn('product_type', $all_product_ids);
            }
        }
        //QueryFilters::applyFilter($query, $request, 'product_type', 'product_type_operator', 'product_type');

        // Filter by color
        QueryFilters::applyFilter($query, $request, 'product_color', 'product_color_operator', 'product_color');

        // Filter by configurable
        if ($request->has('configurable') && $request->configurable != '') {
            if($request->configurable) {
                $query->where('configurable', 0);
                $query->orWhere('configurable', NULL);
            }
        }

        // Filter by manufacturer
        if ($request->has('manufacturer_id') && $request->manufacturer_id != '') {
            $query->where('manufacturer_id', $request->manufacturer_id);
        }

        // Filter by product condition
        if ($request->has('product_condition') && $request->product_condition != '') {
            $query->where('product_condition_id', $request->product_condition);
        }

        // Filter by product_weight
        if ($request->has('product_weight') && $request->product_weight != '') {
            $query->where('product_weight', $request->product_weight);
        }

        // Filter by product_weight
        if ($request->has('package_weight') && $request->package_weight != '') {
            $query->where('package_weight', $request->package_weight);
        }

        // Filter by target audience
        if ($request->has('target_aud') && $request->target_aud != '') {
            $query->where('target_aud', $request->target_aud);
        }

        // Filter by dimension width
        if ($request->has('dimensions_width') && $request->dimensions_width != '') {
            $query->where('dimensions_width', $request->dimensions_width);
        }

        // Filter by dimension height
        if ($request->has('dimensions_height') && $request->dimensions_height != '') {
            $query->where('dimensions_height', $request->dimensions_height);
        }
        // Filter by dimension length
        if ($request->has('dimensions_length') && $request->dimensions_length != '') {
            $query->where('dimensions_length', $request->dimensions_length);
        }

        // Filter by package dimension width
        if ($request->has('package_dimensions_width') && $request->package_dimensions_width != '') {
            $query->where('package_dimensions_width', $request->package_dimensions_width);
        }

        // Filter by package dimension height
        if ($request->has('package_dimensions_height') && $request->package_dimensions_height != '') {
            $query->where('package_dimensions_height', $request->package_dimensions_height);
        }
        // Filter by package dimension length
        if ($request->has('package_dimensions_length') && $request->package_dimensions_length != '') {
            $query->where('package_dimensions_length', $request->package_dimensions_length);
        }

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->where('created_at', $request->created_at);
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

        if($columnName != ''){
            switch(strtolower($columnName)){
                case 'product_id':
                    $query->orderBy('product_id', $columnSortOrder);
                    break;
                case 'product_sku':
                    $query->orderBy('product_sku', $columnSortOrder);
                    break;
                case 'product_type':
                    $query->orderBy('product_type', $columnSortOrder);
                    break;
                case 'product_title':
                    $query->orderBy('product_title', $columnSortOrder);
                    break;
                case 'product_status':
                    $query->orderBy('product_status', $columnSortOrder);
                    break;
                case 'configurable':
                    $query->orderBy('configurable', $columnSortOrder);
                    break;
                case 'product_price':
                    $query->orderBy('product_price', $columnSortOrder);
                    break;
                case 'manufacture_name':
                    $query->orderBy('manufacturer_id', $columnSortOrder);
                    break;
                case 'product_color':
                    $query->orderBy('product_color', $columnSortOrder);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $columnSortOrder);
                    break;

            }
        }

        $query->where('oem_generic',2);
        ///echo $query->toSql();die;
        /*$productsRecord = $query->paginate($perPage); // Paginate results
        $products = $query->skip($start)->take($perPage)->get(); // Paginate results
        $totalRecordwithFilter = $productsRecord->total();*/
        $totalRecords = $query->count();
        $products = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if(!empty((array) $products)){
            foreach($products as $key=>$product) {
                if($product->configurable == 1){
                    $checked = 'checked';
                }else{
                    $checked = '';
                }

                $data[] = array(
                    "product_id" => $product->product_id.'<input type="checkbox" class="pro-id" name="product['.$key.'][product_id]" value="'.$product->product_id.'" style="visibility:hidden;">',
                    "product_image_url" => ($product->image) ? '<div class="main-image"><img src="'.asset('storage/' .$product->image->getFileUrl()).'" alt="'.$product->image.'" class="mainImage" width="70" height="70" data-product-id="'.$product->product_id.'"></div>' : '',
                    "product_sku" => '<span class="prVal">'.$product->product_sku.'</span><input type="text" class="form-control bulk-edit" name="product['.$key.'][product_sku]" value="'.$product->product_sku.'" disabled>',
                    "product_type" => $product->productType->name,
                    "product_title" => '<span class="prVal">'.$product->product_title.'</span><textarea class="form-control bulk-edit" name="product['.$key.'][product_title]" rows="3" disabled>'.$product->product_title.'</textarea>',
                    "product_status" =>  '<span class="prVal">'.$product->product_status.'</span><select class="form-control bulk-edit" name="product['.$key.'][product_status]" disabled>
                        <option value="active"> Active </option>
                        <option value="discontinued"> Discontinued </option>
                        <option value="out_of_stock"> Out of stock </option></select>' ,
                    "configurable" => '<input type="checkbox" class="configurable" data-id="'.$product->product_id.'" '.$checked.'>',
                    "product_price" => '<span class="prVal">'.$product->product_price.'</span><input type="text" class="form-control bulk-edit" name="product['.$key.'][product_price]" value="'.$product->product_price.'" disabled>',
                    "manufacture_name" => isset($product->Manufacturer->name) ? $product->Manufacturer->name : '',
                    "product_color" => isset($product->color) ? $product->color->name : '',
                    "created_at" => date('Y-m-d H:i:s',strtotime($product->created_at)),
                    "action" => '<a href="'.route('catalog.edit', $product->product_id).'" class="btn btn-primary">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                                </a>
                                <button class="btn btn-danger delete-btn" data-id="'.$product->product_id.'"> <i class="fas fa-fw fa-trash"></i></button>'
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
        }else{
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
        if(count($stores ?? []) > 0){
            foreach($stores as $key=>$store){
                Log::info('store_type_id: '.$store->store_type_id);
                $store_details[$key]['store_detail'] = $store;
                $storeCategories = StoreTypeCategory::select('store_type_category.*')
                    ->leftJoin('store_type','store_type.id', '=', 'store_type_category.store_type_id')
                    ->where('store_type.id',$store->store_type_id)
                    ->get();
                $store_details[$key]['store_category'] = $storeCategories;
            }
        }
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::with('children')->where('parent_id',NULL)->get();
        $productConditions = ProductCondition::all();
        $interfaces = Interfaces::all();
        $productInterfaces = ProductInterface::where('product_id', $pro_id)->get();
        $productInterfaces = $productInterfaces->pluck('interface_id')->values()->all();
        $targetAudience = ProductTargetAudience::all();
        $storeTypes = StoreType::all();
        $colors = ProductColor::all();
        $specifications = ProductSpecification::where('product_id', $pro_id)->orderBy('created_at')->get();
        $specificationInfo = ProductSpecificationInfo::all();
        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id', 'product_channel_detail.status','store_type.name')
            ->leftJoin('store_type','store_type.id','=','product_channel_detail.store_type_id')
            ->where('product_id',$pro_id)->get();
        return view('catalog.update', compact('product','keyFeatures','searchTerms','productImages','productTags','pageAt','store_details','manufacturers','productTypes','productConditions','titleKeywords','interfaces','productInterfaces','targetAudience','storeTypes','productStoreType','colors','productAltImages','specifications','specificationInfo'));
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

        }catch (QueryException $e) {
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

        }catch (QueryException $e) {
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
        $productImages = ProductImages::where('product_id', $id)->orderBy('sort_order','asc')->get();
        $productAltImages = ProductImageAlternative::where('product_id', $id)->orderBy('sort_order','asc')->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::with('storeType')->get();

        $store_details = [];
        if(count($stores ?? []) > 0){
            foreach($stores as $key=>$store){
                Log::info('store_type_id: '.$store->store_type_id);
                /* check store active for product */
//                $prochannel = DB::table('product_channel_detail')->where('store_type_id',$store->store_type_id)->where('product_id',$id)->first();
//                if((!empty($prochannel) && $prochannel->status)) {
                    $store_details[$key]['store_detail'] = $store;
                    $storeCategories = StoreTypeCategory::select('store_type_category.*')
                        ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                        ->where('store_type.id', $store->store_type_id)
                        ->get();
                    $store_details[$key]['store_category'] = $storeCategories;
//                }
            }
        }
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::with('children')->where('parent_id',NULL)->get();
        $productConditions = ProductCondition::all();
        $storeProducts = StoreProduct::where('product_id', $id)->get();
        $productStore = $storeProducts->pluck('store_id','store_type_category_id')->all();
        $productStoreCategory = $storeProducts->pluck('store_type_category_id')->values()->all();
        $interfaces = Interfaces::all();
        $productInterfaces = ProductInterface::where('product_id', $id)->get();
        $productInterfaces = $productInterfaces->pluck('interface_id')->values()->all();
        $targetAudience = ProductTargetAudience::all();
        $storeTypes = StoreType::orderBy('name')->get();
        $colors = ProductColor::all();
        $specifications = ProductSpecification::where('product_id', $id)->orderBy('created_at')->get();
        //$specificationInfo = ProductSpecificationInfo::where('product_type_id',$product->product_type)->get();
        $productStoreType = DB::table('product_channel_detail')
                            ->select('product_channel_detail.id','product_channel_detail.status','store_type.name')
                            ->leftJoin('store_type','store_type.id','=','product_channel_detail.store_type_id')
                            ->where('product_id',$id)->get();

        return view('catalog.update', compact('product','keyFeatures','searchTerms','productImages','productTags','pageAt','store_details','manufacturers','productTypes','productConditions','productStore','productStoreCategory','titleKeywords','interfaces','productInterfaces','targetAudience','storeTypes','productStoreType','colors','productAltImages','specifications'));
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

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }elseif ($tabId == 3) {

        }elseif ($tabId == 4) {

            $data = [];
            for($i=0; $i < count($request->interfaces); $i++){
                $data[] = array(
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'interface_id' => $request->interfaces[$i]
                );
            }

            ProductInterface::where('product_id', $productId)->delete();
            ProductInterface::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

        }elseif ($tabId == 5) {

            try {
                $data = [];
                for($i=0; $i < count($request->features); $i++){
                    $data[] = array(
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'value' => $request->features[$i],
                        'sort_order' => $request->feature_sort_order[$i],
                    );
                }
                ProductKeyFeatures::where('product_id', $productId)->delete();
                ProductKeyFeatures::insert($data);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }elseif ($tabId == 6){
            $data = [];
            for($i=0; $i < count($request->search_term); $i++){
                $data[] = array(
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'term' => $request->search_term[$i],
                    'sort_order' => $request->search_sort_order[$i],
                );
            }
            ProductSearchTerm::where('product_id', $productId)->delete();
            ProductSearchTerm::insert($data);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        }elseif ($tabId == 8){
            $data = [];
            for($i=0; $i < count($request->tags); $i++){
                $data[] = array(
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'name' => $request->tags[$i]
                );
            }
            //ProductTags::where('product_id', $productId)->delete();
            ProductTags::upsert($data, ['product_id','name'], ['name']);

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
        }elseif ($tabId == 9){
            unset($request['tab']);
            $request['overwrite_title'] = $request->has('overwrite_title') ? 1 : 0;
            $request['overwrite_description'] = $request->has('overwrite_description') ? 1 : 0;
            $request['overwrite_price'] = $request->has('overwrite_price') ? 1 : 0;
            $request['overwrite_compare_at_price'] = $request->has('overwrite_compare_at_price') ? 1 : 0;
            $request['overwrite_fulfillment_by_channel_price'] = $request->has('overwrite_fulfillment_by_channel_price') ? 1 : 0;
            $request['overwrite_wholesale_price'] = $request->has('overwrite_wholesale_price') ? 1 : 0;
            $request['overwrite_msrp'] = $request->has('overwrite_msrp') ? 1 : 0;

            $created_by = auth()->user()->name ?? 'admin';
            $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

            $product = StoreProduct::where(['product_id' => $productId,'store_id' =>$request->input('store_id')])->first();
            $productArr = isset($product) ? $product->toArray() : [];
            //print_r($productArr);die;
            if(!empty($productArr)){
                $product->update($request->all());
            }else{
                StoreProduct::create($request->all());
            }

            /*StoreProduct::upsert($request->input(), ['product_id','store_id'], ['store_category_id','title','active','price','msrp','description','sales_count','overwrite_compare_at_price','wholesale_price','compare_at_price','overwrite_wholesale_price','overwrite_description','overwrite_title','overwrite_msrp','overwrite_price','created_by','updated_by']);*/

            return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
            /*echo '<pre>';
            print_r($request->input());
            echo '</pre>';exit;*/
        }elseif($tabId == 11){
            try {
                $product = Catalog::findOrFail($productId);
                $product->update($request->all());

                $data = [];
                for($i=0; $i < count($request->title_keyword); $i++){
                    $data[] = array(
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'keyword' => $request->title_keyword[$i],
                        'priority' => $request->title_priority[$i],
                    );
                }
                ProductTitleKeyword::where('product_id', $productId)->delete();
                ProductTitleKeyword::insert($data);

                return response()->json(['success' => true, 'message' => 'Data saved successfully!!']);

            }catch (QueryException $e) {
                // Handle the exception and show an error message
                return response()->json(['errors' => $e->getMessage()]);
            }
        }elseif ($tabId == 12) {

            try {
                $data = [];
                for($i=0; $i < count($request->specification); $i++){
                    $data[] = array(
                        'id' => (string) Str::uuid(),
                        'product_id' => $productId,
                        'name' => $request->specification[$i],
                        'value' => $request->specification_value[$i],
                        'store_type_id' => $request->specification_store_type_id
                    );
                }
                ProductSpecification::where(['product_id' => $productId,'store_type_id'=> $request->specification_store_type_id])->delete();
                ProductSpecification::insert($data);

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
    public function destroy(string $id)
    {
        // Find the user by id
        $product = Catalog::findOrFail($id);

        if ($product) {
//            $product->delete(); // Delete the user

            // Return a success response
            session()->flash('success', 'Product deleted successfully!');
            return response()->json(['success' => 'Product deleted successfully!']);
        }

        // If the user doesn't exist, return an error
        session()->flash('error', 'Product not found!');
        return response()->json(['error' => 'Product not found!'], 404);
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

            foreach ($request->file('images') as $key=>$image) {
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
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'image' => $imageFile->id,
                    //'thumbnail_image' => $imageName,
                    'sort_order' => (int) $key + 1,
                );

                if($key == 0){
                    Catalog::where('product_id', $productId)->update(['product_image_url'=>$imageFile->id]);
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
            $images = ProductImages::upsert($imagedata,['product_id','image'], ['image']);

            return response()->json([
                'success' => 'Image uploaded successfully!',
                'image_name' => $imageName,
                //'thumbnail_name' => 'thumbnails/' . $imageName,
            ]);
        }

        return response()->json(['error' => 'Image upload failed.'], 500);
    }

    /* set default image*/
    public function setDefaultImage(Request $request){

        $imageId = $request->input('image_id');
        $productId = $request->input('product_id');

        try {

            ProductImages::where('product_id', $productId)->update(['main_image' => 0]);
            ProductImages::where('id', $imageId)->update(['main_image' => 1]);

            $imageDetail = ProductImages::find($imageId);
            Catalog::where('product_id', $productId)->update(['product_image_url'=>$imageDetail->image]);

            return response()->json(['success' => true, 'message' => 'Image set default successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    /* set image sort order*/
    public function setImageOrder(Request $request){
        $imageId = $request->input('image_id');
        $sort_order = $request->input('sort_order');

        try {

            ProductImages::where('id', $imageId)->update(['sort_order' => $sort_order]);
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    public function updateImageOrder(Request $request)
    {
        $order = $request->order;

        try {
            foreach ($order as $position => $id) {
                ProductImages::where('id', $id)->update(['sort_order' => ($position+1)]);
            }
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }

    }

    public function getStoreDetail(Request $request){
        $store_id = $request->input('store_id');
        $product_id = $request->input('product_id');
        if($store_id){

            $storeProductDetail = StoreProduct::where(['store_id'=>$store_id,'product_id'=>$product_id])->first();

            if(!$storeProductDetail){
                $storeProductDetail =  Catalog::where(['product_id'=>$product_id])->first();
            }
            //print_r($storeProductDetail);die;
            return response()->json(['success' => true, 'data' => $storeProductDetail]);
        }else{
            return response()->json(['success' =>false]);
        }
    }

    public function getProductChannel(Request $request){
        $product_channel_id = $request->input('id');

        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id','product_channel_detail.store_type_id', 'product_channel_detail.status','store_type.name')
            ->leftJoin('store_type','store_type.id','=','product_channel_detail.store_type_id')
            ->where('product_channel_detail.id',$product_channel_id)->first();

        return response()->json(['success' => true, 'data' => $productStoreType]);

    }

    public function saveProductChannel(Request $request){

        try {
            if($request->has('id') && $request->id == ''){
              $request['id'] = (string)Str::uuid();
            }
            DB::table('product_channel_detail')->upsert($request->all(),['id'],['status']);
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            return response()->json(['errors' => $e->getMessage()], 422);
        }
    }

    public function deleteProductChannel(Request $request){

        $product_channel_id = $request->input('id');
        DB::table('product_channel_detail')->where('id', $product_channel_id)->delete();
        return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
    }

    public function getImages(Request $request){

        $product_id = $request->productId;
        $pimages = [];
        if($product_id){
            $images = ProductImages::where('product_id',$product_id)->orderBy('sort_order','asc')->get();
            if(!empty((array) $images)){
                foreach($images as $img){
                    if($img->imageObj) {
                        $pimages[] = asset('storage/' .$img->imageObj->getFileUrl());
                    }
                }
            }
        }
        return response()->json($pimages);
    }

    public function deleteImage(Request $request){
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

            foreach ($request->file('alternate_images') as $key=>$image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('storage/product_additional_images/'), $imageName);
                $imageNames[] = $imageName;
                $imagedata[] = array(
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'image' => $imageName,
                    //'thumbnail_image' => $imageName,
                    'sort_order' => (int) $key + 1,
                    'version_number' => (int) $key + 1,
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
            $images = ProductImageAlternative::upsert($imagedata,['product_id','image'], ['image']);

            return response()->json([
                'success' => 'Image uploaded successfully!',
                'image_name' => $imageName,
                //'thumbnail_name' => 'thumbnails/' . $imageName,
            ]);
        }

        return response()->json(['error' => 'Image upload failed.'], 500);
    }

    public function deleteAlternativeImage(Request $request){
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
                ProductImageAlternative::where('id', $id)->update(['sort_order' => ($position+1)]);
            }
            return response()->json(['success' => true, 'message' => 'Set order  successfully']);

        }catch(QueryException $e){

            return response()->json(['errors' => $e->getMessage()], 422);
        }

    }

    public function getSpecification(Request $request){

        $store_type_id = $request->input('store_type_id');
        $product_id = $request->input('product_id');
        $specifications = ProductSpecification::where('store_type_id',$store_type_id)->where('product_id',$product_id)->orderBy('created_at','asc')->get();
        /*echo '<pre>';
        print_r($specifications);
        echo '</pre>';die;*/
        $specificationArr = [];
        if(!empty($specifications)){
            foreach($specifications as $key=>$spec){

                $specificationArr[$key]= $spec;

                $field_option = ProductSpecificationOption::where('field_local_name',$spec->name)->first();
                $field = $valoption = '';
                if(!empty($field_option)){
                    $optionvalue = $field_option->field_option;
                    if(str_contains($optionvalue,',')){
                        $field = 'select';
                        $optionvalueArr = explode(',',$optionvalue);
                        $valoption = $optionvalueArr;

                    }else{
                        $field = 'input';
                        $valoption = $optionvalue;
                    }
                }

                $specificationArr[$key]->field = $field;
                $specificationArr[$key]->valoption = $valoption;
            }
        }

        //$specificationArr = (object)$specificationArr;
        /*echo '<pre>';
        print_r((object)$specificationArr);
        echo '</pre>';die;*/
        $specificationInfo = ProductSpecificationInfo::where('store_type_id',$store_type_id)->orderBy('field_local_name')->get();
        return response()->json([
            'html' => view('catalog.specification', compact('specificationInfo','specificationArr'))->render(),
        ]);

    }

    public function getSpecificationOption(Request $request){
        $store_type_id = $request->input('store_type_id');
        $specificationInfo = ProductSpecificationInfo::where('store_type_id',$store_type_id)->get();
        $option = '<option value=""></option>';
        if(!empty($specificationInfo)){
            foreach($specificationInfo as $spec){
                $option .= '<option value="'.$spec->field_local_name.'">'.$spec->field_local_name.'</option>';

                $field_option = ProductSpecificationOption::where('field_local_name',$spec->field_local_name)->first();
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
                }
            }
        }

        return response()->json([
            'option' => $option,
            'valueoption' => $valoption,
            'field' => $field
        ]);
    }

    public function getFieldOption(Request $request){

        $field_name = $request->input('field_name');
        $field = $option = '';
        if($field_name != ''){
            $field_option = ProductSpecificationOption::where('field_local_name',$field_name)->first();
            if(!empty($field_option)){
                $optionvalue = $field_option->field_option;
                if(str_contains($optionvalue,',')){
                    $field = 'select';
                    $optionvalueArr = explode(',',$optionvalue);
                    if(!empty($optionvalueArr)){
                        $option = '<option value=""></option>';
                        foreach($optionvalueArr as $val){
                            $option .= '<option value="'.trim($val).'">'.trim($val).'</option>';
                        }
                    }

                }else{
                    $field = 'input';
                    $option = $optionvalue;
                }
            }

            return response()->json([
                'option' => $option,
                'field' => $field
            ]);
        }
    }
}
