<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
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
            $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'cea');
            })->whereNotLike('code','%-%')->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

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

    private function syncProductType($productType, $magentoParentId, $parentNavigationPageId = null)
    {
        $storePage = StoreNavigationPage::where('store_id', $this->storeId)
            ->where('page_type', WebStorePageType::PRODUCT_TYPE)
            ->where('product_type_id', $productType->id)
            ->first();

        if (isset($storePage) && $storePage->page_id) {
            $magentoCategoryId = $storePage->page_id;
            $currentNavigationPageId = $storePage->id;
            Log::info("ProductType already synced: " . $productType->name . " Magento ID: " . $magentoCategoryId);
        } else {
            Log::info("Syncing ProductType: " . $productType->name);
            $response = $this->magentoAuthService->createCategory($productType->name, $magentoParentId);
            Log::info("Magento API Response for " . $productType->name . ": " . json_encode($response));


            if (isset($response['id'])) {
                $magentoCategoryId = $response['id'];
                $handle = $this->generateHandle($productType->name);

                $storePage = StoreNavigationPage::updateOrCreate(
                    [
                        'store_id' => $this->storeId,
                        'page_type' => WebStorePageType::PRODUCT_TYPE,
                        'product_type_id' => $productType->id,
                    ],
                    [
                        'page_id' => $magentoCategoryId,
                        'url_path' => $handle,
                        'title' => $productType->name,
                        'parent_page_id' => $parentNavigationPageId,
                    ]
                );
                $currentNavigationPageId = $storePage->id;
            }
        }

        if (isset($magentoCategoryId)) {
            $children = ProductType::where('parent_id', $productType->id)->whereNotLike('code','%-%')->get();
            foreach ($children as $child) {
                $this->syncProductType($child, $magentoCategoryId, $currentNavigationPageId ?? null);
            }
        }
    }

    private function generateHandle($title)
    {
        // Convert to lowercase
        $handle = mb_strtolower($title, 'UTF-8');

        // Normalize accented characters (e.g., é → e)
        $handle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $handle);

        // Replace apostrophes
        $handle = preg_replace("/'/", '', $handle);

        // Replace any non-alphanumeric characters (excluding spaces and dots) with nothing
        $handle = preg_replace("/[^a-z0-9\s.]/", '', $handle);

        // Replace dots with hyphens (e.g., 12.3 → 12-3)
        $handle = str_replace('.', '-', $handle);

        // Replace whitespace with hyphens
        $handle = preg_replace("/[\s]+/", '-', $handle);

        // Remove multiple consecutive hyphens
        $handle = preg_replace("/-+/", '-', $handle);

        // Trim hyphens from beginning and end
        $handle = trim($handle, '-');

        return $handle;
    }
}
