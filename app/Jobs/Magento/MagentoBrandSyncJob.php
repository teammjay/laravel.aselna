<?php

namespace App\Jobs\Magento;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Store;
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
                Log::info("Syncing Brand: " . $brand->name);
                $brandData = [
                    'name' => $brand->name,
                    'code' => $brand->code,
                    'image' => $brand->image,
                    // add more fields as needed based on API documentation
                ];
                $magentoAuthService->createBrand($brandData);
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Brand Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
