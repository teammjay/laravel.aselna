<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\DevicesLinkedDevice;
use App\Models\DeviceTypes;
use App\Models\Feed;
use App\Models\GenerateProductImage;
use App\Models\ListingItemProductImage;
use App\Models\Manufacturer;
use App\Models\ProductImages;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Utils\FileUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedController extends Controller
{
    Private $perPage = 20;

    public function index(Request $request){
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();

        return view('feed.list', compact('stores'))->with('perPage', $this->perPage);
    }
    public function create()
    {

        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $manufacturers = Manufacturer::orderBy('name', 'asc')->get();
        $productTypes = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        $deviceTypes = DeviceTypes::all();
        $brands = Brand::orderBy('name', 'asc')->get();
        return view('feed.create_feed', compact('stores', 'manufacturers', 'productTypes', 'deviceTypes', 'brands'));
    }

    public function filter(Request $request){

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = Feed::with('store');

        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }

        $totalRecords = $query->count();

        $query->orderBy($columnName, $columnSortOrder);

        $feeds = $query->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$feeds)) {
            foreach ($feeds as $feed) {

                $data[] = array(
                    "store" => (isset($feed->store)) ? $feed->store->name : 0,
                    "marketplace" => isset($feed->marketplace) ? $feed->marketplace : '',
                    "file_type" => $feed->file_type,
                    "file_name" => $feed->file_name,
                    "created_at" => date('Y-m-d H:i:s', strtotime($feed->created_at)),
                    "action" => '<button class="btn btn-default download-feed" data-file="'.$feed->file_name.'" data-filepath="'.$feed->path.'"><i class="fas fa-fw fa-download"></i> Download</button>',
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;
    }

    public function generateFeed(Request $request){

        $query = StoreProduct::with('product','StoreTypeCategory','product.condition','product.Manufacturer','product.images', 'product.productType','product.interfaces', 'product.interfaces.interfaces');
        //$query = Catalog::with('StoreProduct', 'images', 'productType', 'interfaces.interfaces', 'pinterface.interfaces','condition','Manufacturer')->where('products.product_status', 1);

        $query->select('store_product.*');

        if ($request->has('store_id')) {
            $query->where('store_product.store_id', $request->store_id);
        }

        $query->leftJoin('products', 'store_product.product_id', '=', 'products.product_id');
        $query->where('products.oem_generic', 2);

        if ($request->has('product_code') && $request->product_code != '') {

            $query->whereIn('products.product_sku', $request->product_code);
        }

        if ($request->has('device') && $request->device != '') {
            if(!$request->has('interface') || $request->interface == ''){
                $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'store_product.product_id');
            }
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            $query->whereIn('device_interface.device_id', $request->device);
        }

        if ($request->has('device_series') && $request->device_series != '') {
            $devices = DevicesLinkedDevice::select('device_id')->whereIn('device_series_id', $request->device_series)->get();
            $query->leftJoin('product_interface', 'product_interface.product_id', '=', 'store_product.product_id');
            $query->leftJoin('device_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id');
            $query->whereIn('device_interface.device_id', $devices);
        }

        $products = $query->distinct()->get();

        /*echo '<pre>';
        echo $query->toSql();die;
        print_r($products);
        echo '</pre>';die;*/


        $store_id = $request->input('store_id');
        $storeDetail = Store::with('storeType')->where('id',$store_id)->first();
        $fileSystem = $storeDetail->fileSystem;
        $feed_type = $request->input('feed_type');
        $fileType = $request->input('file_type');

        if(isset($feed_type)){
            switch(strtolower($feed_type)){
                case 'google':

                    $columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];


                    $fname = date('Y-m-d-H-i-s');
                    $fileName = 'google_shopping_feed_' . $fname . '.'.$fileType;
                    $filePath = 'feeds/google-shopping/' . $fileName;
                    $stream = fopen('php://temp', 'w');

                    // Write the header row
                    fputcsv($stream, $columns);

                    // Write product rows

                    foreach ($products as $itemDetail) {

                        $images = array();

                        /*$this->generateProductImages($itemDetail, $storeDetail);
                        $productImages = GenerateProductImage::where('product_id', $itemDetail->product_id)->get();
                        foreach ($productImages as $image) {
                            $images[] = $fileSystem->url_base_path . $image->path;
                        }*/

                        $ptype_id = (isset($itemDetail->StoreTypeCategory) && !empty($itemDetail->StoreTypeCategory)) ? $itemDetail->StoreTypeCategory->store_type_code : '';
                        $product_type = '';
                        if(isset($itemDetail->product)) {
                            $typeDetail = ProductType::with('parent')->where('id', $itemDetail->product->product_type)->first();
                            $product_type = $this->getCategoryPath($typeDetail);
                            fputcsv($stream, [
                                $itemDetail->product->product_sku,
                                $itemDetail->product->product_title,
                                ($itemDetail->overwrite_description) ? $itemDetail->description : $itemDetail->product->product_description,
                                $ptype_id,
                                $product_type,
                                '',
                                (!empty($images)) ? implode(',', $images) : '',
                                (isset($itemDetail->product->condition)) ? $itemDetail->product->condition->name : '',
                                'in stock',
                                ($itemDetail->product->product_marketplace_price != '') ? $itemDetail->product->product_marketplace_price : $itemDetail->product->product_price,
                                '',
                                (isset($itemDetail->product->Manufacturer)) ? $itemDetail->product->Manufacturer->name : '',
                                (isset($itemDetail->product)) ? $itemDetail->product->product_sku : '',
                                '',
                                '',
                                '',
                                '',
                                '',
                                'US::Standard:4.9 USD',
                                '',
                                20,
                                'return_policy_days: "30"',
                                '',
                                '',
                                ($itemDetail->overwrite_title) ? $itemDetail->title : $itemDetail->product->product_title,
                                '',
                                $itemDetail->product->product_sku,
                                ''
                            ]);
                        }
                    }
                    rewind($stream);
                    FileUtility::saveToFile(public_path('feeds/google-shopping/' . $fileName), stream_get_contents($stream));
                    //Storage::put($filePath, stream_get_contents($stream));
                    fclose($stream);

                    break;
                case 'microsoft':
                    break;
            }
        }



        $feedData = [
            'store_id' => $store_id,
            'marketplace' => $feed_type,
            'file_type' => $fileType,
            'file_name' => $fileName,
            'path' => $filePath,
            'created_by' => 'admin',
        ];

        Feed::create($feedData);

        if ($request->ajax()) {
            return response()->json([
                'success' => true
            ]);
        }

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
