<?php

namespace App\Jobs\Sync;

use App\Constants\WebStorePageType;
use App\Jobs\BrandSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\Devices\PopularDevicesSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductKeyFeaturesSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\ShopifyIndvPagesSyncJob;
use App\Jobs\ShopifyBrandPageSyncJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Models\Store;
use App\Models\StorePageTemplate;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;
use Illuminate\Support\Str;

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
        // Generate unique chainId for this job chain
        $this->chainId = (string) Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Shopify Combined']);

        try {
            $chain = [
                new ProductSyncJob(),
                new ProductKeyFeaturesSyncJob(),
                new InterfaceSyncJob(),
//            new DeviceSeriesSyncJob(),
                new CategorySyncJob(),
                new BrandSyncJob(),
                new DevicesSyncJob(),
                new DeviceCarrierSyncJob(),
                new DeviceInterfaces(),
                new ProductInterfaces(),
                new ProductSpecificationSyncJob(),
                new ProductImagesSyncJob(),
                new SubmitShopifyProductsJob(),
                new SubmitShopifyProductsJob(),
                new SubmitShopifyProductsJob()
            ];

            // Set chainId for all initial jobs
            foreach ($chain as $job) {
                if (method_exists($job, 'setChainId')) {
                    $job->setChainId($this->chainId);
                }
            }

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

                    $storeBrandJob1 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::BRAND,
                        $brandTemplate->id
                    );
                    $storeBrandJob1->setChainId($this->chainId);
                    $chain[] = $storeBrandJob1;

                    $storeBrandJob2 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE,
                        $brandTemplate->id
                    );
                    $storeBrandJob2->setChainId($this->chainId);
                    $chain[] = $storeBrandJob2;

                    $storeBrandJob3 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::DEVICE,
                        $brandTemplate->id
                    );
                    $storeBrandJob3->setChainId($this->chainId);
                    $chain[] = $storeBrandJob3;

                    $storeBrandJob4 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES,
                        $brandDeviceProductTemplate->id
                    );
                    $storeBrandJob4->setChainId($this->chainId);
                    $chain[] = $storeBrandJob4;

                    $shopifyIndvJob1 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES
                    );
                    $shopifyIndvJob1->setChainId($this->chainId);
                    $chain[] = $shopifyIndvJob1;

                    $storeBrandJob5 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE_BRANDS,
                        $productTypeBrands->id
                    );
                    $storeBrandJob5->setChainId($this->chainId);
                    $chain[] = $storeBrandJob5;

                    $shopifyIndvJob2 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE_BRANDS
                    );
                    $shopifyIndvJob2->setChainId($this->chainId);
                    $chain[] = $shopifyIndvJob2;

                    $storeBrandJob6 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICES,
                        $brandDevicesTemplate->id
                    );
                    $storeBrandJob6->setChainId($this->chainId);
                    $chain[] = $storeBrandJob6;

                    $shopifyIndvJob3 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICES
                    );
                    $shopifyIndvJob3->setChainId($this->chainId);
                    $chain[] = $shopifyIndvJob3;

                $chain[] = new ShopifyBrandPageSyncJob(
                    $store->id,
                    WebStorePageType::ALL_BRANDS,
                    $allBrandTemplate->id
                );
//
                $chain[] = new ShopifyIndvPagesSyncJob(
                    $store->id,
                    WebStorePageType::ALL_BRANDS
                );
                }

//            self::queueableWithChain($chain)->dispatch();
                Bus::chain($chain)->dispatch();
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Shopify Combined Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }
}
