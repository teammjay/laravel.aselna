<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceInterface;
use App\Models\ManageMarketplaceProduct;
use App\Models\ProductInterface;
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

            // Get all synced products for this store
            $syncedProducts = ManageMarketplaceProduct::where('store_id', $this->storeId)
                ->where('upload_status', 'success')
                ->whereNotNull('marketplace_product_id')
                ->get();

            Log::info("Optimized Sync: Checking relations for " . count($syncedProducts) . " products in store: " . $store->name);

            foreach ($syncedProducts as $manageProduct) {
                $this->syncProductRelations($manageProduct, $magentoAuthService);
            }

        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Relation Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncProductRelations($manageProduct, $magentoAuthService)
    {
        $productId = $manageProduct->product_id;
        $magentoProductId = $manageProduct->marketplace_product_id;

        // 1. Get technical compatible device IDs for this product
        $technicalDeviceIds = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
            ->where('product_interface.product_id', $productId)
            ->pluck('device_interface.device_id')
            ->unique()
            ->toArray();

        // 2. Map technical device IDs to Magento Device IDs (only those synced to this store)
        $shouldHaveMagentoDeviceIds = StoreNavigationPage::where('store_id', $this->storeId)
            ->where('page_type', WebStorePageType::DEVICE)
            ->whereIn('device_id', $technicalDeviceIds)
            ->pluck('page_id')
            ->unique()
            ->toArray();

        // 3. Fetch current relations from Magento for this specific product
        $searchCriteria = [
            'searchCriteria' => [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'product_id',
                                'value' => $magentoProductId,
                                'condition_type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $magentoResponse = $magentoAuthService->searchRelations($searchCriteria);
        $magentoItems = $magentoResponse['items'] ?? [];

        $currentMagentoDeviceToRelationId = [];
        foreach ($magentoItems as $item) {
            $currentMagentoDeviceToRelationId[$item['device_id']] = $item['entity_id'];
        }

        $currentlyLinkedMagentoDeviceIds = array_keys($currentMagentoDeviceToRelationId);

        // 4. Diffing
        $toAdd = array_diff($shouldHaveMagentoDeviceIds, $currentlyLinkedMagentoDeviceIds);
        $toDelete = array_diff($currentlyLinkedMagentoDeviceIds, $shouldHaveMagentoDeviceIds);

        if (empty($toAdd) && empty($toDelete)) {
            // Log::info("Product $productId is already in sync.");
            return;
        }

        Log::info("Syncing Product $magentoProductId: Adding " . count($toAdd) . " relations, Deleting " . count($toDelete));

        // 5. Execute Additions
        foreach ($toAdd as $magentoDeviceId) {
            try {
                $magentoAuthService->createRelation([
                    'product_id' => $magentoProductId,
                    'device_id' => $magentoDeviceId
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create relation for Product $magentoProductId and Device $magentoDeviceId: " . $e->getMessage());
            }
        }

        // 6. Execute Deletions
        foreach ($toDelete as $magentoDeviceId) {
            try {
                $relationId = $currentMagentoDeviceToRelationId[$magentoDeviceId];
                $magentoAuthService->deleteRelation($relationId);
            } catch (\Exception $e) {
                Log::error("Failed to delete relation $relationId: " . $e->getMessage());
            }
        }
    }
}
