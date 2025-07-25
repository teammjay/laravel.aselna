<?php

namespace App\Http\Controllers;

use App\Models\ManageMarketplaceProduct;
use App\Models\Store;
use Illuminate\Http\Request;

class MarketPlaceProductsController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('marketplace_products.index', compact('stores'));
    }

    public function filter(Request $request)
    {
        $draw = $request['draw'];

        $query = ManageMarketplaceProduct::query();
        $query->with(['store', 'store.storeType']);

        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }


        if ($request->has('sort_by') && $request->sort_by != '') {
            $sortBy = $request->sort_by;
            $query->orderBy($sortBy, 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $totalRecords = $query->count();
        $products = $query->paginate(10);

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->id,
                'store_name' => $product->store->name . ' - ' . ($product->store->storeType->name ?? ''),
                'sku' => $product->sku,
                'upload_status' => $product->upload_status,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecords,
            "aaData" => $data
        );

        return $response;
    }
}
