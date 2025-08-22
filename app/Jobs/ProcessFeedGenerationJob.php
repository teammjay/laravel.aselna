<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DevicesLinkedDevice;
use App\Models\Feed;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ProductImages;
use App\Models\ProductSpecification;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreType;
use App\Utils\FileUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFeedGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30000; // Set a timeout for the job

    protected $products;
    protected $feed_id;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($products, $requestdata, $feed_id)
    {
        $this->products = $products;
        $this->data = $requestdata;
        $this->feed_id = $feed_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try{

            $feed_type = $this->data['feed_type'];
            $store_id = $this->data['store_id'];
            $storeDetail = Store::with('storeType')->where('id',$store_id)->first();
            $fileSystem = $storeDetail->fileSystem;
            $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
            switch(strtolower($feed_type)){
                case 'google':

                    $columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];


                    $fileName = $this->data['fileName'];
                    $filePath = $this->data['filePath'];

                    $stream = fopen('php://temp', 'w');

                    // Write the header row
                    fputcsv($stream, $columns);

                    // Write product rows

                    foreach ($this->products as $itemDetail) {

                        $images = array();

                        /*$this->generateProductImages($itemDetail, $storeDetail);
                        $productImages = GenerateProductImage::where('product_id', $itemDetail->product_id)->get();
                        foreach ($productImages as $image) {
                            $images[] = $fileSystem->url_base_path . $image->path;
                        }*/

                        $ptype_id = (isset($itemDetail->StoreTypeCategory) && !empty($itemDetail->StoreTypeCategory)) ? $itemDetail->StoreTypeCategory->store_type_code : '';
                        $product_type = '';
                        if(isset($itemDetail->product)) {
                            $product_code = $itemDetail->product->product_sku;
                            $keywords = ProductTitleKeyword::select('product_id', 'keyword')->where('product_id', $itemDetail->product_id)->orderBy('priority', 'asc')->limit(5)->get();
                            $title = '';
                            $title_keyword = [];
                            if (!empty($keywords)) {
                                foreach ($keywords as $key => $val) {
                                    $title_keyword[] = trim($val['keyword']);
                                }
                                $title = implode(' ',$title_keyword);
                            }

                            $productSpecification = ProductSpecification::where('product_id', $itemDetail->product_id)->where('store_type_id', $store_type_detail->id)->get();
                            $section = 'General';
                            $kvSep = ':';
                            $pairSep = ',';
                            $parts = [];
                            foreach ($productSpecification as $specification) {

                                $section = trim((string) $section);
                                $key     = trim((string) $specification->name);
                                $value   = trim((string) $specification->value);

                                if ($section === '' || $key === '' || $value === '') {
                                    continue;
                                }

                                $parts[] = "{$section}{$kvSep}{$key}{$kvSep}{$value}";
                            }

                            $prodDetail = implode($pairSep, $parts);

                            //echo $product_code;
                            //$compatibleDevice = $this->getCompatibleDevices($product_id,$request->device,$request->device_series);
                            $query = ListingItem::with('brand','device','itemDetails','itemDetail','itemDetail.device','itemDetail.carrier')->whereNotNull('sku')->whereLike('sku','%'.$product_code.'%');
                            $listingItems = $query->get();
                            /*echo '<pre>';
                            echo $query->toSql();die;
                            print_r($listingItems);
                            echo '</pre>';die;*/
                            if($listingItems){
                                foreach($listingItems as $listitem){

                                    $product_url = '';
                                    $store_sku = $listitem->store_sku;
                                    $store_type = isset($shipmentDetail->store->storeType->name) ? $storeDetail->storeType->name : '';
                                    $site_url = isset($shipmentDetail->store->store_url) ? $storeDetail->store_url : '';
                                    $store_url = isset($listitem->store_url) ? $listitem->store_url : '';
                                    if($store_sku != ''){
                                        $product_url = \App\Utils\ListingUtility::generateItemUrl($store_type, $store_sku, $site_url, $store_url);
                                    }

                                    if(isset($listitem->itemDetails)){
                                        foreach($listitem->itemDetails as $item_detail){
                                            $typeDetail = ProductType::with('parent')->where('id', $itemDetail->product->product_type)->first();
                                            $product_type = $this->getCategoryPath($typeDetail);

                                            $productTitle = '';
                                            if(isset($listitem->brand->name)){
                                                $productTitle .= $listitem->brand->name;
                                            }

                                            if(isset($listitem->device->name)){
                                                $productTitle .= ' '.$listitem->device->name.' ';
                                            }

                                            $productTitle .= $title;

                                            fputcsv($stream, [
                                                $item_detail->sku,
                                                $productTitle,
                                                ($itemDetail->overwrite_description) ? $itemDetail->description : $itemDetail->product->product_description,
                                                $ptype_id,
                                                $product_type,
                                                $product_url,
                                                (!empty($images)) ? implode(',', $images) : '',
                                                (isset($itemDetail->product->condition)) ? $itemDetail->product->condition->name : '',
                                                'in stock',
                                                (($itemDetail->product->product_marketplace_price != '') ? $itemDetail->product->product_marketplace_price : $itemDetail->product->product_price).' USD',
                                                (isset($item_detail->upc)) ? $item_detail->upc : '',
                                                (isset($itemDetail->product->Manufacturer)) ? $itemDetail->product->Manufacturer->name : '',
                                                $item_detail->sku,
                                                '',
                                                '',
                                                '',
                                                (isset($itemDetail->product->color->name)) ? $itemDetail->product->color->name : '',
                                                (isset($itemDetail->product->size->value)) ? $itemDetail->product->size->value .' '.$itemDetail->product->size->value_unit  : '',
                                                'US::Standard:3.99 USD',
                                                '',
                                                (isset($listitem->qunatity)) ? $listitem->qunatity : 500,
                                                'return_policy_days: "30"',
                                                (isset($listitem->brand->name)) ? $listitem->brand->name : '',
                                                (isset($listitem->device->name)) ? $listitem->device->name : '',
                                                '',
                                                ($itemDetail->overwrite_title) ? $itemDetail->title : $itemDetail->product->product_title,
                                                (isset($itemDetail->product->product_sku)) ? $itemDetail->product->product_sku : '',
                                                $prodDetail
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    rewind($stream);
                    FileUtility::saveToFile(public_path('feeds/google-shopping/' . $fileName), stream_get_contents($stream));
                    //Storage::put($filePath, stream_get_contents($stream));
                    fclose($stream);

                    if ($this->feed_id != '') {
                        $feedDetail = Feed::findOrFail($this->feed_id);
                        Log::info('Feed detail: '.json_encode($feedDetail));
                        $feedDetail->update([
                            'status' => 1,
                        ]);
                    }

                    break;
                case 'microsoft':
                    break;
            }

        }catch (\Exception $e) {
            Log::error('Error processing feed generation: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    private function getCompatibleDevices($product_id,$devices,$deviceSeries){

        $field = 'carrier.name as carrier_name';
        $field2 = 'carrier.id as carrier_id';

        $query = Device::select('device.*', 'product_interface.product_id', 'products.product_sku', $field, $field2)->with('brand', 'rating')->with('deviceType', 'deviceSeries');
        $query->leftJoin('brands', 'brands.id', '=', 'device.brand_id');
        $query->leftJoin('device_interface', 'device_interface.device_id', '=', 'device.id');
        $query->leftJoin('product_interface', 'product_interface.interface_id', '=', 'device_interface.interface_id');
        $query->leftJoin('products', 'product_interface.product_id', '=', 'products.product_id');

        $query->leftJoin('device_carrier', 'device_carrier.device_id', '=', 'device.id');
        $query->leftJoin('carrier', 'carrier.id', '=', 'device_carrier.carrier_id');
        $query->whereNotNull('carrier.id');

        $query->whereIn('product_interface.product_id', $product_id);

        if ($devices) {
            $query->whereIn('device.id', $devices);
        }
        if ($deviceSeries) {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $deviceSeries)->get();
            $query->whereIn('device.id', $devices);
        }
        $query->orderBy('brands.name', 'asc');
        $query->orderBy('device.name', 'asc');

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
