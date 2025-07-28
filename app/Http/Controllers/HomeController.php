<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Helpers\QueryFilters;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchMagentoOrderJob;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\synccategorytospecification;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\HDMessage;
use App\Models\HdMessageAttachment;
use App\Models\HdMessagePart;
use App\Models\HdMessageTemplate;
use App\Models\HdTicketType;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\ProductType;
use App\Models\Sequence;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Models\SysFile;
use App\Services\Auth\GoogleShoppingAuthService;
use App\Services\Auth\ShopifyAuthService;
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
use Illuminate\Support\Facades\DB;
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


            /*$store = Store::with('storeType')->where('id', '3fab8878-e036-58c7-db30-addd3058875c')->first();
            $authService = new ShopifyAuthService($store->secret_key, $store->service_url);
            $orderId = "gid://shopify/Order/6749583638833";
            $transactions = $authService->getTransactions($orderId);
            die;*/

         /*Bus::chain([
            //new SubmitShopifyProductsJob(),
            //new synccategorytospecification(),
            //new FetchShopifyOrders(),
            //new EbayListingItemSyncJob()
            //new SyncAllOrderStatusJob()
            //new FetchWalmartOrdersJob()
            //new FetchEmailMessagesJob()
        ])->dispatch();*/
        //die;

        /*$productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();
        $products = Catalog::select('*')->with('productType','productKeyFeatures','productColor')
            ->whereNotIn('product_type', $productTypes)
            ->whereNull('parent_id')
            ->where('product_status','!=','discontinued')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        echo '<pre>';
        print_r($products);
        echo '</pre>';die;*/

        /*$store = Store::findOrFail('3fab8878-e036-58c7-db30-addd3058875c');
        $shopifyService = new ShopifyService();
        $shopifyService->refundOrder('6665896263985',$store);
        die;*/

        /*$requestId = (string) Str::uuid();
        $data = [
            'requesterId' => 'Iweb',
            'accountId' => '1257538',
            'requestId' => $requestId,
            'passPhrase' => 'Ag7ycm1274r2!a8hser2m',
            'transaction_id' => '431030',
            'pic_no' => '9400150105798015102707',
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

        /*$imapConfig = [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'accessorychoice.cs@gmail.com',
            'password' => 'qazbvuudllciqpga',
        ];*/
        //die;
        $imapConfig = [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'odemobile.wm@gmail.com',
            'password' => 'tcogxvjrbwxwudgh',
        ];

        $connector = new EmailMessageUtility($smtpConfig, $imapConfig);
        $receiveEmails = $connector->fetchEmails('Walmart', '2025-07-15','INBOX');

        //$store = Store::with('storeType')->where('id','0dd4de79-bba1-c47a-a7aa-822e6f015b8e')->first();
        $store = Store::with('storeType')->where('id','f5ebde6c-2bcc-03f5-4d62-ef4692f87314')->first();
        $store_id = $store->id;
        if (count($receiveEmails ?? []) > 0) {
            foreach ($receiveEmails as $email) {
                // get as max date for next filter
                /*if (isset($email['date']) && $email['date'] > $last_filter_date) {
                    $last_filter_date = $email['date'];
                }*/
                Log::info('Date :: '. $email['date']);
                $order_id = $related_order_id = $po_no = $reason = $customer_id = $order_operation_id = $reference_code = NULL;
                $ticket_type_code = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;

                if (isset($email['from'])) {
                    $customerDetail = Customer::select('customers.*')
                        ->join('customer_emails as CE', 'CE.customer_id', '=', 'customers.id', 'left')
                        ->whereLike('customers.email', '%' . $email['from'] . '%')
                        ->orWhere('CE.email_address', 'like', '%' . $email['from'] . '%')
                        ->first();

                    if ($customerDetail) {
                        $customer_id = $customerDetail->id;
                    }
                }

                if (isset($email['parsedData']) && !empty($email['parsedData'])) {
                    if (isset($email['parsedData']['order_id'])) {
                        $order_id = html_entity_decode($email['parsedData']['order_id']);
                        $orderDetail = Order::with('customer', 'orderOperation')->where('order_id', trim($order_id))->first();
                        if ($orderDetail) {
                            $related_order_id = $orderDetail->id;
                            $store_id = $orderDetail->store_id;
                            $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
                            $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : NULL;
                        }
                    }

                    if (isset($email['parsedData']['purchase_order_number'])) {
                        $po_no = html_entity_decode($email['parsedData']['purchase_order_number']);
                    }

                    if (isset($email['parsedData']['reason'])) {
                        $reason = html_entity_decode($email['parsedData']['reason']);
                    }

                    if (isset($email['parsedData']['ticket_type_id'])) {
                        $ticket_type_code = html_entity_decode($email['parsedData']['ticket_type_id']);
                    }

                    if (isset($email['parsedData']['reference_id'])) {
                        $reference_code = html_entity_decode($email['parsedData']['reference_id']);
                    }
                }

                if ($customer_id == NULL) {
                    if (isset($email['name'])) {
                        $nameArr = explode(' ', $email['name']);
                        $firstname = $nameArr[0];
                        $lastname = isset($nameArr[1]) ? $nameArr[1] : '';
                    }

                    $customerData = [
                        'first_name' => $firstname,
                        'last_name' => $lastname,
                        'full_name' => isset($email['name']) ? $email['name'] : '',
                        'email' => $email['from'],
                        'user_id' => $email['from']
                    ];

                    $customer_detail = Customer::updateOrCreate([
                        'email' => $email['from'],
                    ], $customerData);

                    $customer_id = $customer_detail->id;

                    $customerEmailData = [
                        'customer_id' => $customer_id,
                        'email_address' => $email['from'],
                    ];
                    CustomerEmail::updateOrCreate([
                        'customer_id' => $customer_id,
                        'email_address' => $email['from']
                    ], $customerEmailData);
                } else {
                    $customerDetail = Customer::findOrFail($customer_id);
                    if ($customerDetail->email != $email['from']) {
                        $customerEmailData = [
                            'customer_id' => $customer_id,
                            'email_address' => $email['from'],
                        ];
                        CustomerEmail::updateOrCreate([
                            'customer_id' => $customer_id,
                            'email_address' => $email['from']
                        ], $customerEmailData);
                    }
                }

                $ticket_code = $store->short_code . '-' . $this->getNextTicketNumberForStore($store->id);
                $ticketTypeDetail = HdTicketType::where('code', $ticket_type_code)->first();
                $ticketData = [
                    'ticket_type_id' => $ticketTypeDetail->id,
                    'ticket_email' => trim($email['from']),
                    'status' => HdTicketStatus::NEEDS_ACTION,
                    'related_order_id' => $related_order_id,
                    'subject' => trim($email['subject']),
                    'message_id' => trim($email['message_id']),
                    'notes' => NULL,
                    'customer_id' => $customer_id,
                    'reference_code' => $reference_code,
                    'related_order_operation_id' => $order_operation_id,
                    'order_id' => trim($order_id),
                    'store_id' => $store->id,
                    'ticket_date' => $email['date'],
                    'ticket_code' => $ticket_code,
                    'message_read' => 0,
                    'replies' => 0,
                    'important' => 0,
                    'customer_merge_status' => 0,
                    'ticket_user_id' => $customer_id,
                    'sku' => NULL,
                    'related_listing_item_id' => NULL,
                ];

                Log::info('Email From' . $email['from'] . ' Message ID: ' . $email['message_id']);
                $ticketDetail = Message::where('message_id', trim($email['message_id']))->first();

                if ($ticketDetail) {
                    if ($order_id) {
                        $ticketDetail->order_id = $order_id;
                        $ticketDetail->ticket_type_id = $ticketTypeDetail->id;
                        $ticketDetail->save();
                    }
                    $hd_ticket = $ticketDetail;
                } else {
                    $hd_ticket = Message::create($ticketData);
                }

                if (isset($hd_ticket->id)) {
                    $hdMessageData = [
                        'ticket_id' => $hd_ticket->id,
                        'subject' => trim($email['subject']),
                        'message_date' => $email['date'],
                        'to_' => $email['to'],
                        'cc' => isset($email['cc']) ? $email['cc'] : NULL,
                        'bcc' => isset($email['bcc']) ? $email['bcc'] : NULL,
                        'message_id' => trim($email['message_id']),
                        'messaging_account_id' => '75962aeb-7ac6-476c-a786-e5428447193d',
                        'from_email' => trim($email['from']),
                        'from_name' => trim($email['name']),
                        'message' => $email['body'],
                        'in_reply_to' => $email['reply_to'],
                        'draft' => 0,
                        'reply_to_name' => $email['reply_to_name'],
                        'reply_to_email' => $email['reply_to'],
                    ];

                    $hd_message = HDMessage::updateOrCreate([
                        'ticket_id' => $hd_ticket->id,
                        'message_id' => trim($email['message_id']),
                    ], $hdMessageData);

                    if (isset($hd_message->id) && !empty($email['parts'])) {
                        $email['parts']['message_id'] = $hd_message->id;
                        HdMessagePart::updateOrCreate([
                            'message_id' => $hd_message->id,
                        ], $email['parts']);
                    }

                    if (isset($hd_message->id) && !empty($email['attachments'])) {

                        if(!empty($email['attachments'])){
                            foreach($email['attachments'] as $attachment){
                                $fileName = $attachment->getName();
                                $fileMimeType = $attachment->getMimeType();
                                $fileSize = $attachment->getSize();
                                $attachmentID = $attachment->getId();

                                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                $savePath = storage_path('app/public/email_attachment/');
                                if (!file_exists($savePath)) {
                                    mkdir($savePath, 0755, true);
                                }

                                //$uniqueFileName = uniqid('email_', true) . '.' . $extension;

                                try {
                                    $attachment->save($savePath, $fileName);

                                    $filedata = [
                                        'name' => $fileName,
                                        'ext' => $extension ?: 'jpg',
                                        'file_size' => $fileSize ?: 'Unknown',
                                        'location' => 'email_attachment/',
                                        'created_by' => 'admin',
                                    ];

                                    $fileDetail = SysFile::create($filedata);
                                    $file_id = $fileDetail->id;

                                    if($hd_ticket->id && $file_id){
                                        $attachementData = [
                                            'client_id' => NULL,
                                            'message_id' => $hd_message->id,
                                            'attachment_id' => $attachmentID,
                                            'file_id' => $file_id,
                                            'created_by' => 'admin',
                                        ];

                                        HdMessageAttachment::create($attachementData);

                                    }

                                } catch (\Exception $e) {
                                    echo "Error saving attachment: " . $e->getMessage();
                                    Log::error('Error saving attachment: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
                                }
                            }
                        }
                    }
                }
            }

            /*if ($last_filter_date != '') {
                $account->update(['filter_from_date' => $last_filter_date]);
            }*/
        }
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



    }

    function getNextTicketNumberForStore($storeId) {
        $key = 'ticket_' . str_replace('-', '', $storeId);

        return DB::transaction(function () use ($key) {
            $sequence = Sequence::lockForUpdate()->find($key);

            if (!$sequence) {
                $sequence = Sequence::create([
                    'key' => $key,
                    'number' => 1
                ]);
            } else {
                $sequence->increment('number');
            }

            return $sequence->number;
        });
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

        //Log::info('Files: ' . json_encode($files));
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

            //Log::info('Files: ' . json_encode($files));
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
