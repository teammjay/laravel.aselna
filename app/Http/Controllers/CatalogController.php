<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Interfaces;
use App\Models\Manufacturer;
use App\Models\ProductCondition;
use App\Models\ProductInterface;
use App\Models\ProductKeyFeatures;
use App\Models\ProductSearchTerm;
use App\Models\ProductTags;
use App\Models\ProductTargetAudience;
use App\Models\ProductTitleKeyword;
use App\Models\ProductImages;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use function Illuminate\Database\Query\leftJoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use function Symfony\Component\HttpKernel\Profiler\find;
use Illuminate\Support\Facades\DB;


class CatalogController extends Controller
{

    private $perPage = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the data in the pagination format for datatable to display
        //sku must contain only 3 characters
        session()->forget('pro_id');
        $products = Catalog::with('Manufacturer')->select('*')
            ->where('oem_generic',2)
            ->orderBy('created_at','desc')
            ->paginate($this->perPage);
        $productTypes = ProductType::all();
        $manufacturers = Manufacturer::all();
        $productConditions = ProductCondition::all();
        $targetAudience = ProductTargetAudience::all();
        $perPage = $this->perPage;

        return view('catalog.list',compact('products','perPage','productTypes','manufacturers','productConditions','targetAudience'));
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
            $operator = $request->name_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('product_title', '%'.$request->name.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('product_title', $request->name.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('product_title', '%'.$request->name);
                    break;
                case '=':
                    $query->Where('product_title', $request->name);
                    break;
                case 'in':
                    $names = explode(',',$request->name);
                    $query->WhereIn('product_title',$names);
                    break;
                case 'not_in':
                    $names = explode(',',$request->name);
                    $query->WhereNotIn('product_title', $names);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('product_title', '%'.$request->name.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('product_title');
                    break;
            }
        }

        // Filter by product title
        if ($request->has('product_sku') && $request->product_sku != '') {
            $operator = $request->product_sku_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('product_sku', '%'.$request->product_sku.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('product_sku', $request->product_sku.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('product_sku', '%'.$request->product_sku);
                    break;
                case '=':
                    $query->Where('product_sku', $request->product_sku);
                    break;
                case 'in':
                    $skus = explode(',',$request->product_sku);
                    $query->WhereIn('product_sku',$skus);
                    break;
                case 'not_in':
                    $skus = explode(',',$request->product_sku);
                    $query->WhereNotIn('product_sku', $skus);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('product_sku', '%'.$request->product_sku.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('product_sku');
                    break;
            }
        }

        // Filter by product type
        if ($request->has('product_type') && $request->product_type != '') {
            $operator = $request->product_type_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('product_type', '%'.$request->product_type.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('product_type', $request->product_type.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('product_type', '%'.$request->product_type);
                    break;
                case '=':
                    $query->Where('product_type', $request->product_type);
                    break;
                case 'in':
                    $productTypes = explode(',',$request->product_type);
                    $query->WhereIn('product_type',$productTypes);
                    break;
                case 'not_in':
                    $productTypes = explode(',',$request->product_type);
                    $query->WhereNotIn('product_type', $productTypes);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('product_type', '%'.$request->product_type.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('product_type');
                    break;
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

        // Filter by color
        if ($request->has('product_color') && $request->product_color != '') {
            $operator = $request->product_color_operator;
            switch($operator){
                case 'contains':
                    $query->WhereLike('product_color', '%'.$request->product_color.'%');
                    break;
                case 'starts_with':
                    $query->WhereLike('product_color', $request->product_color.'%');
                    break;
                case 'ends_with':
                    $query->WhereLike('product_color', '%'.$request->product_color);
                    break;
                case '=':
                    $query->Where('product_color', $request->product_color);
                    break;
                case 'in':
                    $productColors = explode(',',$request->product_color);
                    $query->WhereIn('product_color',$productColors);
                    break;
                case 'not_in':
                    $productColors = explode(',',$request->product_color);
                    $query->WhereNotIn('product_color', $productColors);
                    break;
                case 'doesnt_contain':
                    $query->WhereNotLike('product_color', '%'.$request->product_color.'%');
                    break;
                case 'is_set':
                    $query->whereNotNull('product_color');
                    break;
            }

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
        $query->orderBy('created_at','desc');
        $products = $query->paginate($perPage); // Paginate results

        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('catalog.product_list', compact('products'))->render(),
                'pagination' => view('catalog.pagination', compact('products'))->render(),
            ]);
        }

        return view('products.index', compact('products'));
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
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::with('storeType')->get();

        $store_details = [];
        if(count($stores ?? []) > 0){
            foreach($stores as $key=>$store){
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
        $productStoreType = DB::table('product_channel_detail')
            ->select('product_channel_detail.id', 'product_channel_detail.status','store_type.name')
            ->leftJoin('store_type','store_type.id','=','product_channel_detail.store_type_id')
            ->where('product_id',$pro_id)->get();
        return view('catalog.update', compact('product','keyFeatures','searchTerms','productImages','productTags','pageAt','store_details','manufacturers','productTypes','productConditions','titleKeywords','interfaces','productInterfaces','targetAudience','storeTypes','productStoreType'));
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
        $productImages = ProductImages::where('product_id', $id)->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::with('storeType')->get();

        $store_details = [];
        if(count($stores ?? []) > 0){
            foreach($stores as $key=>$store){
                /* check store active for product */
                $prochannel = DB::table('product_channel_detail')->where('store_type_id',$store->store_type_id)->where('product_id',$id)->first();
                if((!empty($prochannel) && $prochannel->status)) {
                    $store_details[$key]['store_detail'] = $store;
                    $storeCategories = StoreTypeCategory::select('store_type_category.*')
                        ->leftJoin('store_type', 'store_type.id', '=', 'store_type_category.store_type_id')
                        ->where('store_type.id', $store->store_type_id)
                        ->get();
                    $store_details[$key]['store_category'] = $storeCategories;
                }
            }
        }
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::with('children')->where('parent_id',NULL)->get();
        $productConditions = ProductCondition::all();
        $storeProducts = StoreProduct::where('product_id', $id)->get();
        $productStore = $storeProducts->pluck('store_id','store_type_category_id')->all();
        /*echo '<pre>';
        print_r($productStore);
        echo '</pre>';die;*/
        $productStoreCategory = $storeProducts->pluck('store_type_category_id')->values()->all();
        $interfaces = Interfaces::all();
        $productInterfaces = ProductInterface::where('product_id', $id)->get();
        $productInterfaces = $productInterfaces->pluck('interface_id')->values()->all();
        $targetAudience = ProductTargetAudience::all();
        $storeTypes = StoreType::all();
        $productStoreType = DB::table('product_channel_detail')
                            ->select('product_channel_detail.id','product_channel_detail.status','store_type.name')
                            ->leftJoin('store_type','store_type.id','=','product_channel_detail.store_type_id')
                            ->where('product_id',$id)->get();

        return view('catalog.update', compact('product','keyFeatures','searchTerms','productImages','productTags','pageAt','store_details','manufacturers','productTypes','productConditions','productStore','productStoreCategory','titleKeywords','interfaces','productInterfaces','targetAudience','storeTypes','productStoreType'));
    }

    /**
     * Save the records from different tab.
     */
    public function saveTabData(Request $request)
    {
        $productId = $request->input('product_id');
        $tabId = $request->input('tab');
        $request->except('product_id', 'tab');

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
            $productArr = $product->toArray();
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
            'manufacturer_id' => 'required|integer',
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
            $product->delete(); // Delete the user

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
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $imageNames[] = $imageName;
                $imagedata[] = array(
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'image' => $imageName,
                    'sort_order' => (int) $key + 1,
                );

                if($key == 0){
                    Catalog::where('product_id', $productId)->update(['product_image_url'=>$imageName]);
                }

                // Create thumbnail
                /*$thumbnailPath = public_path('images/thumbnails');
                if (!file_exists($thumbnailPath)) {
                    mkdir($thumbnailPath, 0755, true);
                }

                $thumbnailImage = Image::make(public_path('images/' . $imageName));
                $thumbnailImage->resize(150, 150); // Set thumbnail size
                $thumbnailImage->save($thumbnailPath . '/' . $imageName);
                $thumbnailNames[] = 'thumbnails/' . $imageName;*/
            }
            //print_r($imagedata);die;
            $iamges = ProductImages::upsert($imagedata,['product_id','image'], ['image']);

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
}
