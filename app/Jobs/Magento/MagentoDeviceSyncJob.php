<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\Store;
use App\Models\StoreNavigationPage;
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
                $storePage = StoreNavigationPage::where('store_id', $this->storeId)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_id', $device->id)
                    ->first();

                if (isset($storePage) && $storePage->page_id) {
                    Log::info("Device already synced: " . $device->name . " Magento ID: " . $storePage->page_id);
                    continue;
                }

                Log::info("Syncing Device: " . $device->name);
                $handle = $this->generateHandle($device->name);
                $deviceData = [
                    'name' => $device->name,
                    'brand_id' => $device->brand_id,
                    'url_key' => $handle,
                ];
                $response = $magentoAuthService->createDevice($deviceData);

                if (isset($response['id'])) {
                    $magentoDeviceId = $response['id'];

                    StoreNavigationPage::updateOrCreate(
                        [
                            'store_id' => $this->storeId,
                            'page_type' => WebStorePageType::DEVICE,
                            'device_id' => $device->id,
                        ],
                        [
                            'page_id' => $magentoDeviceId,
                            'device_brand_id' => $device->brand_id,
                            'url_path' => $handle,
                            'title' => $device->name,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Device Sync Job Failed', [
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
