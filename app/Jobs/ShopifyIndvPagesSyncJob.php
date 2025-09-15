<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ShopifyIndvPagesSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 36000;

    private mixed $storeID;

    private Store $store;

    private mixed $pageType;
    private ShopifyAuthService $shopifyAuthService;

    public function __construct($storeID, $pageType = null)
    {
        $this->storeID = $storeID;
        $this->pageType = $pageType;
    }

    public function handle(): void
    {
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
                $this->shopifyAuthService->syncIndividualPage($StoreNavigationPage);
            } catch (\Exception $e) {
                Log::error('Error in ShopifyIndvPagesSyncJob: ' . $e->getMessage());
            }
        }
    }

    private function setVariables()
    {
        $this->store = Store::find($this->storeID);
        $this->shopifyAuthService = new ShopifyAuthService($this->store->secret_key, $this->store->service_url);
    }
}
