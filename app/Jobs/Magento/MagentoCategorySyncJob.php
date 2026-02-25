<?php

namespace App\Jobs\Magento;

use App\Jobs\Concerns\TrackableJob;
use App\Models\ProductType;
use App\Models\Store;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoCategorySyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    protected $storeId;
    private MagentoAuthService $magentoAuthService;

    public function __construct($storeId)
    {
        $this->storeId = $storeId;
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Magento Category Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $this->magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            // Sync ProductType to Magento Categories
            $productTypes = $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'cea');
            })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

            foreach ($productTypes as $productType) {
                $this->syncProductType($productType, 2); // Root category usually 2
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Category Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncProductType($productType, $parentId)
    {
        Log::info("Syncing ProductType: " . $productType->name);
        $response = $this->magentoAuthService->createCategory($productType->name, $parentId);

        if (isset($response['id'])) {
            $magentoCategoryId = $response['id'];
            $children = ProductType::where('parent_id', $productType->id)->get();
            foreach ($children as $child) {
                $this->syncProductType($child, $magentoCategoryId);
            }
        }
    }
}
