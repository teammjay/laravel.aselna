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
        Device::where('status', 'active')->orderBy('id')->chunk(100, function ($devices) {
            foreach ($devices as $device) {
                $this->syncShopifyCollection($device);
            }
        });
    }

    private function syncShopifyCollection(mixed $device)
    {
        $brand = $device->brand;
        Log::info('Syncing device with Shopify collection', ['device_id' => $device->id]);

        $title = $brand->name . ' ' . $device->name . ' Accessories';
        // create a handle for the collection by replacing spaces ( and .) with dashes and converting to lowercase
        $handle = $this->generateShopifyHandle($title);

        $collectionData['collection'] = [
            'title' => $title,
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

        if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
            Log::info('Collection already exists, updating collection', ['handle' => $handle]);
            $collectionData['collection']['id'] = $alreadyCollection['id'];
            $collectionData['collection']['handle'] = $handle;
            $response = $this->shopifyAuthService->updateCollection($collectionData);
        } else {
            Log::info('Creating new collection', ['handle' => $handle]);
            $collectionData['collection']['handle'] = $handle;
            $collectionData['collection']['published'] = true;
            $response = $this->shopifyAuthService->createCollection($collectionData);
        }
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

    function generateShopifyHandle($title) {
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
