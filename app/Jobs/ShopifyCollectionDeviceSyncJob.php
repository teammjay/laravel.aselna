<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\ShopifyAuthService;
use App\Utils\FileUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyCollectionDeviceSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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

        $storePage = StoreNavigationPage::where('store_id', $this->storeID)
            ->where('page_type', WebStorePageType::DEVICE)
            ->where('device_id', $device->id)
            ->first();

        if (isset($storePage)) {
            $handle = $storePage->url_path;
        } else {
            $handle = $this->generateShopifyHandle($brand->name . ' ' . $device->name );
        }

        $collectionData['collection'] = [
            'title' => $title,
        ];
        $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;
        $path = '/store-images/' . $this->store->id . '/collections/' . $handle . '.jpg';
        try {

            $imagePath = FileUtility::uploadImage($this->store, 'app/public/device_images/' . $device->image, $path);
        } catch (\Exception $e) {
            $imagePath = '';
        }
        Log::info('Image path for device', ['device_id' => $device->id, 'image_path' => $imagePath]);
        $collection = null;

        if ($imagePath) {
            $collectionData['collection']['image'] = [
                'src' => $imagePath,
            ];
        } else {
            Log::error('Image upload failed for device', ['device_id' => $device->id, 'image' => $device->image]);
        }

        $interfaces = DeviceInterface::where('device_id', $device->id)->get();
        foreach ($interfaces as $interface) {
            $collectionData['collection']['ruleSet']['rules'][] = [
                'column' => 'TAG',
                'relation' => 'EQUALS',
                'condition' => $interface->interface_id,
            ];
        }

        if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
            Log::info('Collection already exists, updating collection', ['handle' => $handle]);
            $collectionData['collection']['id'] = $alreadyCollection['id'];
            $collectionData['collection']['handle'] = $handle;
            Log::info('Collection data update', ['collection_data' => $collectionData]);
            $response = $this->shopifyAuthService->updateCollection($collectionData);

            if (isset($response['data']['collectionUpdate'])) {
                $collection = $response['data']['collectionUpdate']['collection'];
            }
        } else {
            Log::info('Creating new collection', ['handle' => $handle]);

            foreach ($this->publications as $publication) {
                $collectionData['collection']['publications'][]['publicationId'] = $publication;
            }

            $collectionData['collection']['handle'] = $handle;
            Log::info('Collection data creation', ['collection_data' => $collectionData]);
            $response = $this->shopifyAuthService->createCollection($collectionData);

            if (isset($response['data']['collectionCreate'])) {
                $collection = $response['data']['collectionCreate']['collection'];
            }
        }
        Log::info('Collection ::' . json_encode($collection));
        if (isset($collection)) {
            $id =str_replace('gid://shopify/Collection/', '', $collection['id']);
            $imageUrl = $collection['image']['url'] ?? '';
            StoreNavigationPage::updateOrCreate(
                [
                    'store_id' => $this->storeID,
                    'page_type' => WebStorePageType::DEVICE,
                    'device_id' => $device->id,
                ],
                [
                    'store_id' => $this->storeID,
                    'page_type' => WebStorePageType::DEVICE,
                    'device_id' => $device->id,
                    'device_brand_id' => $device->brand_id,
                    'url_path' => $handle,
                    'store_image_url' => $imageUrl,
                    'title' => $title,
                    'page_id' => $id,
                ]
            );
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
        Log::info('Store ID :: ' . $this->storeID);
        $this->store = Store::find($this->storeID);
        Log::info('Store Details :: ' . json_encode($this->store));

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
