<?php

namespace App\Jobs\Sync;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Shopify\ShopifyBrandPageSyncJob;
use App\Jobs\ShopifyIndvPagesSyncJob;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DevicesSyncJob;
use App\Jobs\Fonus\ChainCompletionJob;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductKeyFeaturesSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Models\JobStatus;
use App\Models\Store;
use App\Models\StorePageTemplate;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopifyOnlySyncJob implements ShouldQueue
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
        $this->chainId = (string) Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Combined']);

        try {
            $chain = [];
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
                    $chain[] = $storeBrandJob1;

                    $storeBrandJob2 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE,
                        $brandTemplate->id
                    );
                    $chain[] = $storeBrandJob2;

                    $storeBrandJob3 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::DEVICE,
                        $brandTemplate->id
                    );
                    $chain[] = $storeBrandJob3;

                    $storeBrandJob4 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES,
                        $brandDeviceProductTemplate->id
                    );
                    $chain[] = $storeBrandJob4;

                    $shopifyIndvJob1 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES
                    );
                    $chain[] = $shopifyIndvJob1;

                    $storeBrandJob5 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE_BRANDS,
                        $productTypeBrands->id
                    );
                    $chain[] = $storeBrandJob5;

                    $shopifyIndvJob2 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::PRODUCT_TYPE_BRANDS
                    );
                    $chain[] = $shopifyIndvJob2;

                    $storeBrandJob6 = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICES,
                        $brandDevicesTemplate->id
                    );
                    $chain[] = $storeBrandJob6;

                    $shopifyIndvJob3 = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::BRAND_DEVICES
                    );
                    $chain[] = $shopifyIndvJob3;

                    $chain[] = new ShopifyBrandPageSyncJob(
                        $store->id,
                        WebStorePageType::ALL_BRANDS,
                        $allBrandTemplate->id
                    );

                    $chain[] = new ShopifyIndvPagesSyncJob(
                        $store->id,
                        WebStorePageType::ALL_BRANDS
                    );
                }

                $chain[] = new ChainCompletionJob($this->jobId);

                // Create pending record for the completion job
                $this->createPendingRecordsForChain($chain, $this->jobId);

                // Store parent job ID for the catch callback
                $parentJobId = $this->jobId;

                Bus::chain($chain)
                    ->catch(function (\Throwable $e) use ($parentJobId) {
                        // Mark parent as failed if chain fails
                        JobStatus::where('job_id', $parentJobId)
                            ->update(['status' => 'failed', 'output' => $e->getMessage(), 'finished_at' => now()]);
                    })
                    ->dispatch();
            }
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Shopify Combined Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }
}
