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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedController extends Controller
{
    private $perPage = 20;

    public function index(Request $request)
    {
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

    public function filter(Request $request)
    {

        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = Feed::with('store', 'parts');

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
                $action = '';

                // Check if feed has parts
                if ($feed->parts && $feed->parts->count() > 0) {
                    $partCounter = 0;
                        $action .= '<div class="d-flex flex-wrap" style="width: 300px;">';
                        foreach ($feed->parts->sortBy('part_number') as $part) {
                            $action .= '<button class="btn btn-sm btn-default download-feed m-1" style="flex: 0 0 30%;" data-file="' . $part->file_name . '" data-filepath="' . $part->path . '"><i class="fas fa-fw fa-download"></i> Part ' . $part->part_number . '</button>';
                            $partCounter++;
                            if ($partCounter % 3 == 0) {
                                $action .= '<div class="w-100"></div>';
                            }
                        }
                        $action .= '</div>';
                } else {
                    // Fallback for feeds that don't have parts
                    $action = '<button class="btn btn-default download-feed" data-file="' . $feed->file_name . '" data-filepath="' . $feed->path . '"><i class="fas fa-fw fa-download"></i> Download</button>';
                }


                $data[] = array(
                    "store" => (isset($feed->store)) ? $feed->store->name : 0,
                    "marketplace" => isset($feed->marketplace) ? $feed->marketplace : '',
                    "file_type" => $feed->file_type,
                    "file_name" => $feed->file_name,
                    "status" => ($feed->status) ? 'Completed' : 'Processing',
                    "created_at" => date('Y-m-d H:i:s', strtotime($feed->created_at)),
                    "action" => $action,
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

    public function generateFeed(Request $request)
    {

        /*echo '<pre>';
        echo $query->toSql();die;
        print_r($products);
        echo '</pre>';die;*/

        DB::beginTransaction();
        $store_id = $request->input('store_id');
        $feed_type = $request->input('feed_type');
        $fileType = $request->input('file_type');
        $product_code = $request->input('product_code');

        try {

            $requestData = [
                'store_id' => $store_id,
                'feed_type' => $feed_type,
                'fileType' => $fileType,
                'product_code' => $product_code,
                'device' => $request->input('device'),
                'interface' => $request->input('interface'),
                'device_series' => $request->input('device_series'),
            ];

            if (isset($feed_type)) {

                switch (strtolower($feed_type)) {
                    case 'google':
                        $fname = date('Y-m-d-H-i-s');
                        $fileName = 'google_shopping_feed_' . $fname . '.' . $fileType;
                        $filePath = 'feeds/google-shopping/' . $fileName;
                        break;
                    case 'microsoft':
                        $fname = date('Y-m-d-H-i-s');
                        $fileName = 'microsoft_feed_' . $fname . '.' . $fileType;
                        $filePath = 'feeds/microsoft/' . $fileName;
                        break;
                }

                $requestData['fileName'] = $fileName;
                $requestData['filePath'] = $filePath;

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
                DB::commit();
                Log::info('Dispatching ProcessFeedGenerationJob for feed ID: ' . $feed_id);
                Log::info('Request Data: ' . json_encode($requestData));
                ProcessFeedGenerationJob::dispatch($requestData, $feed_id);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing feed generation: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }

    }

    private function getProductListingItems($product_id)
    {

        $listingItems = ListingItem::with('itemDetail')->whereNotNull('store_product_id')->where('store_product_id', $product_id)->get();
    }

    /**
     * Download a feed file
     */
    public function downloadFeed(Request $request)
    {
        $fileName = $request->input('file');
        $filePath = $request->input('path');

        $fullPath = public_path($filePath);

        if (file_exists($fullPath)) {
            return response()->download($fullPath, $fileName);
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}
