<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFeedGenerationJob;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\DevicesLinkedDevice;
use App\Models\DeviceTypes;
use App\Models\Feed;
use App\Models\GenerateProductImage;
use App\Models\ListingItem;
use App\Models\ListingItemProductImage;
use App\Models\Manufacturer;
use App\Models\ProductImages;
use App\Models\ProductSpecification;
use App\Models\ProductTitleKeyword;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreType;
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
                    "status" => ($feed->status) ? 'Completed' : 'Processing',
                    "created_at" => date('Y-m-d H:i:s', strtotime($feed->created_at)),
                    "action" => ($feed->status) ? '<button class="btn btn-default download-feed" data-file="'.$feed->file_name.'" data-filepath="'.$feed->path.'"><i class="fas fa-fw fa-download"></i> Download</button>' : '',
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

        $query = StoreProduct::with('product','product.size','StoreTypeCategory','product.condition','product.Manufacturer','product.images', 'product.productType','product.interfaces', 'product.interfaces.interfaces','product.color');
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

        //$query->orderBy('products.product_sku','asc');
        //$query->limit(10);
        $products = $query->distinct()->get();

        /*echo '<pre>';
        echo $query->toSql();die;
        print_r($products);
        echo '</pre>';die;*/


        $store_id = $request->input('store_id');
        $feed_type = $request->input('feed_type');
        $fileType = $request->input('file_type');

        $requestData = [
            'store_id' => $store_id,
            'feed_type' => $feed_type,
            'fileType' => $fileType,
        ];

        if(isset($feed_type)){

            switch(strtolower($feed_type)) {
                case 'google':
                    $fname = date('Y-m-d-H-i-s');
                    $fileName = 'google_shopping_feed_' . $fname . '.'.$fileType;
                    $filePath = 'feeds/google-shopping/' . $fileName;
                    break;
                case 'microsoft':
                    $fname = date('Y-m-d-H-i-s');
                    $fileName = 'microsoft_feed_' . $fname . '.'.$fileType;
                    $filePath = 'feeds/microsoft/' . $fileName;
                    break;
            }

            $requestData['fileName']= $fileName;
            $requestData['filePath']= $filePath;

            $feedData = [
                'store_id' => $store_id,
                'marketplace' => $feed_type,
                'file_type' => $fileType,
                'file_name' => $fileName,
                'path' => $filePath,
                'status' => 0,
                'created_by' => 'admin',
            ];

            $marketfeed = Feed::create($feedData);
            $feed_id = $marketfeed->id;
            ProcessFeedGenerationJob::dispatch($products, $requestData, $feed_id);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true
            ]);
        }

    }

    private function getProductListingItems($product_id){

        $listingItems = ListingItem::with('itemDetail')->whereNotNull('store_product_id')->where('store_product_id',$product_id)->get();


    }


}
