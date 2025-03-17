<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchMagentoOrderJob;
use App\Jobs\FetchShopifyOrders;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\Store;
use App\Services\Auth\GoogleShoppingAuthService;
use App\Services\DataTable\DataTableService;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\Marketplace\Service\EbayService;
use App\Services\Marketplace\Service\GoogleShoppingService;
use App\Services\Marketplace\Service\MagentoService;
use App\Services\Marketplace\Service\ShopifyService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->orderBy('name', 'asc')->get();
        //$eBayServie = new EbayService();
        //$eBayServie->getOrders();
        //$test = $eBayServie->generateFeed('fd484f04-2ae4-454e-82f6-3e1ea34de65f');die;
        //$eBayServie->submitFeed('221284a9-d400-4712-9eee-a9850f52dd88','c9a99498-1aac-4a4a-a8f6-a27df41b8763');
        //$eBayServie->getInputFeed('task-1-1274330','c9a99498-1aac-4a4a-a8f6-a27df41b8763');
        //echo 'sd';die;
        //$googleService = new GoogleShoppingAuthService();
        //$magentoService = new MagentoService();
        //$magentoService->getOrders();
        //$shopifyService = new ShopifyService();
        //$shopifyService->getOrders();

        /*Bus::chain([
            new FetchShopifyOrders(),
        ])->dispatch();*/

        //$test = $googleService->generateFeed('0310236d-295f-bd6d-730d-35fcc324bdf9');
        //echo $test;die;
        return view('welcome', compact('stores'));
    }

    public function getListingData(Request $request)
    {
        $query = ListingFeed::query()
            ->when($request->input('store_id'), function ($query, $storeId) {
                $query->where('store_id', $storeId);
            })
            //->where('listing_id','=','8cf6ffb3-d1c4-4885-2eb9-e794a7df2a49')
            ->with('store', 'store.storeType');

        $totalRecords = ListingFeed::query()
            ->when($request->input('store_id'), function ($query, $storeId) {
                $query->where('store_id', $storeId);
            })
            ->count();

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('feed_type'), function ($query, $searchValue) {
                    $query->where('feed_type', '=', $searchValue);
                })
                ->when($request->input('feed_id'), function ($query) use ($request) {
                    QueryFilters::applyFilter($query, $request, 'id', 'feed_id_operator', 'feed_id');
                })
                ->when($request->input('listing_id'), function ($query) use ($request) {
                    QueryFilters::applyFilter($query, $request, 'listing_id', 'listing_id_operator', 'listing_id');
                });
        });

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $sortColumn = $request->input('columns.' . $columnIndex . '.data', 'id');
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName === 'store') {
                $query->join('store', 'store.id', '=', 'listing_feeds.store_id')
                    ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                    ->orderByRaw("CONCAT(store.name, ' - ', store_type.name) $sortDirection");
            } else {
                // Default sorting logic
                $query->orderBy($sortColumn, $sortDirection);
            }
        });

        $data = $dataTableService->handle();

        // Return data in DataTable format
        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $data->total(),
            'data' => $data->items(),
        ]);
    }

    public function generateFeedFile(Request $request)
    {
        $request->validate([
            'listing_id' => 'required',
        ]);

        $listingId = $request->listing_id;
        $listingFeed = Listing::where('id', $listingId)->first();
        $storeType = $listingFeed->store->storeType->name;

        FeedGenerationJob::dispatch($listingId);

        Log::info('Store Type: ' . $storeType);

        //file may be json or xml check for the file type
        $directory = public_path('feeds/' . strtolower($storeType) . '/');
        $files = [];
        Log::info('Directory: ' . $directory);
        if (is_dir($directory)) {
            Log::info('Directory exists');
            foreach (scandir($directory) as $file) {
                Log::info('File: ' . $file);
                if (strpos($file, $listingId) === 0 && preg_match('/\.(json|xml)$/', $file)) {
                    $files[] = 'feeds/' . strtolower($storeType) . '/' . $file;
                }
            }
        }

        Log::info('Files: ' . json_encode($files));
        if (!empty($files)) {
            return response()->json(['file_path' => $files[0]]);
        }

        // get StoreType froms store that is inside listingfeed

        // give the url to download the file
        return response()->json(['generating' => true]);
    }

    public function submitFeedFile(Request $request)
    {
        try {
            $request->validate([
                'listing_id' => 'required',
            ]);

            $listingId = $request->listing_id;
            $listingInfo = Listing::where('id', $listingId)->first();

            $storeType = $listingInfo->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);

            $marketplaceService->submitFeed('', $listingId);

            // $marketplaceService->getResultFeed($listingId);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::info('Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function generateResultFeedFile(Request $request)
    {
        try {
            $request->validate([
                'listing_id' => 'required',
            ]);

            $listingId = $request->listing_id;
            $listingFeed = Listing::where('id', $listingId)->first();

            $storeType = $listingFeed->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);

            $filePath = $marketplaceService->getResultFeed($listingId);;

            return response()->json(['success' => true,'file_path' => $filePath]);
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

    }
}
