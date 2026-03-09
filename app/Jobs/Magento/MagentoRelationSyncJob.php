<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceInterface;
use App\Models\ManageMarketplaceProduct;
use App\Models\MarketplaceAttributeMapping;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoRelationSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    protected $storeId;

    public function __construct($storeId)
    {
        $this->storeId = $storeId;
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Magento Relation Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            // Get relations between devices and products based on shared interfaces
            $relations = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
                ->join('products', 'products.product_id', '=', 'product_interface.product_id')
                ->join('device', 'device_interface.device_id', '=', 'device.id')
                ->where('device.status', 'active')
//                ->where('products.product_status', 'active')
                ->select('device_interface.device_id', 'products.product_id')
                ->where('products.product_id', '=', '260ad8c1-499b-4e4e-bf11-729023566d3e')
                ->distinct()
                ->get();

            Log::info("Found " . count($relations) . " technical relations to sync for store: " . $store->name);

            foreach ($relations as $relation) {
                // 1. Get Magento Product ID
                $manageProduct = ManageMarketplaceProduct::where('product_id', $relation->product_id)
                    ->where('store_id', $this->storeId)
                    ->where('upload_status', 'success')
                    ->first();

                if (!$manageProduct || !$manageProduct->marketplace_product_id) {
                    Log::info('Skipping relation for Product ID: ' . $relation->product_id . ' - not synced to Magento store yet.');
                    continue; // Product not yet synced to this store
                }

                // 2. Get Magento Device ID
                $devicePage = StoreNavigationPage::where('store_id', $this->storeId)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_id', $relation->device_id)
                    ->first();

                if (!$devicePage || !$devicePage->page_id) {
                    Log::info('Skipping relation for Device ID: ' . $relation->device_id . ' - not synced to Magento store yet.');
                    continue; // Device not yet synced to this store
                }

                Log::info("Syncing Relation for Product: " . $manageProduct->marketplace_product_id . " and Device: " . $devicePage->page_id);

                $relationData = [
                    'product_id' => $manageProduct->marketplace_product_id,
                    'device_id' => $devicePage->page_id
                ];

                $response = $magentoAuthService->createRelation($relationData);

                if (isset($response['entity_id'])) {
                    Log::info("Relation synced successfully. Magento ID: " . $response['entity_id']);
                } else {
                    Log::error("Failed to sync relation: " . json_encode($response));
                }
            }

        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Relation Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
