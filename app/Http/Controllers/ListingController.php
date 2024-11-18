<?php

namespace App\Http\Controllers;

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
        //session()->forget('pro_id');
        if (!session()->has('pro_id')) {
            $record = Catalog::create(['product_status' => 'draft']);
            session(['pro_id' => $record->product_id]);
            $pro_id = $record->product_id;
        }else{
            $pro_id = session('pro_id');
        }
        //return view('catalog.update', ['product_id' => $record->product_id]);
        $pageAt = 'Add';
        $product = Catalog::findOrFail($pro_id); // Retrieve the product by ID
        $keyFeatures = ProductKeyFeatures::where('product_id', $pro_id)->orderBy('sort_order')->get();
        $titleKeywords = ProductTitleKeyword::where('product_id', $pro_id)->orderBy('priority')->get();
        $productImages = ProductImages::where('product_id', $pro_id)->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::all();
        return view('listing.update', compact('product','keyFeatures','titleKeywords','productImages','productTags','pageAt','stores'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageAt = 'Edit';
        $product = Catalog::findOrFail($id); // Retrieve the product by ID
        $keyFeatures = ProductKeyFeatures::where('product_id', $id)->orderBy('sort_order')->get();
        $titleKeywords = ProductTitleKeyword::where('product_id', $id)->orderBy('priority')->get();
        $productImages = ProductImages::where('product_id', $id)->get();
        $productTags = ProductTags::pluck('name')->values()->all();
        $stores = Store::all();
        //echo '<pre>';
        //print_r($productTags);die;
        return view('listing.update', compact('product','keyFeatures','titleKeywords','productImages','productTags','pageAt','stores'));
    }
}
