<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class UpdateListingItemSalesSeeder extends Seeder
{
    public function run(): void
    {
        $totalUpdated = 0;

        DB::table('order_item')
            ->select(
                'sku',
                DB::raw('
                    SUM(COALESCE(shipped_quantity, 0)
                        - COALESCE(canceled_quantity, 0)
                        - COALESCE(refund_quantity, 0)) AS saleCount
                '),
                DB::raw('
                    MAX(CASE
                        WHEN shipped_quantity IS NOT NULL
                             AND (canceled_quantity IS NULL OR canceled_quantity = 0)
                             AND (refund_quantity IS NULL OR refund_quantity = 0)
                        THEN created_at
                    END) AS lastSaleDate
                ')
            )
            ->whereNotNull('sku')
            ->groupBy('sku')
            ->orderBy('sku')
            ->chunk(1000, function ($salesChunk) use (&$totalUpdated) {
                foreach ($salesChunk as $row) {
                    $sku  = $row->sku;
                    $count = $row->saleCount ?? 0;
                    $date  = $row->lastSaleDate;

                    $listing = DB::table('listing_item')
                        ->where('sku', $sku)
                        ->select('id')
                        ->first();

                    if ($listing) {
                        DB::table('listing_item')
                            ->where('id', $listing->id)
                            ->update([
                                'sales_count'    => $count,
                                'last_sale_date' => $date,
                            ]);

                        $totalUpdated++;
                        continue;
                    }

                    $detail = DB::table('listing_item_detail')
                        ->where('sku', $sku)
                        ->select('listing_item_id')
                        ->first();

                    if ($detail && $detail->listing_item_id) {
                        DB::table('listing_item')
                            ->where('id', $detail->listing_item_id)
                            ->update([
                                'sales_count'    => $count,
                                'last_sale_date' => $date,
                            ]);

                        $totalUpdated++;
                    }
                }
            });
    }
}
