<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Store;
use App\Models\StorePageTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyCombinedSyncing implements ShouldQueue
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
        $chain = [
            new InterfaceSyncJob(),
//            new DeviceSeriesSyncJob(),
            new CategorySyncJob(),
            new BrandSyncJob(),
            new DevicesSyncJob(),
            new DeviceCarrierSyncJob(),
            new DeviceInterfaces(),
            new ProductInterfaces(),
            new ProductImagesSyncJob(),
            new SubmitShopifyProductsJob()
        ];

        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {

                if (($store->auto_generate_listings ?? 0) == 0) {
                    Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                    continue;
                }

                $allBrandTemplate = StorePageTemplate::where('name', 'All Brands')->first();
                $brandTemplate = StorePageTemplate::where('name', 'Brand')->first();
                $brandDeviceProductTemplate = StorePageTemplate::where('name', 'Brand Device Product Types')->first();
                $productTypeBrands = StorePageTemplate::where('name', 'Product types brands')->first();
                $brandDevicesTemplate = StorePageTemplate::where('name', 'Brand Devices')->first();

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::BRAND,
                    $brandTemplate->id
                );

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::PRODUCT_TYPE,
                    $brandTemplate->id
                );

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::DEVICE,
                    $brandTemplate->id
                );

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES,
                    $brandDeviceProductTemplate->id
                );

                $chain[] = new ShopifyIndvPagesSyncJob(
                    $store->id,
                    WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES
                );

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::PRODUCT_TYPE_BRANDS,
                    $productTypeBrands->id
                );

                $chain[] = new ShopifyIndvPagesSyncJob(
                    $store->id,
                    WebStorePageType::PRODUCT_TYPE_BRANDS
                );

                $chain[] = new StoreBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::BRAND_DEVICES,
                    $brandDevicesTemplate->id
                );

                $chain[] = new ShopifyIndvPagesSyncJob(
                    $store->id,
                    WebStorePageType::BRAND_DEVICES
                );

//                $chain[] = new StoreBrandPageSyncJob(
//                    $store->id,
//                    WebStorePageType::ALL_BRANDS,
//                    $allBrandTemplate->id
//                );
//
//                $chain[] = new ShopifyIndvPagesSyncJob(
//                    $store->id,
//                    WebStorePageType::ALL_BRANDS
//                );
            }

//            self::queueableWithChain($chain)->dispatch();
            Bus::chain($chain)->dispatch(new ProductSyncJob());
        }
    }
}
