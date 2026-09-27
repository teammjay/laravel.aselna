<?php

namespace App\Http\Controllers;

use App\Constants\FeedStatus;
use App\Jobs\ProcessFeedGenerationJob;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DeviceTypes;
use App\Models\Feed;
use App\Models\FeedConfiguration;
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
        $deviceRatings = DeviceRating::orderBy('value', 'asc')->get();
        return view('feed.create_feed', compact('stores', 'manufacturers', 'productTypes', 'deviceTypes', 'brands', 'deviceRatings'));
    }

    public function saveConfiguration(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'store_id' => 'required',
                'feed_type' => 'required',
                'file_type' => 'required',
            ]);

            $filters = [
                'product_code' => $request->input('product_code', []),
                'excluded_product_code' => $request->input('excluded_product_code', []),
                'device' => $request->input('device', []),
                'excluded_device' => $request->input('excluded_device', []),
                'included_ratings' => $request->input('included_ratings', []),
                'excluded_ratings' => $request->input('excluded_ratings', []),
                'manufacturer' => $request->input('manufacturer', []),
                'product_type' => $request->input('product_type', []),
                'brand' => $request->input('brand', []),
            ];

            FeedConfiguration::updateOrCreate(
                [
                    'name' => $request->name,
                    'store_id' => $request->store_id
                ],
                [
                    'feed_type' => $request->feed_type,
                    'file_type' => $request->file_type,
                    'filters' => $filters,
                    'created_by' => auth()->user()->name ?? 'admin',
                ]
            );

            return response()->json(['success' => true, 'message' => 'Configuration saved successfully']);
        } catch (\Exception $e) {
            Log::error('Error saving feed configuration: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getConfigurations(Request $request)
    {
        $store_id = $request->query('store_id');
        $query = FeedConfiguration::with('store.storeType');
        if ($store_id) {
            $query->where('store_id', $store_id);
        }
        $configurations = $query->orderBy('created_at', 'desc')->get();

        // Device filters store ids only; send the names so "Load" can show them
        $deviceIds = $configurations->flatMap(fn ($config) => array_merge(
            (array) ($config->filters['device'] ?? []),
            (array) ($config->filters['excluded_device'] ?? [])
        ))->filter()->unique()->values();
        $deviceNames = Device::whereIn('id', $deviceIds)->pluck('name', 'id');

        foreach ($configurations as $config) {
            $ids = array_merge((array) ($config->filters['device'] ?? []), (array) ($config->filters['excluded_device'] ?? []));
            $config->setAttribute('device_names', (object) collect($ids)->mapWithKeys(fn ($id) => [$id => $deviceNames[$id] ?? $id])->all());
        }

        return response()->json(['success' => true, 'data' => $configurations]);
    }

    public function generateFromConfiguration($id)
    {
        DB::beginTransaction();
        try {
            $config = FeedConfiguration::findOrFail($id);
            $store_id = $config->store_id;
            $feed_type = $config->feed_type;
            $fileType = $config->file_type;
            $filters = $config->filters;

            $requestData = [
                'store_id' => $store_id,
                'feed_type' => $feed_type,
                'fileType' => $fileType,
                'product_code' => $filters['product_code'] ?? [],
                'excluded_product_code' => $filters['excluded_product_code'] ?? [],
                'device' => $filters['device'] ?? [],
                'excluded_device' => $filters['excluded_device'] ?? [],
                'included_ratings' => $filters['included_ratings'] ?? [],
                'excluded_ratings' => $filters['excluded_ratings'] ?? [],
                'manufacturer' => $filters['manufacturer'] ?? [],
                'product_type' => $filters['product_type'] ?? [],
                'brand' => $filters['brand'] ?? [],
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
                    case 'meta':
                        $fname = date('Y-m-d-H-i-s');
                        $fileName = 'meta_feed_' . $fname . '.' . $fileType;
                        $filePath = 'feeds/meta/' . $fileName;
                        break;
                    default:
                        throw new \Exception('Invalid feed type');
                }

                $requestData['fileName'] = $fileName;
                $requestData['filePath'] = $filePath;

                $feedData = [
                    'store_id' => $store_id,
                    'marketplace' => $feed_type,
                    'file_type' => $fileType,
                    'file_name' => $fileName,
                    'path' => $filePath,
                    'status' => FeedStatus::PROCESSING,
                    'created_by' => auth()->user()->name ?? 'admin',
                ];

                $marketfeed = Feed::create($feedData);
                $feed_id = $marketfeed->id;
                DB::commit();
                
                ProcessFeedGenerationJob::dispatch($requestData, $feed_id);
                return response()->json(['success' => true, 'message' => 'Feed generation job dispatched successfully.']);
            }
            
            throw new \Exception('Feed type not set');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating feed from configuration: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteConfiguration($id)
    {
        try {
            $config = FeedConfiguration::findOrFail($id);
            $config->delete();
            return response()->json(['success' => true, 'message' => 'Configuration deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
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
                }
                else if ($feed->status == FeedStatus::PROCESSED) {
                    // Fallback for feeds that don't have parts
                    $action = '<button class="btn btn-default download-feed" data-file="' . $feed->file_name . '" data-filepath="' . $feed->path . '"><i class="fas fa-fw fa-download"></i> Download</button>';
                }


                $data[] = array(
                    "store" => (isset($feed->store)) ? $feed->store->name . ' - ' . $feed->store->storeType->name : 0,
                    "marketplace" => isset($feed->marketplace) ? $feed->marketplace : '',
                    "file_type" => $feed->file_type,
                    "file_name" => $feed->file_name,
                    "status" => FeedStatus::getStatusName($feed->status),
                    "error_text" => $feed->error_text ?? '',
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
                'excluded_device' => $request->input('excluded_device', []),
                'excluded_product_code' => $request->input('excluded_product_code', []),
                'included_ratings' => $request->input('included_ratings'),
                'excluded_ratings' => $request->input('excluded_ratings'),
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
                    case 'meta':
                        $fname = date('Y-m-d-H-i-s');
                        $fileName = 'meta_feed_' . $fname . '.' . $fileType;
                        $filePath = 'feeds/meta/' . $fileName;
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
                    'status' => FeedStatus::PROCESSING,
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
        }
        catch (\Exception $e) {
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