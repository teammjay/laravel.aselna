<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Device;
use App\Models\DevicesLinkedDevice;
use App\Models\Feed;
use App\Models\GenerateProductImage;
use App\Models\GoodUpc;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\ListingItemProductImage;
use App\Models\ProductImages;
use App\Models\ProductSpecification;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use App\Utils\FileUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ProcessFeedGenerationJob implements ShouldQueue
{
    use Dispatchable, TrackableJob {
        TrackableJob::withChain insteadof Dispatchable;
        Dispatchable::withChain as dispatchableWithChain;
    }

    public $timeout = 30000; // Set a timeout for the job

    protected $feed_id;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($requestdata, $feed_id)
    {
        $this->data = $requestdata;
        $this->feed_id = $feed_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            $this->updateTrackingRecord(['tags' => 'Google/Microsoft Feed Generation']);
            $feed_type = $this->data['feed_type'];
            $store_id = $this->data['store_id'];
            $fileType = $this->data['fileType'];
            $storeDetail = Store::with('storeType')->where('id', $store_id)->first();
            $fileSystem = $storeDetail->fileSystem;
            $store_type_detail = StoreType::getStoreTypeDetailById($store_id);

            if ($this->feed_id == '') {
                Log::error('Feed ID is missing. Cannot proceed with feed generation.');
                return;
            }
            $feedDetail = Feed::findOrFail($this->feed_id);
            Log::info('Feed detail: ' . json_encode($feedDetail));

            $query = StoreProduct::with('product', 'product.size', 'StoreTypeCategory', 'product.condition', 'product.Manufacturer', 'product.images', 'product.productType', 'product.interfaces', 'product.interfaces.interfaces', 'product.color');
            //$query = Catalog::with('StoreProduct', 'images', 'productType', 'interfaces.interfaces', 'pinterface.interfaces','condition','Manufacturer')->where('products.product_status', 1);

            $query->select('store_product.*');

            if ($this->data['store_id']) {
                $query->where('store_product.store_id', $store_id);
            }

            $query->leftJoin('products', 'store_product.product_id', '=', 'products.product_id');
            $query->where('products.oem_generic', 2);

            if (isset($this->data['product_code']) && $this->data['product_code'] != '') {

                $query->whereIn('products.product_sku', $this->data['product_code']);
            }

            if (isset($this->data['device']) && $this->data['device'] != '') {
                if (!isset($this->data['interface']) || $this->data['interface'] == '') {
                    $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'store_product.product_id');
                }
                $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
                $query->whereIn('device_interface.device_id', $this->data['device']);
            }

            if (isset($this->data['device_series']) && $this->data['device_series'] != '') {
                $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $this->data['device_series'])->get();
                $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'store_product.product_id');
                $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
                $query->whereIn('device_interface.device_id', $devices);
            }

            //$query->orderBy('products.product_sku','asc');
            if (env('APP_ENV') == 'local') {
                $query->limit(10);
            }

            $products = $query->distinct()->get();

            switch (strtolower($feed_type)) {
                case 'google':
                case 'microsoft':

                    $columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];

                    if (strtolower($feed_type) == 'microsoft') {
                        $columns = ['id', 'title', 'description', 'product_category', 'product_type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item_group_id', 'gender', 'age_group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];
                    }


                    $fileName = $this->data['fileName'];
                    $filePath = $this->data['filePath'];
                    $directory = public_path('feeds/google-shopping/');
                    $fileNameBase = pathinfo($this->data['fileName'], PATHINFO_FILENAME);
                    $fileExtension = pathinfo($this->data['fileName'], PATHINFO_EXTENSION);

//                    $stream = fopen('php://temp', 'w');
                    $fullPath = public_path('feeds/google-shopping/' . $fileName);

                    $max_file_size = 150 * 1024 * 1024; // 200 MB in bytes
                    $current_file_index = 1;

                    $getFilePath = function ($index) use ($directory, $fileNameBase, $fileExtension) {
                        return $directory . '/' . $fileNameBase . "_part{$index}." . $fileExtension;
                    };

                    $fileHandle = fopen($getFilePath($current_file_index), 'w');
                    fputcsv($fileHandle, $columns);

                    // Track feed parts for database
                    $feedParts = [];
                    $getPartFilename = function ($index) use ($fileNameBase, $fileExtension) {
                        return $fileNameBase . "_part{$index}." . $fileExtension;
                    };

                    $getPartPath = function ($index) use ($fileNameBase, $fileExtension) {
                        return 'feeds/google-shopping/' . $fileNameBase . "_part{$index}." . $fileExtension;
                    };

                    // Add first part to tracking array
                    $feedPart = [
                        'part_number' => $current_file_index,
                        'file_name' => $getPartFilename($current_file_index),
                        'path' => $getPartPath($current_file_index)
                    ];

                    // Write product rows

                    foreach ($products as $itemDetail) {

                        $images = array();
                        Log::info('Processing product ID: ' . $itemDetail->id);

                        $listingItemImages = ListingItemProductImage::where('listing_item_id',$itemDetail->id)->get();
                        $mainImage = null;

                        foreach ($listingItemImages as $image) {
                            if($image->main_image == 1){
                                $mainImage = $image->path;
                            } else {
                                $images[] = $image->path;
                            }
                        }

                        /*$this->generateProductImages($itemDetail, $storeDetail);
                        $productImages = GenerateProductImage::where('product_id', $itemDetail->product_id)->get();
                        foreach ($productImages as $image) {
                            $images[] = $fileSystem->url_base_path . $image->path;
                        }*/

                        $ptype_id = (isset($itemDetail->StoreTypeCategory) && !empty($itemDetail->StoreTypeCategory)) ? $itemDetail->StoreTypeCategory->store_type_code : '';
                        $product_type = '';
                        if (isset($itemDetail->product)) {
                            $product_code = $itemDetail->product->product_sku;
                            $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $itemDetail->product_id)->orderBy('priority', 'asc')->limit(5)->get();
                            $title = '';
                            $title_keyword = [];
                            if (!empty($keywords)) {
                                foreach ($keywords as $key => $val) {
                                    $title_keyword[] = trim($val['keyword']);
                                }
                                $title = implode(' ', $title_keyword);
                            }

                            $prodDetail = $this->generateProductDetail($itemDetail->product_id, $store_type_detail->id);

                            //echo $product_code;
                            //$compatibleDevice = $this->getCompatibleDevices($product_id,$request->device,$request->device_series);
                            $query = ListingItem::with('brand', 'device')->where('store_product_id', $itemDetail->id);
                            $listingItems = $query->get();
                            /*echo '<pre>';
                            echo $query->toSql();die;
                            print_r($listingItems);
                            echo '</pre>';die;*/
                            if ($listingItems) {
                                foreach ($listingItems as $listitem) {
                                    Log::info('Processing listing item ID: ' . $listitem->id . ' for product ID: ' . $itemDetail->product_id);

                                    $product_url = '';
                                    $store_sku = $listitem->store_sku;
                                    $store_type = isset($storeDetail->storeType->name) ? $storeDetail->storeType->name : '';
                                    $site_url = isset($storeDetail->store_url) ? $storeDetail->store_url : '';
                                    $store_url = isset($listitem->store_url) ? $listitem->store_url : '';

                                    $item_url = StoreNavigationPage::where('store_id', $store_id)
                                        ->where('page_type', WebStorePageType::DEVICE)
                                        ->where('device_id', $listitem->device_id)
                                        ->first();

                                    if ($item_url && !isset($listitem->url)) {
                                        $listitem->update(['url' => $item_url->url_path]);
                                    }

                                    $compatibledevices = $this->getCompatibleDevices($itemDetail->product_id, $this->data['device'], $this->data['device_series']);

                                    if ($compatibledevices) {

                                        foreach ($compatibledevices as $device) {
                                            Log::info('Processing compatible device ID: ' . $device->id . ' for listing item ID: ' . $listitem->id);
                                            $item_detail = ListingItemDetail::where('listing_item_id', $listitem->id)
                                                ->where('device_id', $device->id)->first();

                                            $productTitle = $device->name . ' ' . $title;

                                            if (!$item_detail) {
                                                Log::info('Inserting new listing item detail for listing item ID: ' . $listitem->id . ' and device ID: ' . $device->id);
                                                $item_detail = $this->insertListingItemDetail($storeDetail, $product_code, $listitem, $device);
                                            }

                                            $devicePage = StoreNavigationPage::where('store_id', $store_id)
                                                ->where('page_type', WebStorePageType::DEVICE)
                                                ->where('device_id', $device->id)
                                                ->first();

                                            if ($store_sku != '') {
                                                $product_url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url,$devicePage->url_path ?? '');
                                            }

                                            $typeDetail = ProductType::with('parent')->where('id', $itemDetail->product->product_type)->first();
                                            $product_type = $this->getCategoryPath($typeDetail);

                                            fputcsv($fileHandle, [
                                                $item_detail->sku,
                                                $productTitle,
                                                ($itemDetail->overwrite_description) ? $itemDetail->description : $itemDetail->product->product_description,
                                                $ptype_id,
                                                $product_type,
                                                $product_url,
                                                (isset($mainImage)) ? $mainImage : '',
                                                (isset($itemDetail->product->condition)) ? $itemDetail->product->condition->name : '',
                                                'in stock',
                                                (($itemDetail->product->product_marketplace_price != '') ? $itemDetail->product->product_marketplace_price : $itemDetail->product->product_price) . ' USD',
                                                (isset($item_detail->upc)) ? $item_detail->upc : '',
                                                (isset($itemDetail->product->Manufacturer)) ? $itemDetail->product->Manufacturer->name : '',
                                                $item_detail->sku,
                                                '',
                                                '',
                                                '',
                                                (isset($itemDetail->product->color->name)) ? $itemDetail->product->color->name : '',
                                                (isset($itemDetail->product->size->value)) ? $itemDetail->product->size->value . ' ' . $itemDetail->product->size->value_unit : '',
                                                'US::Standard:3.99 USD',
                                                (isset($images) && count($images) > 0) ? implode(',', $images) : '',
                                                (isset($listitem->qunatity)) ? $listitem->qunatity : 500,
                                                'return_policy_days: "30"',
                                                (isset($item_detail->device->brand->name)) ? $item_detail->device->brand->name : '',
                                                (isset($item_detail->device->name)) ? $item_detail->device->name : '',
                                                '',
                                                ($itemDetail->overwrite_title) ? $itemDetail->title : $itemDetail->product->product_title,
                                                (isset($itemDetail->product->product_sku)) ? $itemDetail->product->product_sku : '',
                                                $prodDetail
                                            ]);

                                            if (ftell($fileHandle) >= $max_file_size) {
                                                fclose($fileHandle);
                                                $current_file_index++;
                                                $fileHandle = fopen($getFilePath($current_file_index), 'w');
                                                fputcsv($fileHandle, $columns); // Write header to new file

                                                $feedDetail->parts()->create([
                                                    'part_number' => $feedPart['part_number'],
                                                    'file_name' => $feedPart['file_name'],
                                                    'path' => $feedPart['path']
                                                ]);

                                                // Track this new part
                                                $feedPart = [
                                                    'part_number' => $current_file_index,
                                                    'file_name' => $getPartFilename($current_file_index),
                                                    'path' => $getPartPath($current_file_index)
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $feedDetail->parts()->create([
                        'part_number' => $feedPart['part_number'],
                        'file_name' => $feedPart['file_name'],
                        'path' => $feedPart['path']
                    ]);

                    fclose($fileHandle);
//                    rewind($stream);
//                    FileUtility::saveToFile(public_path('feeds/google-shopping/' . $fileName), stream_get_contents($stream));
                    //Storage::put($filePath, stream_get_contents($stream));
//                    fclose($stream);

                    $feedDetail->update([
                        'status' => 1,
                    ]);


                    break;

            }

        } catch (\Exception $e) {
            Log::error('Error processing feed generation: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Feed generation job failed: ' . $exception->getMessage());
    }

    private function insertListingItemDetail($storeDetail, $product_code, $listitem, $device)
    {

        $store_id = $storeDetail->id;

        if ($storeDetail->sku_generation_type) {
            switch ($storeDetail->sku_generation_type) {
                case 1:
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6);
                    break;
                case 2:
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku('', 6);
                    break;
                case 3:
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6);
                    break;
                case 4:
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6);
                    break;
                case 'default':
                    $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6);
                    break;
            }
        } else {
            $sku = StoreTypeListing::generateUniqueAlphabeticSku($product_code, 6);
        }

        if ($storeDetail->upc_usage_type) {
            switch ($storeDetail->upc_usage_type) {
                case 0:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 1:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 2:
                    $upcCodes = GoodUpc::where('is_used', 0)->orderBy('upc', 'asc')->first();
                    break;
                case 3:
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 'default':
                    $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
            }
        } else {
            $upcCodes = UPCCodes::where('store', $store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
        }

        $created_by = auth()->user()->name ?? 'admin';

        $itemDetail = array(
            'sku' => $sku,
            'title' => $listitem->title,
            'upc' => isset($upcCodes->upc_code) ? $upcCodes->upc_code : '',
            'device_id' => $device->id,
            'listing_item_id' => $listitem->id,
            'device_carrier_id' => null,
            //'store_sku' => $store_id,
            'created_by' => $created_by
        );

        $newlistingItem = ListingItemDetail::create($itemDetail);

        return $newlistingItem;
    }

    private function generateProductDetail($product_id, $store_type_id)
    {

        $productSpecification = ProductSpecification::where('product_id', $product_id)->where('store_type_id', $store_type_id)->get();
        $section = 'General';
        $kvSep = ':';
        $pairSep = ',';
        $parts = [];
        foreach ($productSpecification as $specification) {

            $section = trim((string)$section);
            $key = trim((string)$specification->name);
            $value = trim((string)$specification->value);

            if ($section === '' || $key === '' || $value === '') {
                continue;
            }

            $parts[] = "{$section}{$kvSep}{$key}{$kvSep}{$value}";
        }

        return implode($pairSep, $parts);
    }


    private function getCompatibleDevices($product_id, $devices, $deviceSeries)
    {
        $query = Device::select('device.*', 'product_interface.product_id')->with('brand', 'rating')->with('deviceType', 'deviceSeries');
        $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
        $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');

        $query->where('product_interface.product_id', $product_id);

        if ($devices) {
            $query->whereIn('device.id', $devices);
        }

        $query->where('device.status', 'active');

        if ($deviceSeries) {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $deviceSeries)->get();
            $query->whereIn('device.id', $devices);
        }
        $query->orderBy('device.name', 'asc');

        Log::info('Compatible Devices Query: ' . $query->toSql());

        $devices = $query->get();

        return $devices;
    }

    private function getCategoryPath($category)
    {
        $path = [];
        while ($category) {
            $path[] = $category->name;
            $category = $category->parent;
        }
        return implode(' > ', array_reverse($path));
    }

    public function generateProductImages($productData, $store)
    {
        //Log::info('Filesystem : ' . json_encode($store->fileSystem));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($store, $productData) {
                if (!isset($store->fileSystem)) return;
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '/store-images/' . $store->id . '/' . $productImage->id . '.jpg';

                    $imageData = [
                        'product_id' => $productData->product_id,
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => null,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ];

                    Log::info("Main Image :: " . json_encode($productImage));

                    if ($productImage->main_image == 1) {
                        $imageData['main_image'] = 1;
                    }
                    $this->uploadImage($store, $Image, $path);

                    GenerateProductImage::updateOrCreate([
                        'product_image_id' => $productImage->id,
                    ], $imageData);
                }
            });
    }

    public function uploadImage($store, $Image, $path)
    {
        $storeFileSystem = $store->fileSystem;
        Log::info("File system :: " . json_encode($storeFileSystem));
        $host = $storeFileSystem->host;
        $port = $storeFileSystem->port ?? 21;
        $loginId = $storeFileSystem->login_id;
        $password = $storeFileSystem->password;
        $rootPath = $storeFileSystem->root_path;
        $localFilePath = storage_path('app/public/' . $Image->location . $Image->name);

        $ftpConn = \ftp_connect($host, $port);
        if (!$ftpConn) {
            Log::info("Could not connect to FTP server: $host");
        }

        $login = \ftp_login($ftpConn, $loginId, $password);
        if (!$login) {
            ftp_close($ftpConn);
            Log::info("Could not login to FTP server with provided credentials.");
        }

        \ftp_pasv($ftpConn, true);

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

    public function createFtpDirectory($ftpConn, $dirPath)
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
