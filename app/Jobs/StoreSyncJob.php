<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\StoreLogisticCenter;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class StoreSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Stores']);

        try {
            $stores = DB::connection('fonus_db')->table('tbl_seller')->get();
            foreach ($stores as $store) {
//            $this->createStore($store);
            }

            //check if connection exists or not
            if (DB::connection('easyesale_db')->getDatabaseName()) {

                $allStores = DB::connection('easyesale_db')->table('store')->cursor();
                foreach ($allStores as $store) {
                    $this->createStoreFromEasyEsale($store);
                }

                $storeLogistics = DB::connection('easyesale_db')->table('store_logistic_center')->cursor();
                foreach ($storeLogistics as $storeLogistic) {
                    $this->createStoreLogistics($storeLogistic);
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Stores Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    private function createStore(mixed $store)
    {
//        if (Store::where(''where))
//        Store::create([
//            'id' => (string)Str::uuid(),
//            'name' => $store->seller,
//        ]);
    }

    private function createStoreFromEasyEsale(mixed $store)
    {
//        if (Store::where(['id' => $store->id])->exists()) {
//            return;
//        }
//        $storeType = StoreType::where(['code' => $store->store_type_id])->first();
        Store::updateOrCreate([
            'id' => $store->id,
        ],[
            'id' => $store->id,
            'code' => $store->id,
            'name' => $store->name,
            'store_type_id' => $store->store_type_id,
            // 'api_key' => $store->api_key,
            // 'password' => $store->password,
            // 'secret_key' => $store->secret_key,
            // 'app_name' => $store->app_name,
            // 'app_version' => $store->app_version,
            // 'service_url' => $store->service_url,
            'app_id' => $store->app_id,
            'dev_id' => $store->dev_id,
            'certificate' => $store->certificate,
            'authorized' => $store->authorized,
            'seller_id' => $store->seller_id,
            'marketplace_name' => $store->marketplace_name,
            'store_status' => $store->store_status,
        //    'listing_type' => $store->listing_type,
            'brand_name' => $store->brand_name,
            'user_id' => $store->user_id,
            // 'file_system_id' => $store->image_file_system_id,
//             'sku_generation_type' => $store->sku_generation_type,
//             'fulfilment_node_id' => $store->fulfilment_node_id,
            'listing_template_id' => $store->listing_template_id,
//             'default_brand_id' => $store->default_brand_id,
            'profit_rate' => $store->profit_rate,
            'image_base_path' => $store->image_base_path,
            'alter_image_files' => $store->alter_image_files,
            'resize_images' => $store->resize_images,
            'apply_watermark' => $store->apply_watermark,
            'watermark_image_id' => $store->watermark_image_id,
            'watermark_transparency' => $store->watermark_transparency,
            'images_location' => $store->images_location,
            'short_code' => $store->short_code,
            'upc_usage_type' => $store->upc_usage_type,
            'upc_default_company_prefix' => $store->upc_default_company_prefix,
            'working_hours' => $store->working_hours,
            'default_listing_quantity' => $store->default_listing_quantity,
            'ad_account' => $store->ad_account,
//            'linked_store_id' => $store->linked_store_id,
            'order_cutoff_time' => $store->order_cutoff_time,
            'logo_code' => $store->logo_code,
            'standard_shipping_name' => $store->standard_shipping_name,
            'logo_code_base64' => $store->logo_code_base64,
            'wholesale' => $store->wholesale,
            'auto_generate_listings' => $store->auto_generate_listings,
            'max_listing_inventory_quantity' => $store->max_listing_inventory_quantity,
            'free_shipping_amount' => $store->free_shipping_amount,
            'default_shipping_amount' => $store->default_shipping_amount,
            'store_url' => $store->store_url,
            'random_title_keyword_level' => $store->random_title_keyword_level,
            'download_listings' => $store->download_listings,
            'download_buy_box' => $store->download_buy_box,
            'download_reviews' => $store->download_reviews,
            'product_alt_image_number' => $store->product_alt_image_number,
            'productaltimagenumber' => $store->productaltimagenumber,
            'generate_device_details' => $store->generate_device_details,
            'label_order_prefix' => $store->label_order_prefix,

        ]);
    }

    private function createStoreLogistics(mixed $storeLogistic)
    {
        StoreLogisticCenter::updateOrInsert([

        ], [

        ]);
    }
}
