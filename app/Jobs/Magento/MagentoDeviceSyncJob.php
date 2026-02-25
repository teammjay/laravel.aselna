<?php

namespace App\Jobs\Magento;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\Store;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoDeviceSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Magento Device Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            $devices = Device::where('status', 'active')->get();
            foreach ($devices as $device) {
                Log::info("Syncing Device: " . $device->name);
                $deviceData = [
                    'name' => $device->name,
                    'brand_id' => $device->brand_id,
                    'code' => $device->code,
                    'image' => $device->image,
                    // add more fields as needed based on API documentation
                ];
                $magentoAuthService->createDevice($deviceData);
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Device Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
