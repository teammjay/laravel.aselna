<?php

namespace App\Http\Controllers;

use App\Models\StoreTypeListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalmartExportController extends Controller
{
    public function exportWalmartOrdersCsv()
    {
        $sixMonthsAgo = now()->subMonths(6);

        // Grouping by SKU only as requested. 
        // We use MAX() for other columns to satisfy MySQL's ONLY_FULL_GROUP_BY mode.
        $results = DB::table('order_item as oi')
            ->join('order as o', 'o.id', '=', 'oi.order_id')
            ->join('store as s', 's.id', '=', 'o.store_id')
            ->join('store_type as st', 'st.id', '=', 's.store_type_id')
            ->leftJoin('listing_item as li', 'li.sku', '=', 'oi.sku')
            ->leftJoin('store_product as sp', 'sp.id', '=', 'li.store_product_id')
            ->leftJoin('store_type_listing as stl', 'stl.id', '=', 'li.store_type_listing_id')
            ->where('st.code', 'walmart')
            ->where('o.order_date', '>=', $sixMonthsAgo)
            ->where(function($query) {
                $query->whereNull('sp.store_id')
                      ->orWhereRaw('sp.store_id = o.store_id');
            })
            ->select(
                'oi.sku',
                DB::raw('MAX(li.store_sku) as walmartItemId'),
                DB::raw('MAX(COALESCE(li.upc, stl.upc)) as upc'),
                DB::raw('MAX(oi.item_price) as Price'),
                DB::raw('SUM(oi.quantity) as items_sold')
            )
            ->groupBy('oi.sku')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="walmart_orders_last_6_months.csv"',
        ];

        $callback = function () use ($results) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SKU', 'walmartItemId', 'UPC', 'Price', 'Number of Items Sold', 'New SKU']);

            $skuCache = [];

            foreach ($results as $row) {
                $newSku = StoreTypeListing::generateUniqueAlphabeticSku($row->sku, 6, $skuCache);
                
                fputcsv($file, [
                    $row->sku,
                    $row->walmartItemId ?? 'N/A',
                    $row->upc ?? 'N/A',
                    $row->Price,
                    $row->items_sold,
                    $newSku
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
