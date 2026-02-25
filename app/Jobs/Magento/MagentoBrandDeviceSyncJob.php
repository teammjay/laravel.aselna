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

class MagentoBrandDeviceSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Magento Brand-Device Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            $devices = Device::where('status', 'active')->get();
            foreach ($devices as $device) {
                if ($device->brand_id) {
                    Log::info("Syncing Relation: Brand {$device->brand_id} - Device {$device->id}");
                    $magentoAuthService->createBrandDeviceRelation($device->brand_id, $device->id);
                }
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Brand-Device Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
