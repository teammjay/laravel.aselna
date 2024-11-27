<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceSeries;
use App\Models\DeviceTypes;
use App\Models\Manufacturer;
use App\Models\ProductType;
use App\Models\StoreProduct;
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
        $stores = Store::all();

        return view('listing.list',compact('stores'))->with('perPage', $this->perPage);
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
        $stores = Store::all();
        $manufacturers = Manufacturer::all();
        $productTypes = ProductType::whereNull('parent_id')->get();
        $deviceTypes = DeviceTypes::all();
        $brands = Brand::all();
        return view('listing.create', compact('pageAt','stores','manufacturers','productTypes','deviceTypes','brands'));
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

        switch ($type){

            case 'product_code':
                $product = Catalog::select('product_id','product_sku')->WhereLike('product_sku', '%'.$term.'%')->get();
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
                $deviceSeries = DeviceSeries::select('id','name')->WhereLike('name', '%'.$term.'%')->get();
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
                $devices = Device::select('id','name')->WhereLike('name', '%'.$term.'%')->get();
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
        if(!empty($product_ids)){
            $query = StoreProduct::select('store_product.product_id','store_product.title','store_product.price','store_product.created_at','product_type.name as ProductType')->with('Product');
            $query->leftJoin('products','products.product_id', '=', 'store_product.product_id');
            $query->leftJoin('product_type','products.product_type', '=', 'product_type.id');
            $query->whereIn('store_product.id', $product_ids);
            $products = $query->get();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('listing.selected_product_list', compact('products'))->render()
                ]);
            }

        }else{
            return response()->json([
                'success' => false
            ]);
        }
    }
}
