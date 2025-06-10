<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchMagentoOrderJob;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\synccategorytospecification;
use App\Models\HDMessage;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Services\Auth\GoogleShoppingAuthService;
use App\Services\DataTable\DataTableService;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\Marketplace\Service\EbayService;
use App\Services\Marketplace\Service\GoogleShoppingService;
use App\Services\Marketplace\Service\MagentoService;
use App\Services\Marketplace\Service\ShopifyService;
use App\Services\Marketplace\Service\WalmartService;
use App\Utils\EmailMessageUtility;
use App\Utils\EndicialabelService;
use App\Utils\order\OrderOperationUtility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Constants\FeedStatus;
use Tests\Utils\order\OrderOperationUtilityTest;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->orderBy('name', 'asc')->get();
        //$eBayServie = new EbayService();
        //$eBayServie->generateFeed('5cc5b16a-a618-4e6e-b72d-41385242ffaf');die;
        return view('welcome', compact('stores'));
    }

    public function testfunction(){

        /*$requestId = (string) Str::uuid();
        $data = [
            'requesterId' => 'Iweb',
            'accountId' => '1257538',
            'requestId' => $requestId,
            'passPhrase' => 'Ag7ycm1274r2!a8hser2m',
            'transaction_id' => '431030',
            'pic_no' => '9400150105496000076315',
        ];
        $endicia = new EndicialabelService();
        $result = $endicia->cancelLabel($data);
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        echo 'here'; die;*/

        //$orderOperation = OrderOperation::with('order', 'store', 'store.storeType', 'operationItem', 'operationItem.orderItem', 'carrier', 'carrierService','shippingPackage','shipFrom')->where('id', 'bf9525a0-27f3-4ea6-87be-9db88bfda601')->first();
        //Log::info('Order Operation:'.json_encode($orderOperation));
        /*echo '<pre>';
        print_r($orderOperation);
        echo '</pre>';die;*/
       // $utility = new OrderOperationUtility();
        //$utility->createLabel($orderOperation);


        $smtpConfig = [
//            'host' => 'smtp.gmail.com',
//            'port' => 587,
//            'encryption' => 'tls',
//            'username' => 'odemobile.wm@gmail.com',
//            'password' => 'tcogxvjrbwxwudgh',
//            'from_address' => 'odemobile.wm@gmail.com',
//            'from_name' => 'Odemobile Walmart',
        ];

        $imapConfig = [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'awaccessory@gmail.com',
            'password' => 'qbdpyazqodnwsnju',
        ];

        /*$connector = new EmailMessageUtility($smtpConfig, $imapConfig);
        $receiveEmails = $connector->fetchEmails();


        if(count($receiveEmails ?? []) > 0){
            foreach($receiveEmails as $email){

                $order_id = $related_order_id = $po_no = $store_id = $reason = $customer_id = '';
                if(isset($email['parsedData']) && !empty($email['parsedData'])){
                    if(isset($email['parsedData']['customer_order_number'])){
                        $related_order_id = $email['parsedData']['customer_order_number'];
                        $orderDetail = Order::with('customer')->where('order_id',trim($related_order_id))->first();
                        if($orderDetail){
                            $order_id = $orderDetail->id;
                            $store_id = $orderDetail->store_id;
                            $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : '';
                        }
                    }

                    if(isset($email['parsedData']['purchase_order_number'])){
                        $po_no = $email['parsedData']['purchase_order_number'];
                    }

                    if(isset($email['parsedData']['reason'])){
                        $reason = $email['parsedData']['reason'];
                    }


                }
                $ticketData = [
                    'ticket_type_id' => 1,
                    'ticket_email' => $email['from'],
                    'status' => 1,
                    'related_order_id' => html_entity_decode($related_order_id),
                    'subject' => $email['subject'],
                    'message_id' => 1,
                    'notes' => $email['body'],
                    'reference_code' => 1,
                    'related_order_operation_id' => 1,
                    'order_id' => $order_id,
                    'store_id' => $store_id,
                    'ticket_date' => date('Y-m-d H:i:s'),
                    'ticket_code' => 1,
                    'message_read' => 0,
                    'replies' => 0,
                    'important' => 0,
                    'customer_merge_status' => 0,
                    'ticket_user_id' => $customer_id,
                    'sku' => 0,
                    'related_listing_item_id' => 0,
                ];

                $hd_ticket = Message::updateOrCreate([
                    'ticket_email' => $email['from'],
                ],$ticketData);

                if(isset($hd_ticket->id)) {
                    $hdMessageData = [
                        'ticket_id' => $hd_ticket->id,
                        'subject' => $email['subject'],
                        'message_date' => $email['date'],
                        'to_' => $email['to'],
                        'cc' => $email['cc'],
                        'bcc' => $email['bcc'],
                        'message_id' => '',
                        'messaging_account_id' => 'bea29234-1700-c078-9a74-13b52b47e85a',
                        'from_email' => $email['from'],
                        'from_name' => $email['name'],
                        'message' => $email['body'],
                        'in_reply_to' => '',
                        'draft' => 0
                    ];

                    $hd_ticket = HDMessage::create($hdMessageData);
                }
            }
        }*/
        //dd($emails);

        /*$stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();
        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                $storeMessageAccount = StoreMessageAccount::with('incomingAccount', 'outgoingAccount')->where('store_id', $store->id)->get();
                echo '<pre>';
                print_r($storeMessageAccount);
                echo '</pre>';
            }die;
        }*/

        /*$stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);
                if ($store->name != 'AccessoryWiz') {
                    continue;
                }
                $shopifyService = new ShopifyService();
                $orders = $shopifyService->getOrders($store);
                echo '<pre>';
                print_r($orders);
                echo '</pre>';

            }die;
        }*/

        /*$stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','walmart')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if ($store->name != 'AWAccessory') {
                    continue;
                }
                $walmartService = new WalmartService();
                $orders = $walmartService->getOrders($store);
                echo '<pre>';
                print_r($orders);
                echo '</pre>';die;
            }
        }*/

        /*$stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                if ($store->name != 'AccessoryWiz') {
                    continue;
                }
                $shopifyService = new ShopifyService();
                $orders = $shopifyService->getOrders($store);
                echo '<pre>';
                print_r($orders);
                echo '</pre>';
            }
        }*/

        /*$stores = Store::with('fileSystem','storeType')->where('name','AccessoryWiz')->get();
        //dd($stores);
        echo '<pre>';
        print_r($stores);
        echo '</pre>';die;*/


        Bus::chain([
            //new SubmitShopifyProductsJob(),
            //new synccategorytospecification(),
            new FetchShopifyOrders(),
            //new EbayListingItemSyncJob()
            //new SyncAllOrderStatusJob()
            //new FetchWalmartOrdersJob()
        ])->dispatch();
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
        $feedId = $request->feed_id;
        $listingFeed = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();
        $storeType = $listingFeed->store->storeType->name;

        FeedGenerationJob::dispatch($listingId,$feedId);

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
            $feedId = $request->feed_id;
            $listingInfo = Listing::where('id', $listingId)->first();
            $feedInfo = ListingFeed::where('id', $feedId)->first();


            $storeType = $listingInfo->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);

            $marketplaceService->submitFeed($feedId, $listingId);

            $feedInfo->update([
                'status' => FeedStatus::PROCESSING,
            ]);

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
            $feedID = $request->feed_id;

            $storeType = $listingFeed->store->storeType->name;

            $fileId = strtolower($storeType) == 'walmart' ? $feedID : $listingId;

            $directory = public_path('result-feeds/' . strtolower($storeType) . '/');
            $files = [];
            Log::info('Directory: ' . $directory);
            if (is_dir($directory)) {
                Log::info('Directory exists');
                foreach (scandir($directory, SCANDIR_SORT_DESCENDING) as $file) {
                    Log::info('File: ' . $file);
                    if (strpos($file, $fileId) === 0 && preg_match('/\.(json|xml)$/', $file)) {
                        $files[] = 'result-feeds/' . strtolower($storeType) . '/' . $file;
                    }
                }
            }

            Log::info('Files: ' . json_encode($files));
            if (!empty($files) && false) {
                return response()->json(['success' => true,'file_path' => $files[0]]);
            } else {
                $marketplaceService = MarketplaceServiceFactory::create($storeType);

                $filePath = $marketplaceService->getResultFeed($listingId,$feedID);

                return response()->json(['success' => true,'file_path' => $filePath]);
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

    }
}
