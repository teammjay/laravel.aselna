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
        $path = '/store-images/' . $this->store->id . '/collections/' . $handle . '.jpg';
        $imagePath = $this->uploadImage($this->store, $device->image, $path);
        Log::info('Image path for device', ['device_id' => $device->id, 'image_path' => $imagePath]);

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

    private function uploadImage($store, $Image, $path)
    {
        $storeFileSystem = $store->fileSystem;
        //Log::info("File system :: " . json_encode($storeFileSystem));
        $host = $storeFileSystem->host;
        $port = $storeFileSystem->port ?? 21;
        $loginId = $storeFileSystem->login_id;
        $password = $storeFileSystem->password;
        $rootPath = $storeFileSystem->root_path;
        $localFilePath = storage_path('app/public/device_images/' . $Image);

        $ftpConn = ftp_connect($host, $port);
        if (!$ftpConn) {
            Log::info("Could not connect to FTP server: $host");
        }

        $login = ftp_login($ftpConn, $loginId, $password);
        if (!$login) {
            ftp_close($ftpConn);
            Log::info("Could not login to FTP server with provided credentials.");
        }

        ftp_pasv($ftpConn, true);

        $remoteFilePath = $rootPath . $path;
        Log::info("Remote Path :: " . $remoteFilePath);

        $this->createFtpDirectory($ftpConn, dirname($remoteFilePath));

        //Upload the file
        if (ftp_put($ftpConn, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            //echo "File uploaded successfully to $remoteFilePath";
            return $storeFileSystem->url_base_path . $path;
        } else {
            //echo "Error uploading file.";
        }

        // Close FTP connection
        ftp_close($ftpConn);
    }

    function createFtpDirectory($ftpConn, $dirPath)
    {
        $parts = explode('/', $dirPath);
        $currentDir = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentDir .= "/$part";

            if (!@ftp_chdir($ftpConn, $currentDir)) { // Check if directory exists
                ftp_mkdir($ftpConn, $currentDir); // Create directory if it does not exist
            }
        }
    }

}
