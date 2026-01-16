<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\SysFile;
use App\Services\Auth\EbayAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ListingItemImageCreate implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

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
        $this->updateTrackingRecord(['tags' => 'Listing Item Image Create']);

        try {
            $listingInfo = Listing::find($this->listigId);
            //Log::info('Listing :: ' . json_encode($listingInfo));
            ListingItemLink::where('listing_id', $this->listigId)->chunk(100, function ($listingItemLinks) use ($listingInfo) {
                foreach ($listingItemLinks as $listingItemLink) {
                    $this->createListingItemImage($listingItemLink, $listingInfo);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Item Image Create Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
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
            ->chunk(100, function ($productImages) use ($listingItem, $store, $storeType, $listingItemLink,$productData) {
                if (!isset($store->fileSystem)) return;
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '';
                    //Log::info('StoreType :: ' . json_encode($storeType));

                    switch (strtolower($storeType->name)) {
                        case 'shopify':
                        case 'amazon':
                        case 'ebay':
                        case 'magento':
                        case 'temu':
                        case 'walmart':
                            $path = '/store-images/' . $store->id . '/' . $listingItem->id . '_' . $productImage->id . '.jpg';
                            break;
                    }

                    $imageData = [
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => null,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ];

                    //Log::info("Main Image :: " . json_encode($productImage));

                    if ($productImage->main_image == 1) {
                        $imageData['main_image'] = 1;
                        $nextImageVersion = $this->getProductMainImage($productData->product_sku,$listingItem->id);
                        if ($nextImageVersion != null) {
                            Log::info("Version :: " .  $nextImageVersion);
                            $alternativeImage = ProductImageAlternative::where('main_image_id', $productImage->id)->where('version_number', $nextImageVersion)->first();
                            if(isset($alternativeImage) && isset($alternativeImage->imageObj)) {
                                //Log::info("Alternative Image :: " . json_encode($alternativeImage));
                                $path = '/store-images/' . $store->id . '/' . $listingItem->id . '_' . $alternativeImage->id . '.jpg';
                                $imageData['path'] = $path;
                                $imageData['product_image_id'] = null;
                                $imageData['alt_product_image_id'] = $alternativeImage->id;
                                $Image = $alternativeImage->imageObj;
                                $this->uploadImage($store, $Image, $path);
                            } else {
                                $this->uploadImage($store, $Image, $path);
                            }
                        } else {
                            $this->uploadImage($store, $Image, $path);
                        }
                    } else {
                        $this->uploadImage($store, $Image, $path);
                    }

                    if($path != '' && strtolower($storeType->name) == 'ebay'){
                        $himage = $this->generateHostedImage($path,$store);
                        $imageData['path'] = $himage;
                    }

                    ListingItemProductImage::updateOrCreate([
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                    ], $imageData);
                }
            });
    }

    private function generateHostedImage($path,$store){

        $storeFileSystem = $store->fileSystem;
        $store_user_id = $store->user_id;

        $newpath = $storeFileSystem->url_base_path.$path;

        Log::info("Image path : $newpath");
        Log::info("store user id : $store_user_id");

        $filename = basename($path,".jpg");
        $clientId = env('EBAY_CLIENT_ID');
        $clientSecret = env('EBAY_CLIENT_SECRET');
        $redirectUri = env('EBAY_REDIRECT_URI');

        $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri,$store_user_id);
        $epsUrl = $authService->uploadImageToEPS($newpath, $filename);

        Log::info("Ebay Hosted Image: $epsUrl");

        if ($epsUrl) {
            $hostimage = $epsUrl;
        } else {
            $hostimage = $path;
        }

        return $hostimage;
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

        $this->createFtpDirectory($ftpConn, dirname($remoteFilePath));

        //Upload the file
        if (ftp_put($ftpConn, $remoteFilePath, $localFilePath, FTP_BINARY)) {
            echo "File uploaded successfully to $remoteFilePath";
        } else {
            echo "Error uploading file.";
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

    private function getProductMainImage($product_code, $listingItemId)
    {

        // Step 1: Fetch the used image_version from listing_item table
        $listingItems = ListingItem::whereLike('sku', '%' . $product_code . '%')->get();
        $usedImageVersion = [];
        if (count($listingItems ?? []) > 0) {
            foreach ($listingItems as $litem) {
                $usedImageVersion[] = $litem->image_version;
            }
        }
		//Log::info("Used Images :: " . json_encode($usedImageVersion));

        // Step 2: Get all alternate image versions from product_image_alternatives table
        $alternateImages = ProductImageAlternative::select('product_image_alternatives.product_id', 'product_image_alternatives.version_number', 'products.product_id', 'products.product_sku')->leftJoin('products', 'products.product_id', '=', 'product_image_alternatives.product_id')->where('products.product_sku', $product_code)->get();

        // Step 3: Determine the next image_version
        $nextImageVersion = null;
        $unusedImages = [];
        $usedImages = [];

        if (isset($alternateImages) && count($alternateImages) > 0) {
            foreach ($alternateImages as $image) {
                // Collect used and unused image versions
                if (isset($usedImageVersion) && in_array($image->version_number, $usedImageVersion)) {
                    $usedImages[] = $image->version_number;
                } else {
                    $unusedImages[] = $image->version_number;
                }
            }
        }

        if (count($unusedImages) > 0) {
            // If any image version is unused, take the first unused one as next image version
            $nextImageVersion = $unusedImages[0];
        } else if (count($usedImages) > 0) {
            // If all images are used, select a random one from the unused images
            $nextImageVersion = $usedImages[array_rand($usedImages)];
        } else {
            return null;
        }

        ListingItem::where('id', $listingItemId)->update(['image_version' => $nextImageVersion]);

        return $nextImageVersion;

    }
}
