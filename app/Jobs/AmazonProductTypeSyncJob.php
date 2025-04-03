<?php

namespace App\Jobs;

use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\AmazonAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AmazonProductTypeSyncJob implements ShouldQueue
{
    use Queueable;

    private $amazonAuthService;

    public $timeout = 30000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->amazonAuthService = new AmazonAuthService();
        StoreTypeCategory::whereIn('store_type_id', function ($subQuery) {
            return $subQuery->select('id')->from('store_type')->where('code', 'amazon');
        })
            ->whereNull('marketplace_code')
            ->orderBy('id')->chunk(1000, function ($storeProductTypes) {
            foreach ($storeProductTypes as $storeProductType) {
                $this->syncAmazonProductType($storeProductType);
                sleep(1);
            }
        });
    }

    private function syncAmazonProductType(mixed $storeProductType)
    {

        Log::info('Syncing Amazon product type' . json_encode($storeProductType));
        $result = $this->amazonAuthService->getProductTypes($storeProductType['name']);
        if (isset($result['productTypes'])) {
            $productTypes = $result['productTypes'];
            foreach ($productTypes as $productType) {
                $storeProductType->update([
                    'marketplace_code' => $productType['name'],
                ]);
                break;
            }
        } else {
            Log::error('Error syncing Amazon product type: ' . json_encode($result));
        }
    }
}
