<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoBrandSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Magento Brand Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            $brands = Brand::where('status', 'active')->get();
            foreach ($brands as $brand) {
                $storePage = StoreNavigationPage::where('store_id', $this->storeId)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->where('device_brand_id', $brand->id)
                    ->first();

                if (isset($storePage) && $storePage->page_id) {
                    Log::info("Brand already synced: " . $brand->name . " Magento ID: " . $storePage->page_id);
                    continue;
                }

                Log::info("Syncing Brand: " . $brand->name);
                $handle = $this->generateHandle($brand->name);

                $brandData = [
                    'name' => $brand->name,
                    'url_key' => $handle,
                ];
                $response = $magentoAuthService->createBrand($brandData);

                if (isset($response['id'])) {
                    $magentoBrandId = $response['id'];

                    StoreNavigationPage::updateOrCreate(
                        [
                            'store_id' => $this->storeId,
                            'page_type' => WebStorePageType::BRAND,
                            'device_brand_id' => $brand->id,
                        ],
                        [
                            'page_id' => $magentoBrandId,
                            'url_path' => $handle,
                            'title' => $brand->name,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Brand Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
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
