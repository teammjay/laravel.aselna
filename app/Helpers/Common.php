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

    /**
     * Store ids mapped to the active cron job for $job_name (store_scheduled_cron_jobs).
     *
     * @param  string  $job_name  Fully-qualified job class (exact match) or its short class name.
     */
    public static function getActiveStoreJobs($job_name, $storeType=''){

        if($job_name) {
            if (str_contains($job_name, '\\')) {
                $scheduleJob = ScheduledJobs::where('class_name', ltrim($job_name, '\\'))->where('is_active', 1)->first();
            } else {
                // Short name: match the class basename exactly, not any class containing the name
                $scheduleJob = ScheduledJobs::whereLike('class_name', '%'.$job_name)->where('is_active', 1)->get()
                    ->first(fn ($row) => class_basename($row->class_name) === $job_name);
            }

            if ($scheduleJob) {
                $query = StoreScheduledCronJobs::select('store_id')->where('scheduled_job_id', $scheduleJob->id);
                if($storeType){
                    $query->whereIn('store_type_id', function ($subquery) use ($storeType) {
                        return $subquery->select('id')
                            ->from('store_type')
                            ->where('code', $storeType);
                    });
                }
                $store_ids = $query->get()->pluck('store_id')->toArray();

                return $store_ids;
            }
        }
        return false;
    }

    public static function splitEmailBody($messagebody)
    {

        // Remove Gmail quoted replies
        $body = preg_replace('/<div class="gmail_quote.*?<\/div>/is', '', $messagebody);
        $body = preg_replace('/<blockquote class="gmail_quote.*?<\/blockquote>/is', '', $body);

        // Remove images / tracking pixels
        $body = preg_replace('/<img[^>]+>/i', '', $body);

        $body = html_entity_decode($body);
        $body = strip_tags($body);
        $body = preg_replace("/\r\n|\r/", "\n", $body);

        //echo $body;die;

        $patterns = [
            '/On\s+[A-Z][a-z]{2,8}\s+\d{1,2},\s+\d{4},\s+at\s+\d{1,2}:\d{2}\s?(AM|PM),\s+.+?wrote:/i',
            '/On\s+[A-Z][a-z]{2,8}\s+\d{1,2},\s+\d{4}.+?wrote:/is',
            '/On\s(?<day>Mon|Tue|Wed|Thu|Fri|Sat|Sun),\s(?<month>Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s(?<date>\d{1,2}),\s(?<year>\d{4})/i',
            '/\bOn\s.+?\swrote:/i',
            '/^From:\s.+/mi',
            '/^Sent:\s.+/mi',
            '/^-----Original Message-----/mi',
            '/^-------- Original message --------/mi',
            '/^_{5,}$/m',
            '/^\>+/m',
        ];

        $positions = [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE)) {
                $positions[] = $match[0][1];
            }
        }

        if (!empty($positions)) {
            $position = min($positions);
            $message = trim(substr($body, 0, $position));

            $message = self::removeSignatures($message);

            return [
                'message' => trim($message),
                'history' => trim(substr($body, $position)),
            ];
        }

        $message = self::removeSignatures($messagebody);
        return [
            'message' => trim($message),
            'history' => null,
        ];
    }

    private static function removeSignatures($text)
    {
        $signaturePatterns = [
            '/Sent from my iPhone/i',
            '/Sent from my iPad/i',
            '/Sent from my Android/i',
            '/Sent from my Galaxy/i',
            '/Get Outlook for iOS/i',
            '/Sent from Mail for Windows/i',
            '/Sent from Yahoo Mail/i',
            '/Sent from my T-Mobile 5G Device/i',
        ];

        return preg_replace($signaturePatterns, '', $text);
    }

}
