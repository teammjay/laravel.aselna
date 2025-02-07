<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductImages;
use App\Models\SysFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ListingItemImageCreate implements ShouldQueue
{
    use Queueable;

    private $listigId;

    /**
     * Create a new job instance.
     */
    public function __construct($listigId)
    {
        //
        $this->listigId = $listigId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listingInfo = Listing::find($this->listigId);
        Log::info('Listing :: ' . json_encode($listingInfo));
        ListingItemLink::where('listing_id', $this->listigId)->chunk(100, function ($listingItemLinks) use ($listingInfo) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemImage($listingItemLink, $listingInfo);
            }
        });
    }

    private function createListingItemImage(mixed $listingItemLink, $listingInfo)
    {
        //Log::info('ListingItemLink :: ' . json_encode($listingItemLink));
        $listingItem = $listingItemLink->item;
        $store = $listingInfo->store;
        $storeType = $listingInfo->store->storeType;
        $storeProduct = $listingItem->storeProduct;
        $productData = $storeProduct->product;
        //Log::info('ProductData :: ' . json_encode($productData));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($listingItem, $store, $storeType, $listingItemLink) {
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '';
                    Log::info('StoreType :: ' . json_encode($storeType));

                    switch (strtolower($storeType->name)) {
                        case 'amazon':
                        case 'walmart':
                            $path = '/store-images/' . $store->id . '/' . $listingItem->id . '_' . $productImage->id . '.jpg';
                            break;
                    }
                    $this->uploadImage($store,$Image,$path);

                    ListingItemProductImage::updateOrCreate([
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                    ], [
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ]);
                }
            });
    }

    private function uploadImage($store,$Image,$path)
    {
        $storeFileSystem = $store->fileSystem;
        Log::info("File system :: " . json_encode($storeFileSystem));
        $host = $storeFileSystem->host;
        $port = $storeFileSystem->port ?? 21;
        $loginId = $storeFileSystem->login_id;
        $password = $storeFileSystem->password;
        $rootPath = $storeFileSystem->root_path;
        $localFilePath = storage_path('app/public/' . $Image->location . $Image->name);

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

        //Upload the file
        if (ftp_put($ftpConn, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            echo "File uploaded successfully to $remoteFilePath";
        } else {
            echo "Error uploading file.";
        }

        // Close FTP connection
        ftp_close($ftpConn);
    }
}
