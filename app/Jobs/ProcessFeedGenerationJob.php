<?php

namespace App\Jobs;

use App\Constants\FeedStatus;
use App\Constants\WebStorePageType;
use App\Exceptions\UpcNotAvailableException;
use App\Helpers\ErrorLogger;
use App\Models\Device;
use App\Models\DeviceRating;
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
use App\Models\ProductTypeStoreTypeCATLink;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StoreDeviceType;
use App\Models\StoreNavigationPage;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\StoreTypeListing;
use App\Models\StoreTypeManufaturerLink;
use App\Models\UPCCodes;
use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

    private $file_name = 'feed.csv';
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
    private $max_file_size;
    private $category_store_type_detail;
    // public/ relative folder the part files go to, one sub folder per store
    private $feedFolder = 'feeds/google-shopping';

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

            $feed_type = $this->data['feed_type'];
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => ucfirst(strtolower($feed_type)) . ' Feed Generation']);
            $this->store_id = $store_id = $this->data['store_id'];
            $fileType = $this->data['fileType'];
            $this->storeDetail = Store::with('storeType')->where('id', $store_id)->first();
            $fileSystem = $this->storeDetail->fileSystem;
            $this->store_type_detail = StoreType::getStoreTypeDetailById($store_id);
            $this->category_store_type_detail = StoreType::where('code', 'googleShopping')->first();

            $this->max_file_size = 200 * 1024 * 1024;

            // Meta takes at most 100 MB per uploaded feed file, so its parts are cut smaller.
            if (strtolower($feed_type) == 'meta') {
                $this->max_file_size = 100 * 1024 * 1024;
            }

            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'development') {
//                $this->max_file_size = 1 * 1024 * 1024; // 1 MB for local testing
            }

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

            // !empty(): a saved template stores an unused filter as [], and [] != '' is true, which
            // filtered on an empty list and gave a feed with no products.
            if (!empty($this->data['product_code'])) {

                $query->whereIn('products.product_sku', $this->data['product_code']);
            }

            if (!empty($this->data['device'])) {
                if (empty($this->data['interface'])) {
                    $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'store_product.product_id');
                }
                $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
                $query->whereIn('device_interface.device_id', $this->data['device']);
            }

            if (!empty($this->data['excluded_product_code'])) {
                $query->whereNotIn('products.product_sku', $this->data['excluded_product_code']);
            }

            $query->orderBy('products.product_sku', 'asc');

            switch (strtolower($feed_type)) {
                case 'google':
                    $this->columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];
                    break;
                case 'microsoft':
                    $this->columns = ['id', 'title', 'description', 'product_category', 'product_type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item_group_id', 'gender', 'age_group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];
                    break;
                case 'meta':
                    // Columns of Meta's catalog template (docs/catalog_products.csv), followed by the
                    // optional Meta fields we also fill for Google.
                    $this->columns = ['id', 'title', 'description', 'availability', 'condition', 'link', 'image_link', 'brand', 'price', 'google_product_category', 'fb_product_category', 'quantity_to_sell_on_facebook', 'sale_price', 'sale_price_effective_date', 'item_group_id', 'gender', 'color', 'size', 'age_group', 'material', 'pattern', 'shipping', 'shipping_weight', 'offer_disclaimer', 'offer_disclaimer_url', 'video[0].url', 'video[0].tag[0]', 'gtin', 'product_tags[0]', 'product_tags[1]', 'style[0]', 'additional_image_link', 'product_type', 'mpn', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4'];
                    $this->feedFolder = 'feeds/meta';
                    break;
            }

            $this->current_file_index = 1;
            $this->directory = public_path($this->feedFolder . '/' . $this->store_id);
            // ensure directory exists
            FileUtility::createDirectoryIfNotExists($this->directory);
            $this->fileNameBase = pathinfo($this->file_name, PATHINFO_FILENAME);
            $this->fileExtension = pathinfo($this->file_name, PATHINFO_EXTENSION);

            $this->clearPreviousFeedFiles();

            $this->fileHandle = fopen($this->getFilePath($this->current_file_index), 'w');
            fputcsv($this->fileHandle, $this->columns);

            $this->feedPart = [
                'part_number' => $this->current_file_index,
                'file_name' => $this->getPartFilename($this->current_file_index),
                'path' => $this->getPartPath($this->current_file_index)
            ];

            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'development') {
                Log::info('Feed generation query: ' . $query->toRawSql());
                $query->limit(10);
            }

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
                'status' => FeedStatus::PROCESSED,
            ]);

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);

        } catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            // Without this the feed stays on Processing forever after a crash. Running out of UPCs
            // is a deliberate stop, so that feed is marked Failed.
            if ($this->feed_id) {
                Feed::where('id', $this->feed_id)->update([
                    'status' => $e instanceof UpcNotAvailableException ? FeedStatus::FAILED : FeedStatus::ERROR,
                ]);
            }

            if (is_resource($this->fileHandle)) {
                fclose($this->fileHandle);
            }

            // Not a crash: the store ran out of UPCs. Log it once, in words the client can act on,
            // against the store so it shows up under that store on the error log page.
            if ($e instanceof UpcNotAvailableException) {
                ErrorLogger::logMessage($e->getMessage(), __METHOD__, $this->store_id);
                Log::error('Feed ' . $this->feed_id . ' stopped at listing item detail ' . $e->listingItemDetailId . ': ' . $e->getMessage());

                return;
            }

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Error processing feed generation', [
                'marketplace' => $this->storeDetail->storeType->name ?? '',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ], ['store_id' => $this->store_id]);

            Log::error('Error processing feed generation: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Feed generation job failed: ' . $exception->getMessage());

        if ($this->feed_id) {
            Feed::where('id', $this->feed_id)->update(['status' => FeedStatus::FAILED]);
        }

        $this->updateTrackingRecord([
            'status' => 'failed',
            'output' => $exception->getMessage(),
            'finished_at' => now(),
        ]);
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

        $created_by = auth()->user()->name ?? 'admin';

        $itemDetail = array(
            'sku' => $sku,
            'title' => $listitem->title,
            // Left empty when none are left; the caller then stops the feed with UpcNotAvailableException.
            'upc' => $this->claimUnusedUpc() ?? '',
            'device_id' => $device->id,
            'listing_item_id' => $listitem->id,
            'device_carrier_id' => null,
            //'store_sku' => $this->store_id,
            'created_by' => $created_by
        );

        $newlistingItem = ListingItemDetail::create($itemDetail);

        return $newlistingItem;
    }

    /**
     * Claim the next unused UPC and mark it as used. Stores on the UPC pool (upc_usage_type 2,
     * "Upcpool") draw from good_upc; every other store draws from its own upc_codes.
     * Returns null when no UPC is available.
     *
     * The claim is an update that only matches while the code is still unused, so two jobs can't
     * take the same code and no rows stay locked while searching. A code that is already on a
     * listing item detail (its is_used flag was never set) stays marked used and the next is tried.
     */
    private function claimUnusedUpc(): ?string
    {
        $usesUpcPool = (int) $this->storeDetail->upc_usage_type === 2;

        while (true) {
            if ($usesUpcPool) {
                $upc = GoodUpc::where('is_used', 0)->where('upc', '!=', '')->orderBy('upc', 'asc')->value('upc');
                if ($upc === null) {
                    return null;
                }
                // good_upc has no id or timestamp columns, so the update can't go through the model
                $claimed = DB::table('good_upc')->where('upc', $upc)->where('is_used', 0)->update(['is_used' => 1]);
            } else {
                $upcCode = UPCCodes::where('store', $this->store_id)
                    ->where('is_used', 0)
                    ->where('upc_code', '!=', '')
                    ->orderBy('sequence_id', 'asc')
                    ->first(['id', 'upc_code']);
                if (!$upcCode) {
                    return null;
                }
                $upc = $upcCode->upc_code;
                $claimed = UPCCodes::where('id', $upcCode->id)->where('is_used', 0)->update(['is_used' => 1]);
            }

            // 0 rows updated means another job claimed it first
            if ($claimed && !ListingItemDetail::where('upc', $upc)->exists()) {
                return $upc;
            }
        }
    }

    private function generateProductDetail($product_id, $store_type_id)
    {

        $productSpecification = ProductSpecification::where('product_id', $product_id)->where('store_type_id', $store_type_id)->orderBy('name', 'asc')->get();
        $section = 'General';
        $kvSep = ':';
        $pairSep = ',';
        $parts = [];
        foreach ($productSpecification as $specification) {

            $section = trim((string)$section);
            $key = trim((string)$specification->name);
            $value = ucfirst(trim((string)$specification->calculated_value()));

            // if value contains , replace with -
            if (strpos($value, ',') !== false) {
                $value = str_replace(',', ' - ', $value);
            }

            if ($section === '' || $key === '' || $value === '') {
                continue;
            }

            $parts[] = "{$section}{$kvSep}{$key}{$kvSep}{$value}";
        }

        return implode($pairSep, $parts);
    }


    private function getCompatibleDevices($product_id, $devices)
    {
        $excludedDevices = $this->data['excluded_device'] ?? [];
        $excludedRating = $this->data['excluded_ratings'] ?? [];
        $includedRatings = $this->data['included_ratings'] ?? [];

        Log::info('Excluded Device Ratings: ' . json_encode($excludedRating));
        $query = Device::select('device.*', 'product_interface.product_id')->with('brand', 'rating')->with('deviceType', 'deviceSeries');
        $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
        $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
        $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');

        $query->where('product_interface.product_id', $product_id);

        if ($devices) {
            $query->whereIn('device.id', $devices);
        }

        if (!empty($excludedDevices)) {
            $query->whereNotIn('device.id', $excludedDevices);
        }

        if (isset($this->compatibleDeviceTypes)) {
            Log::info('Compatible Device Types: ' . json_encode($this->compatibleDeviceTypes));
            $query->whereIn('device_type_id', $this->compatibleDeviceTypes);
        }

        $query->where('device.status', 'active');

        // An unrated device has rating_id NULL, and both IN and NOT IN evaluate to
        // NULL for it, so it fails either test. Let it through explicitly.
        $query->where(function ($subQuery) use ($excludedRating, $includedRatings) {
            $subQuery->whereNull('device.rating_id')
                ->orWhere(function ($rated) use ($excludedRating, $includedRatings) {
                    $rated->whereNotIn('device.rating_id', $excludedRating)
                        ->whereIn('device.rating_id', $includedRatings);
                });
        });

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

    public function getFilePath($index)
    {
        return $this->directory . '/' . $this->fileNameBase . "_{$index}." . $this->fileExtension;
    }

    /**
     * Remove the part files of the previous run for this store. A run that ends up with fewer parts
     * than the last one only overwrites the parts it writes, so without this the leftover parts
     * (feed_4.csv .. feed_7.csv after a 7 part run followed by a 3 part one) stay on disk and keep
     * being served.
     */
    private function clearPreviousFeedFiles(): void
    {
        // Numbered parts only: feed_1.csv, feed_12.csv. Anything else named like a part, such as
        // feed_1_asl.csv or a backup copy, is left alone.
        $partPattern = '/^' . preg_quote($this->fileNameBase, '/') . '_\d+\.' . preg_quote($this->fileExtension, '/') . '$/';

        $removed = 0;

        foreach (glob($this->directory . '/' . $this->fileNameBase . '_*.' . $this->fileExtension) ?: [] as $oldFile) {
            if (is_file($oldFile) && preg_match($partPattern, basename($oldFile))) {
                @unlink($oldFile);
                $removed++;
            }
        }

        if ($removed > 0) {
            Log::info('Removed ' . $removed . ' feed part file(s) of the previous run from ' . $this->directory);
        }
    }

    private function getPartFilename($index)
    {
        return $this->fileNameBase . "_{$index}." . $this->fileExtension;
    }

    private function getPartPath($index)
    {
        return $this->feedFolder . '/' . $this->store_id . '/' . $this->fileNameBase . "_{$index}." . $this->fileExtension;
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
            $manufacturer = null;
            $manufacturer_name = '';
            Log::info('Processing product ID: ' . $itemDetail->id);

            $product_description = '';
            ProductKeyFeatures::where('product_id', $itemDetail->product_id)
                ->orderBy('sort_order', 'asc')
                ->chunk(100, function ($keyFeatures) use (&$product_description) {
                    foreach ($keyFeatures as $feature) {
                        if (isset($feature->value) && !empty($feature->value)) {
                            $product_description .= ((string)$feature->value) . ' ';
                        }
                    }
                });

            if (isset($itemDetail->product->product_description)) {
                $cleanDescription = str_replace(["\r", "\n"], ' ', $itemDetail->product->product_description);
                $product_description .= $cleanDescription;
            }
            $product_description = str_replace(["\r", "\n"], ' ', $product_description);
//            $product_description = str_replace([";"], '.', $product_description);

            //$ptype_id = (isset($itemDetail->StoreTypeCategory) && !empty($itemDetail->StoreTypeCategory)) ? $itemDetail->StoreTypeCategory->store_type_code : '';

            $product_type = '';
            if (isset($itemDetail->product)) {
                $product_code = $itemDetail->product->product_sku;
                $ptype_id = $this->getProductCategoryCode($itemDetail->product->product_type);
                if (!isset($ptype_id)) $ptype_id = 264;

                $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $itemDetail->product_id)->orderBy('priority', 'asc')->limit(6)->get();
                $title = '';
                $itemProductTitle = NULL;
                $product_price = '';
                $title_keyword = [];
                if (!empty($keywords)) {
                    foreach ($keywords as $key => $val) {
                        $title_keyword[] = trim($val['keyword']);
                    }
                    $title = implode(' ', $title_keyword);
                }

                if (isset($itemDetail->title)) {
                    $itemProductTitle = $title = $itemDetail->title;
                } else  {
                    $title = $title . '  - ' . $this->storeDetail->short_code . $itemDetail->product->product_sku;
                }

                if (isset($itemDetail->product->Manufacturer)) {
                    $manufacturer = $itemDetail->product->Manufacturer;
                }

                if (isset($itemDetail->Manufacturer)) {
                    $manufacturer = $itemDetail->Manufacturer;
                }

                if (isset($itemDetail->product)) {
                    $product_price = $itemDetail->product->product_price;
                }

                if (isset($itemDetail->price) && isset($itemDetail->overwrite_price) && $itemDetail->overwrite_price == 1) {
                    $product_price = $itemDetail->price;
                }

                $custom_label_3_prefix = '';
                $singular_name = $itemDetail->product->productType->singular_name ?? '';
                if (isset($singular_name) && $singular_name != '') {
                    $custom_label_3_prefix = $singular_name . ' for ';
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

                        $typeDetail = ProductType::with('parent')->where('id', $itemDetail->product->product_type)->first();
                        $product_type = $this->getCategoryPath($typeDetail);

                        $listingItemImages = ListingItemProductImage::where('listing_item_id', $listitem->id)->orderBy('main_image','desc')->limit(5)->get();
                        $mainImage = null;

                        foreach ($listingItemImages as $image) {
                            if ($image->main_image == 1) {
                                $mainImage = $image->path;
                            } else {
                                $images[] = $image->path;
                            }
                        }

                        if (isset($listitem->manufacturer)) {
                            $manufacturer = $listitem->manufacturer;
                        }
                        $manufacturer_name = $this->getManufacturerName($manufacturer);

                        if (isset($listitem->price) && isset($listitem->overwrite_price) && $listitem->overwrite_price == 1) {
                            $product_price = $listitem->price;
                        }

                        Log::info('Main image for listing item ID ' . $listitem->id . ': ' . $mainImage);
                        Log::info('Additional images for listing item ID ' . $listitem->id . ': ' . json_encode($images));

                        $compatibledevices = $this->getCompatibleDevices($itemDetail->product_id, $this->data['device'] ?? []);

                        if ($compatibledevices) {

                            foreach ($compatibledevices as $device) {
                                Log::info('Processing compatible device ID: ' . $device->id . ' for listing item ID: ' . $listitem->id);
                                $item_detail = ListingItemDetail::where('listing_item_id', $listitem->id)
                                    ->where('device_id', $device->id)->first();

                                $productTitle = $itemProductTitle;

                                $custom_label_3 = '';

                                if (strtolower($device->brand->name) == 'apple') {
                                    $custom_label_3 = $custom_label_3_prefix . $device->name;
                                } else {
                                    $custom_label_3 = $custom_label_3_prefix . $device->brand->name . ' ' . $device->name;
                                }

                                if ($productTitle == NULL) {
                                    $productTitle = $title;
                                }

                                $deviceType = $device->deviceType;
                                $descriptionTitle = '';
                                if ($deviceType->code != 1 && $deviceType->code != 2) {
                                    $descriptionTitle = $device->brand->name . ' ' . $device->name . ' Compatible ' . $title;
                                } else {
                                    $descriptionTitle = $device->brand->name . ' ' . $device->name . ' - ' . $title;
                                }

                                $final_description = $descriptionTitle . ' for ' . $device->name . '. ' . $product_description;

                                if ($this->can_add_device_to_title($productTitle, $device)) {
                                    if (strpos($productTitle, ' ') !== false &&
                                        (str_contains(strtolower($this->storeDetail->store_url), 'accessorychoice') || str_contains(strtolower($this->storeDetail->store_url), 'accessorywiz')
                                            || str_contains(strtolower($this->storeDetail->store_url), 'aselna') || str_contains(strtolower($this->storeDetail->store_url), 'neva'))) {
                                        $productTitle = str_replace(',', ' for ' . $device->brand->name . ' ' . $device->name . ' -', $productTitle);
                                    } else {

                                        if ($deviceType->code != 1 && $deviceType->code != 2) {
                                            // Smartphone or Tablet
                                            $productTitle = $device->brand->name . ' ' . $device->name . ' Compatible ' . $productTitle;
                                        } else {
                                            $productTitle = $device->brand->name . ' ' . $device->name . ' - ' . $productTitle;
                                        }
                                    }
                                }

                                $productTitle = str_replace('Apple ', '', $productTitle);
                                $productTitle = str_replace('apple', '', $productTitle);
                                $productTitle = trim($productTitle);
                                $productTitle = preg_replace('/ +/', ' ', $productTitle);


                                if (!$item_detail) {
                                    Log::info('Inserting new listing item detail for listing item ID: ' . $listitem->id . ' and device ID: ' . $device->id);
                                    $item_detail = $this->insertListingItemDetail($product_code, $listitem, $device);
                                }

                                // Fill a missing UPC from upc_codes. If none are left, stop the whole feed
                                // here: the client has to generate UPCs for the store first.
                                if (empty($item_detail->upc)) {
                                    $newUpc = $this->claimUnusedUpc();
                                    if (!$newUpc) {
                                        throw new UpcNotAvailableException($this->store_id, $this->storeDetail->name ?? null, $item_detail->id);
                                    }
                                    $item_detail->update(['upc' => $newUpc]);
                                    Log::info('Assigned UPC ' . $newUpc . ' to listing item detail ID: ' . $item_detail->id);
                                }

                                $collection_url = '';

                                $devicePage = StoreNavigationPage::where('store_id', $this->store_id)
                                    ->where('page_type', WebStorePageType::DEVICE)
                                    ->where('device_id', $device->id)
                                    ->first();

                                if ($item_detail->url) {
                                    $collection_url = $item_detail->url;
                                }

                                if (!isset($collection_url) || $collection_url == '') {
                                    $collection_url = $devicePage->url_path ?? '';
                                }

                                if ($store_sku != '') {
                                    $product_url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url, $collection_url);
                                }

                                $custom_label_1 = '';
                                $custom_label_2 = '';

                                if ($deviceType->code != 1 && $deviceType->code != 2) {
                                    $custom_label_2 = (isset($item_detail->device->name)) ? $item_detail->device->name : '';
                                } else {
                                    $custom_label_1 = (isset($item_detail->device->name)) ? $item_detail->device->name : '';
                                }

                                // item group id logic
                                //$itemGroupId = $item_detail->sku . '-' . $device->code;
                                $itemGroupId = $listitem->sku . '-' . $device->code;
                                $finalMainImage = $mainImage . '?device=' . $listitem->id;

                                $upcCode = (isset($item_detail->upc)) ? $item_detail->upc : '';

                                // Google wants the 14 digit GTIN, so a 12 digit UPC goes in padded.
                                if (strtolower($feed_type) == 'google' && $upcCode != '' && !str_starts_with($upcCode, '00')) {
                                    $upcCode = '00' . $upcCode;
                                }

                                /*if (!in_array($this->storeDetail->name,['UZID','NevaWireless','AccessoryWiz','OdeMobile','AccessoryChoice','GetAccessory'])) {
                                    $upcCode = $listitem->sku;
                                }*/

                                if(isset($this->storeDetail->include_upc_in_feed) && $this->storeDetail->include_upc_in_feed != 1){
                                    $upcCode = $listitem->sku;
                                }
                                if (strtolower($feed_type) == 'meta') {
                                    fputcsv($this->fileHandle, $this->metaRow([
                                        'id' => $item_detail->sku,
                                        'title' => $productTitle,
                                        'description' => $final_description,
                                        'condition' => (isset($itemDetail->product->condition)) ? strtolower($itemDetail->product->condition->name) : '',
                                        'link' => $product_url,
                                        'image_link' => (isset($finalMainImage)) ? $finalMainImage : '',
                                        'brand' => $manufacturer_name,
                                        'price' => $product_price,
                                        'google_product_category' => $ptype_id,
                                        'quantity' => (isset($listitem->qunatity)) ? $listitem->qunatity : 1000,
                                        'item_group_id' => (isset($device) ? $itemGroupId : ''),
                                        'color' => (isset($itemDetail->product->color->name)) ? $itemDetail->product->color->name : '',
                                        'size' => (isset($itemDetail->product->size->value)) ? $itemDetail->product->size->value . ' ' . $itemDetail->product->size->value_unit : '',
                                        'gtin' => $upcCode,
                                        'additional_images' => $images,
                                        'product_type' => $product_type,
                                        'custom_label_0' => (isset($item_detail->device->brand->name)) ? $item_detail->device->brand->name : '',
                                        'custom_label_1' => $custom_label_1,
                                        'custom_label_2' => $custom_label_2,
                                        'custom_label_3' => $custom_label_3,
                                        'custom_label_4' => (isset($itemDetail->product->product_sku)) ? $itemDetail->product->product_sku : '',
                                    ]));
                                } else {
                                    fputcsv($this->fileHandle, [
                                        $item_detail->sku,
                                        $productTitle,
                                        $final_description,
                                        $ptype_id,
                                        $product_type,
                                        $product_url,
                                        (isset($finalMainImage)) ? $finalMainImage : '',
                                        (isset($itemDetail->product->condition)) ? strtolower($itemDetail->product->condition->name) : '',
                                        'in stock',
                                        $product_price . ' USD',
                                        $upcCode,
                                        $manufacturer_name,
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
                                        $custom_label_1,
                                        $custom_label_2,
                                        $custom_label_3,
                                        (isset($itemDetail->product->product_sku)) ? $itemDetail->product->product_sku : '',
                                        $prodDetail
                                    ]);
                                }

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

    /**
     * One Meta feed line, in the order of the meta columns. Values are the ones the Google feed uses
     * for the matching column; Meta fields we have no data for stay empty.
     */
    private function metaRow(array $v): array
    {
        $condition = in_array($v['condition'], ['new', 'refurbished', 'used']) ? $v['condition'] : 'new';
        $price = $v['price'] !== '' && $v['price'] !== null ? number_format((float) $v['price'], 2, '.', '') . ' USD' : '';
        // product tags are limited to 110 characters each
        $tag0 = mb_substr($v['custom_label_0'], 0, 110);
        $tag1 = mb_substr($v['custom_label_3'], 0, 110);
        // Stores that keep UPCs out of the feed put the SKU in gtin. Meta rejects an invalid GTIN,
        // so only real 8/12/13/14 digit codes are sent.
        $gtin = preg_match('/^(\d{8}|\d{12,14})$/', (string) $v['gtin']) ? $v['gtin'] : '';

        return [
            mb_substr($v['id'], 0, 100),
            mb_substr($v['title'], 0, 200),
            mb_substr($v['description'], 0, 9999),
            'in stock',
            $condition,
            $v['link'],
            $v['image_link'],
            mb_substr($v['brand'], 0, 100),
            $price,
            $v['google_product_category'],
            '',                                    // fb_product_category
            $v['quantity'],
            '',                                    // sale_price
            '',                                    // sale_price_effective_date
            mb_substr($v['item_group_id'], 0, 100),
            '',                                    // gender
            mb_substr($v['color'], 0, 200),
            mb_substr(trim($v['size']), 0, 200),
            '',                                    // age_group
            '',                                    // material
            '',                                    // pattern
            'US::Standard:4.99 USD',
            '',                                    // shipping_weight
            '',                                    // offer_disclaimer
            '',                                    // offer_disclaimer_url
            '',                                    // video[0].url
            '',                                    // video[0].tag[0]
            $gtin,
            $tag0,
            $tag1,
            '',                                    // style[0]
            implode(',', array_slice($v['additional_images'] ?? [], 0, 20)),
            $v['product_type'],
            $v['id'],                              // mpn
            $v['custom_label_0'],
            $v['custom_label_1'],
            $v['custom_label_2'],
            $v['custom_label_3'],
            $v['custom_label_4'],
        ];
    }

    public function getProductCategoryCode($id)
    {
        $category = ProductType::where('id', $id)->first();
        while (isset($category) && $category->parent_id != null) {
            $parentCategory = ProductType::where('id', $category->parent_id)->first();

            $categorycode = ProductTypeStoreTypeCATLink::join('store_type_category', 'store_type_category.id', '=', 'product_type_store_type_category_link.channel_category_id')
                ->where('store_type_category.store_type_id', $this->category_store_type_detail->id)
                ->where('product_type_id', $category->id)
                ->orderBy('product_type_store_type_category_link.id', 'desc')
                ->select('store_type_category.code')
                ->first();

            if (isset($categorycode)) {
                return $categorycode->code;
            }
            $category = $parentCategory;
        }

        return $categorycode ? $categorycode->code : null;
    }

    private function can_add_device_to_title($productTitle, mixed $device)
    {
        if ($productTitle == null) {
            return true;
        }
        if (stripos(mb_strtolower($productTitle), mb_strtolower($device->name)) === false) {
            return true;
        }
        return false;
    }

    private function getManufacturerName($manufacturer)
    {
        if ($manufacturer == null) {
            return '';
        }
        $manufacturerLink = StoreTypeManufaturerLink::where('store_type_id', $this->store_type_detail->id)
            ->where('manufacturer_id', $manufacturer->id)
            ->first();

        if (isset($manufacturerLink)) {
            if (strtolower($manufacturerLink->manufacturer_name) == 'hco') {
                return $this->storeDetail->name;
            } else {
                return $manufacturerLink->manufacturer_name;
            }
        } else {
            if ($manufacturer->name == 'Use store as Brand' || strtolower($manufacturer->name) == 'hco') {
                return $this->storeDetail->name;
            } else {
                return $manufacturer->name;
            }
        }
    }
}
