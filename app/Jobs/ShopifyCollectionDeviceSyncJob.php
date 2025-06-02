<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Store;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ShopifyCollectionDeviceSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

    private mixed $storeID;
    private $store;
    private mixed $publications;
    private ShopifyAuthService $shopifyAuthService;


    /**
     * Create a new job instance.
     */
    public function __construct($storeID)
    {
        $this->storeID = $storeID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->setVariables();
        $this->getPuplications();
        Device::orderBy('id')->chunk(1000, function ($devices) {
            foreach ($devices as $device) {
                $this->syncShopifyCollection($device);
            }
        });
    }

    private function syncShopifyCollection(mixed $device)
    {
        $brand = $device->brand;
        Log::info('Syncing device with Shopify collection', ['device_id' => $device->id]);


        $collectionData['collection'] = [
            'title' => $brand->name . ' ' . $device->name . ' Accessories',
        ];
        $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;
        foreach ($this->publications as $publication) {
            $collectionData['collection']['publications'][]['publicationId'] = $publication;
        }

        $interfaces = DeviceInterface::where('device_id', $device->id)->get();
        foreach ($interfaces as $interface) {
            $collectionData['collection']['ruleSet']['rules'][] = [
                'column' => 'TAG',
                'relation' => 'EQUALS',
                'condition' => $interface->id,
            ];
        }

        $response = $this->shopifyAuthService->createCollection($collectionData);
    }

    private function getPuplications()
    {
        $publications = $this->shopifyAuthService->getPublications();

        $publicationIds = [];
        foreach ($publications['data']['publications']['edges'] as $publication) {
            $publicationIds[] = $publication['node']['id'];
        }
        $this->publications = $publicationIds;
    }

    private function setVariables()
    {
        $this->store = Store::find($this->storeID);
        $this->shopifyAuthService = new ShopifyAuthService($this->store->secret_key,$this->store->service_url);
    }
}
