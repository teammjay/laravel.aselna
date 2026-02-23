<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyIndvPagesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 36000;

    private $storeID;

    private $store;

    private $pageType;
    private $shopifyAuthService;

    public function __construct($storeID, $pageType = null)
    {
        $this->storeID = $storeID;
        $this->pageType = $pageType;
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Individual Pages']);

        try {
            $this->setVariables();
            $pagetTypes = [];
            if ($this->pageType) {
                $pagetTypes[] = $this->pageType;
            } else {
                $pagetTypes = [WebStorePageType::ALL_BRANDS, WebStorePageType::BRAND_DEVICES,
                    WebStorePageType::PRODUCT_TYPE_BRANDS, WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES];
            }
            $StoreNavigationPages = StoreNavigationPage::where('store_id', $this->storeID)
                ->whereIn('page_type', $pagetTypes)
                ->get();

            foreach ($StoreNavigationPages as $StoreNavigationPage) {
                try {
                    Log::info('Syncing page: ' . $StoreNavigationPage->page_name . ' for store: ' . $this->store->name);
                    $this->shopifyAuthService->syncIndividualPage($StoreNavigationPage);
                } catch (\Throwable $e) {

                    \App\Helpers\ErrorLogger::log($e, __METHOD__);

                    Log::error('Error in ShopifyIndvPagesSyncJob: ' . $e->getMessage());
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Shopify Indevidual Pages Sync Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function setVariables()
    {
        $this->store = Store::find($this->storeID);
        $this->shopifyAuthService = new ShopifyAuthService($this->store->secret_key, $this->store->service_url);
    }
}
