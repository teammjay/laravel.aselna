<?php

namespace App\Helpers;

use App\Models\Catalog;
use App\Models\ScheduledJobs;
use App\Models\StoreScheduledCronJobs;
use App\Models\StoreType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Common
{
    public static function calculateProductWeight($orderOperation){

        $product_total_weight = 0;
        if (count($orderOperation->operationItem ?? []) > 0) {
            $product_weight = 0;
            foreach ($orderOperation->operationItem as $oitem) {
                if (isset($oitem->orderItem->product)) {
                    $product_id = $oitem->orderItem->product->product_id;
                    if ($product_id) {
                        $product_detail = Catalog::findOrFail($product_id);
                        $product_weight = $product_detail->product_weight * $oitem->quantity;
                        $product_total_weight = $product_total_weight + $product_weight;
                    }
                }
            }
        }
        return $product_total_weight;
    }

    public static function updateListingItemSaleDetail($sku, $itemDetail){

        try {
            $salesData = DB::table('order_item')
                ->select(
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
                ->where('sku', $sku)
                ->first();

            Log::info('sales data:'.json_encode($salesData));

            if($salesData) {
                $saleCount = (int)($salesData->saleCount ?? 0);
                $lastSaleDate = $salesData->lastSaleDate;

                Log::info('Update sales count:'.$sku);
                Log::info('sales count itemdetail:'.json_encode($itemDetail));

                /*$itemDetail->update([
                    'sales_count' => $saleCount,
                    'last_sale_date' => $lastSaleDate,
                ]);*/
                if($itemDetail) {
                    $itemDetail->sales_count = $saleCount;
                    $itemDetail->last_sale_date = $lastSaleDate;
                    $itemDetail->save();
                }

                return true;
            }

            return false;
        }catch (\Exception $e){

            Log::error('Error in Update Listing Item Sales Detail ('.$sku.'): ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }

        return false;
    }

    public static function getActiveStoreJobs($job_name, $storeType=''){

        if($job_name) {
            $scheduleJob = ScheduledJobs::where('name', $job_name)->first();

            if ($scheduleJob) {
                $query = StoreScheduledCronJobs::select('store_id')->where('scheduled_job_id', $scheduleJob->id);
                if($storeType){
                    $query->where('store_type_id', function ($subquery) use ($storeType) {
                        return $subquery->select('id')
                            ->from('store_type')
                            ->where('code', $storeType)->get();
                    });
                }
                $store_ids = $query->get()->pluck('store_id')->toArray();

                return $store_ids;
            }
        }
        return false;
    }
}
