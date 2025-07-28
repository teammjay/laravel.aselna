<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\ManageMarketplaceProduct;
use App\Models\Store;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $totalRecords = ManageMarketplaceProduct::query()->count();

        $query->with(['store', 'store.storeType']);

        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            Log::info("Sorting by column: $columnName, direction: $sortDirection");
            if ($columnName == 'code') {
                $query->orderBy('product_code', $sortDirection);
            } else {
                $query->orderBy($columnName, $sortDirection);
            }
        });

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('sku'), function ($query) use ($request) {
                    QueryFilters::applyFilter($query, $request, 'sku', 'sku_operator', 'sku');
                });
        });


        $dataQuery = $dataTableService->handle();

        $data = [];

        foreach ($dataQuery->items() as $product) {
            $data[] = [
                'id' => $product->id,
                'store_name' => $product->store->name . ' - ' . ($product->store->storeType->name ?? ''),
                'sku' => $product->sku,
                'code' => $product->product_code,
                'product_id' => $product->product_id,
                'upload_status' => $product->upload_status,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $response = array(
            "draw" => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $dataQuery->total(),
            "aaData" => $data
        );

        return $response;
    }
}
