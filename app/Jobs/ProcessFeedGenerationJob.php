<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DevicesLinkedDevice;
use App\Models\Feed;
use App\Models\GenerateProductImage;
use App\Models\GoodUpc;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\ListingItemProductImage;
use App\Models\ProductImages;
use App\Models\ProductKeyFeatures;
use App\Models\ProductSpecification;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreDeviceType;
use App\Models\StoreNavigationPage;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeListing;
use App\Models\UPCCodes;
use App\Services\CriticalErrorService;
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
    protected $store_id;
    protected $compatibleDeviceTypes;
    private $storeDetail;
    private $store_type_detail;
    private $current_file_index;
    private $feedPart;
    private $directory;
    private $fileNameBase;
    private $fileExtension;
    /**
     * @var array|string[]
     */
    private $columns;
    /**
     * @var false|resource
     */
    private $fileHandle;
    private int $max_file_size;

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
            $this->store_id = $store_id = $this->data['store_id'];
            $fileType = $this->data['fileType'];
            $this->storeDetail = Store::with('storeType')->where('id', $store_id)->first();
            $fileSystem = $this->storeDetail->fileSystem;
            $this->store_type_detail = StoreType::getStoreTypeDetailById($store_id);

            $this->max_file_size = 200 * 1024 * 1024;

            $this->compatibleDeviceTypes = StoreDeviceType::where('store_id', $store_id)
                ->where('purpose', 'feed')->pluck('device_type_id')->toArray();
            Log::info('Sql for Compatible Device Types: ' . StoreDeviceType::where('store_id', $store_id)->where('purpose', 'feed')->toRawSQL());
            Log::info('Compatible Device Types for Feed: ' . json_encode($this->compatibleDeviceTypes));


            if ($this->feed_id == '') {
                Log::error('Feed ID is missing. Cannot proceed with feed generation.');
                return;
            }
            $feedDetail = Feed::findOrFail($this->feed_id);
            Log::info('Feed detail: ' . json_encode($feedDetail));

            $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();
            $ceaProductTypes = [];
            $ceaParentTypes = ProductType::select('id')->where('code', 'cea')->get()->pluck('id')->toArray();

            // Get immediate children
            $ceaChildrenTypes = ProductType::select('id')->whereIn('parent_id', $ceaParentTypes)->get()->pluck('id')->toArray();
            $ceaProductTypes = array_merge($ceaProductTypes, $ceaParentTypes, $ceaChildrenTypes);

            // Get descendants recursively
            $nextLevelParents = $ceaChildrenTypes;
            foreach ($nextLevelParents as $parentId) {
                $children = ProductType::select('id')->where('parent_id', $parentId)->get()->pluck('id')->toArray();
                if (!empty($children)) {
                    $ceaProductTypes = array_merge($ceaProductTypes, $children);
                }
            }

            $query = StoreProduct::with('product', 'product.size', 'StoreTypeCategory', 'product.condition', 'product.Manufacturer', 'product.images', 'product.productType', 'product.interfaces', 'product.interfaces.interfaces', 'product.color');
            //$query = Catalog::with('StoreProduct', 'images', 'productType', 'interfaces.interfaces', 'pinterface.interfaces','condition','Manufacturer')->where('products.product_status', 1);

            $query->select('store_product.*');

            if ($this->data['store_id']) {
                $query->where('store_product.store_id', $store_id);
            }

            $query->leftJoin('products', 'store_product.product_id', '=', 'products.product_id');
//            $query->where('products.oem_generic', 2);
            $query->whereNotIn('products.product_type', $productTypes);
            $query->whereIn('products.product_type', $ceaProductTypes);
            $query->whereNull('products.parent_id');
            $query->where('product_status', '=', 'active');

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

            $query->orderBy('products.product_sku', 'asc');

            switch (strtolower($feed_type)) {
                case 'google':
                    $this->columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];
                    break;
                case 'microsoft':
                    $this->columns = ['id', 'title', 'description', 'product_category', 'product_type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item_group_id', 'gender', 'age_group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];
                    break;
            }

            $this->current_file_index = 1;
            $this->directory = public_path('feeds/google-shopping/');
            $this->fileNameBase = pathinfo($this->data['fileName'], PATHINFO_FILENAME);
            $this->fileExtension = pathinfo($this->data['fileName'], PATHINFO_EXTENSION);

            $this->fileHandle = fopen($this->getFilePath($this->current_file_index), 'w');
            fputcsv($this->fileHandle, $this->columns);

            $this->feedPart = [
                'part_number' => $this->current_file_index,
                'file_name' => $this->getPartFilename($this->current_file_index),
                'path' => $this->getPartPath($this->current_file_index)
            ];

            $query->chunk(10, function ($products) use ($feed_type, $feedDetail) {
                Log::info('Processing chunk of products, count: ' . $products->count());
                $this->addProductsToFeed($feed_type, $products, $feedDetail);
            });

            fclose($this->fileHandle);

            $feedDetail->parts()->create([
                'part_number' => $this->feedPart['part_number'],
                'file_name' => $this->feedPart['file_name'],
                'path' => $this->feedPart['path']
            ]);

            $feedDetail->update([
                'status' => 1,
            ]);

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Error processing feed generation', [
                'marketplace' => $this->storeDetail->storeType->name ?? '',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['store_id' => $this->store_id]);

            Log::error('Error processing feed generation: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Feed generation job failed: ' . $exception->getMessage());
    }

    private function insertListingItemDetail($product_code, $listitem, $device)
    {
        if ($this->storeDetail->sku_generation_type) {
            switch ($this->storeDetail->sku_generation_type) {
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

        if ($this->storeDetail->upc_usage_type) {
            switch ($this->storeDetail->upc_usage_type) {
                case 0:
                    $upcCodes = UPCCodes::where('store', $this->store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 1:
                    $upcCodes = UPCCodes::where('store', $this->store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 2:
                    $upcCodes = GoodUpc::where('is_used', 0)->orderBy('upc', 'asc')->first();
                    break;
                case 3:
                    $upcCodes = UPCCodes::where('store', $this->store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
                case 'default':
                    $upcCodes = UPCCodes::where('store', $this->store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
                    break;
            }
        } else {
            $upcCodes = UPCCodes::where('store', $this->store_id)->where('is_used', 0)->orderBy('sequence_id', 'asc')->first();
        }

        $created_by = auth()->user()->name ?? 'admin';

        $itemDetail = array(
            'sku' => $sku,
            'title' => $listitem->title,
            'upc' => isset($upcCodes->upc_code) ? $upcCodes->upc_code : '',
            'device_id' => $device->id,
            'listing_item_id' => $listitem->id,
            'device_carrier_id' => null,
            //'store_sku' => $this->store_id,
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
            $value = ucfirst(trim((string)$specification->calculated_value()));

            if ($section === '' || $key === '' || $value === '') {
                continue;
            }

            $parts[] = "{$section}{$kvSep}{$key}{$kvSep}{$value}";
        }

        return implode($pairSep, $parts);
    }


    private function getCompatibleDevices($product_id, $devices, $deviceSeries)
    {
        $excludedRating = DeviceRating::whereIn('value',[8])->pluck('id')->toArray();
        $includedRatings = DeviceRating::whereIn('value',[1,2,9,10])->pluck('id')->toArray();
        Log::info('Excluded Device Ratings: ' . json_encode($excludedRating));
        $query = Device::select('device.*', 'product_interface.product_id')->with('brand', 'rating')->with('deviceType', 'deviceSeries');
        $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
        $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
        $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');

        $query->where('product_interface.product_id', $product_id);

        if ($devices) {
            $query->whereIn('device.id', $devices);
        }

        if (isset($this->compatibleDeviceTypes)) {
            Log::info('Compatible Device Types: ' . json_encode($this->compatibleDeviceTypes));
            $query->whereIn('device_type_id', $this->compatibleDeviceTypes);
        }

        $query->where('device.status', 'active');
        $query->whereNotIn('device.rating_id', $excludedRating);
        $query->whereIn('device.rating_id', $includedRatings);

        if ($deviceSeries) {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $deviceSeries)->get();
            $query->whereIn('device.id', $devices);
        }
        $query->orderBy('brands.name', 'asc');
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

     public function getFilePath ($index) {
        return $this->directory . '/' . $this->fileNameBase . "_part{$index}." . $this->fileExtension;
    }

    private function getPartFilename($index)
    {
        return $this->fileNameBase . "_part{$index}." . $this->fileExtension;
    }

    private function getPartPath($index)
    {
        return 'feeds/google-shopping/' . $this->fileNameBase . "_part{$index}." . $this->fileExtension;
    }

    /**
     * @param mixed $feed_type
     * @param \Illuminate\Database\Eloquent\Collection $products
     * @param $feedDetail
     * @return string
     */
    public function addProductsToFeed(mixed $feed_type, \Illuminate\Database\Eloquent\Collection $products, $feedDetail)
    {
        foreach ($products as $itemDetail) {

            $images = array();
            Log::info('Processing product ID: ' . $itemDetail->id);

            $product_description = '';
            ProductKeyFeatures::where('product_id', $itemDetail->product_id)
                ->orderBy('sort_order', 'asc')
                ->chunk(100, function ($keyFeatures) use (&$product_description) {
                    foreach ($keyFeatures as $feature) {
                        if (isset($feature->value)) {
                            $product_description .= trim((string)$feature->value) . ' ';
                        }
                    }
                });

            if (isset($itemDetail->product->product_description)) {
                $cleanDescription = str_replace(["\r", "\n"], ' ', $itemDetail->product->product_description);
                $product_description .= ' ' . trim($cleanDescription);
            }
            $product_description = trim($product_description);

            //$ptype_id = (isset($itemDetail->StoreTypeCategory) && !empty($itemDetail->StoreTypeCategory)) ? $itemDetail->StoreTypeCategory->store_type_code : '';
            $ptype_id = 264;
            $product_type = '';
            if (isset($itemDetail->product)) {
                $product_code = $itemDetail->product->product_sku;
                $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $itemDetail->product_id)->orderBy('priority', 'asc')->limit(6)->get();
                $title = '';
                $title_keyword = [];
                if (!empty($keywords)) {
                    foreach ($keywords as $key => $val) {
                        $title_keyword[] = trim($val['keyword']);
                    }
                    $title = implode(' ', $title_keyword);
                }

                $prodDetail = $this->generateProductDetail($itemDetail->product_id, $this->store_type_detail->id);

                $query = ListingItem::where('store_product_id', $itemDetail->id);
                $listingItems = $query->get();

                if ($listingItems) {
                    foreach ($listingItems as $listitem) {
                        Log::info('Processing listing item ID: ' . $listitem->id . ' for product ID: ' . $itemDetail->product_id);

                        $product_url = '';
                        $store_sku = $listitem->store_sku;
                        $store_type = isset($this->storeDetail->storeType->name) ? $this->storeDetail->storeType->name : '';
                        $site_url = isset($this->storeDetail->store_url) ? $this->storeDetail->store_url : '';
                        $store_url = isset($listitem->store_url) ? $listitem->store_url : '';

                        $item_url = StoreNavigationPage::where('store_id', $this->store_id)
                            ->where('page_type', WebStorePageType::DEVICE)
                            ->where('device_id', $listitem->device_id)
                            ->first();

                        if ($item_url && !isset($listitem->url)) {
                            $listitem->update(['url' => $item_url->url_path]);
                        }

                        $productTitle = NULL;
                        if(isset($listitem->title) && $listitem->title != NULL){
                            $productTitle = $listitem->title;
                        }

                        $typeDetail = ProductType::with('parent')->where('id', $itemDetail->product->product_type)->first();
                        $product_type = $this->getCategoryPath($typeDetail);

                        $listingItemImages = ListingItemProductImage::where('listing_item_id', $listitem->id)->get();
                        $mainImage = null;

                        foreach ($listingItemImages as $image) {
                            if ($image->main_image == 1) {
                                $mainImage = $image->path;
                            } else {
                                $images[] = $image->path;
                            }
                        }
                        Log::info('Main image for listing item ID ' . $listitem->id . ': ' . $mainImage);
                        Log::info('Additional images for listing item ID ' . $listitem->id . ': ' . json_encode($images));

                        $compatibledevices = $this->getCompatibleDevices($itemDetail->product_id, $this->data['device'], $this->data['device_series']);

                        if ($compatibledevices) {

                            foreach ($compatibledevices as $device) {
                                Log::info('Processing compatible device ID: ' . $device->id . ' for listing item ID: ' . $listitem->id);
                                $item_detail = ListingItemDetail::where('listing_item_id', $listitem->id)
                                    ->where('device_id', $device->id)->first();

                                if($productTitle == NULL) {

                                    if (strtolower($device->brand->name) == 'apple') {
                                        $productTitle = $device->name . ' - ' . $title . ' - ' . $this->storeDetail->short_code . $itemDetail->product->product_sku;
                                    } else {
                                        $productTitle = $device->brand->name . ' ' . $device->name . ' - ' . $title . ' - ' . $this->storeDetail->short_code . $itemDetail->product->product_sku;
                                    }
                                }

                                $final_description = $device->brand->name . ' ' . $device->name . ' - ' . $title . ' - ' . $this->storeDetail->short_code . $itemDetail->product->product_sku. ' for ' . $device->name . '. ' . $product_description;

                                if (!$item_detail) {
                                    Log::info('Inserting new listing item detail for listing item ID: ' . $listitem->id . ' and device ID: ' . $device->id);
                                    $item_detail = $this->insertListingItemDetail($product_code, $listitem, $device);
                                }

                                $devicePage = StoreNavigationPage::where('store_id', $this->store_id)
                                    ->where('page_type', WebStorePageType::DEVICE)
                                    ->where('device_id', $device->id)
                                    ->first();

                                if ($store_sku != '') {
                                    $product_url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url, $devicePage->url_path ?? '');
                                }

                                // item group id logic
                                //$itemGroupId = $item_detail->sku . '-' . $device->code;
                                $itemGroupId = (isset($listitem->sku) && $listitem->sku != '') ? $listitem->sku : $item_detail->sku . '-' . $device->code;

                                fputcsv($this->fileHandle, [
                                    $item_detail->sku,
                                    $productTitle,
                                    $final_description,
                                    $ptype_id,
                                    $product_type,
                                    $product_url,
                                    (isset($mainImage)) ? $mainImage : '',
                                    (isset($itemDetail->product->condition)) ? strtolower($itemDetail->product->condition->name) : '',
                                    'in stock',
                                    (($itemDetail->product->product_price != '') ? $itemDetail->product->product_price : $itemDetail->product->product_price) . ' USD',
                                    (isset($item_detail->upc)) ? $item_detail->upc : '',
                                    (isset($this->storeDetail->brand_name)) ? $this->storeDetail->brand_name : '',
                                    $item_detail->sku,
                                    (isset($device) ? $itemGroupId : ''),
                                    '',
                                    '',
                                    (isset($itemDetail->product->color->name)) ? $itemDetail->product->color->name : '',
                                    (isset($itemDetail->product->size->value)) ? $itemDetail->product->size->value . ' ' . $itemDetail->product->size->value_unit : '',
                                    'US::Standard:4.99 USD',
                                    (isset($images) && count($images) > 0) ? implode(',', $images) : '',
                                    (isset($listitem->qunatity)) ? $listitem->qunatity : 1000,
                                    'return_policy_days: "30"',
                                    (isset($item_detail->device->brand->name)) ? $item_detail->device->brand->name : '',
                                    (isset($item_detail->device->name)) ? $item_detail->device->name : '',
                                    '',
                                    ($itemDetail->overwrite_title) ? $itemDetail->title : $itemDetail->product->product_title,
                                    (isset($itemDetail->product->product_sku)) ? $itemDetail->product->product_sku : '',
                                    $prodDetail
                                ]);

                                if (ftell($this->fileHandle) >= $this->max_file_size) {
                                    fclose($this->fileHandle);
                                    $this->current_file_index++;
                                    $this->fileHandle = fopen($this->getFilePath($this->current_file_index), 'w');
                                    fputcsv($this->fileHandle, $this->columns); // Write header to new file

                                    $feedDetail->parts()->create([
                                        'part_number' => $this->feedPart['part_number'],
                                        'file_name' => $this->feedPart['file_name'],
                                        'path' => $this->feedPart['path']
                                    ]);

                                    // Track this new part
                                    $this->feedPart = [
                                        'part_number' => $this->current_file_index,
                                        'file_name' => $this->getPartFilename($this->current_file_index),
                                        'path' => $this->getPartPath($this->current_file_index)
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
