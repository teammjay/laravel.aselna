<?php

namespace App\Http\Controllers;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Constants\ListingSyncStatus;
use App\Constants\OrderOperationStatus;
use App\Helpers\QueryFilters;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchMagentoOrderJob;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SendNotificationForPendingTransactionsJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\synccategorytospecification;
use App\Jobs\TransactionStatusCheckJob;
use App\Models\AccountTransaction;
use App\Models\Catalog;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\HDMessage;
use App\Models\HdMessageAttachment;
use App\Models\HdMessagePart;
use App\Models\HdMessageReference;
use App\Models\HdMessageTemplate;
use App\Models\HdTicketType;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Message;
use App\Models\ListingItemDetail;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\ProductType;
use App\Models\Sequence;
use App\Models\ShippingAccount;
use App\Models\AccountTransactionSynchronization;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Models\SysFile;
use App\Services\Auth\GoogleShoppingAuthService;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Auth\WalmartAuthService;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\FeedStatus;
use Tests\Utils\order\OrderOperationUtilityTest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

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


        $shopifyService = new ShopifyAuthService('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', 'https://accessorychoice.myshopify.com');
        $orderDetail = $shopifyService->getOrderDetail('6108743270539');

        echo '<pre>';
        print_r($orderDetail);
        echo '</pre>';die;

        /*$productCodes = ['J50','J52'];

        $skus = ListingItem::where(function ($q) use ($productCodes) {
            foreach ($productCodes as $code) {
                $q->orWhere('sku', 'like', "{$code}-%");
            }
        })->pluck('sku')->toArray();

        $detailSkus = ListingItemDetail::where(function ($q) use ($productCodes) {
            foreach ($productCodes as $code) {
                $q->orWhere('sku', 'like', "{$code}-%");
            }
        })->pluck('sku')->toArray();

        $skuCache = array_flip(array_merge($skus, $detailSkus));
        echo '<pre>';
        print_r($skuCache);
        echo '</pre>';die;*/


        /*$product_codes = 'J50';
        $existingSkus = ListingItem::whereLike('sku',$product_codes.'-%')->pluck('sku')->toArray();
        $detailSkus = ListingItemDetail::whereLike('sku',$product_codes.'-%')->pluck('sku')->toArray();
        $skuCache = array_flip(array_merge($existingSkus, $detailSkus));
        echo '<pre>';
        print_r($skuCache);
        echo '</pre>';die;*/

        /*$stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','shopify')->get();

        if(count($stores ?? []) > 0) {
           foreach ($stores as $store) {
               if ($store->name != 'AccessoryChoice') {
                   continue;
               }
               $d = now()->subDays(3)->toDateTimeString();
               $filterStartDateFormatted = date('Y-m-d\TH:i:s\Z', strtotime($d));
               $shopifyService = new ShopifyService();
               $orders = $shopifyService->getOrders($store,$filterStartDateFormatted);
               echo '<pre>';
               print_r($orders);
               echo '</pre>';
           }
        }die;*/

        /*$stores = Store::with('fileSystem','storeType')->where('name','AccessoryWiz')->get();
        //dd($stores);
        echo '<pre>';
        print_r($stores);
        echo '</pre>';die;

        /*$walmart_store_ids = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'walmart');
        })->pluck('id');
        Log::info('Walmart store ids: ' . json_encode($walmart_store_ids));

        $records = ListingItemLink::query()
            ->with(['listing','listingItem'])
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $walmart_store_ids)
            ->whereRaw("COALESCE(listing_item.store_sku, '') = ''")
            ->whereIn('listing_item.status', [1,2])
            ->whereRaw('(listing_item.creation_retry_count < 5 OR listing_item.creation_retry_count IS NULL)')
            ->whereIn('listing_item.sync_status', [
                ListingSyncStatus::CreationError,
                ListingSyncStatus::Synchronizing,
            ])
            ->whereNotExists(function ($subquery) use ($walmart_store_ids) {
                $subquery->selectRaw(1)
                    ->from('listing_feeds')
                    ->whereColumn('listing_feeds.listing_id', 'listing.id')
                    ->whereIn('store_id', $walmart_store_ids)
                    ->where('status', FeedStatus::PROCESSING);
            })
            ->orderBy('listing_item.created_at','asc')
            //->limit(5)
            ->chunk(100, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    Log::info('Syncing Walmart listing item: ' . $listingItemLink->item_id);
                    //$this->syncListingItem($listingItemLink);
                }
            });


        //dd(DB::getQueryLog());
        //var_dump($query->toSql());die;
        //echo $query->toSql();die;
        echo '<pre>';
        print_r($records);
        echo '</pre>';die;*/

        /*$stores = Store::with('storeType')->where('store_status','>',0)->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                $latest_system_order = Order::where('store_id',$store->id)->orderBy('order_date','desc')->first();
                $latest_order_id = '';
                $latest_order_date = '';
                switch (strtolower($store->storeType->name)) {
                    case 'shopify':

                        //$filterStartDateFormatted = date('Y-m-d\TH:i:s\Z', strtotime('-2 days'));

                        Log::info('System Shopify Latest Order: '.json_encode($latest_system_order));

                        $shopifyService = new ShopifyService();
                        $orders = $shopifyService->getLatestOrders($store);

                        Log::info('Shopify Latest Order: '.json_encode($orders));

                        if (isset($orders) && !empty($orders)) {
                            foreach ($orders as $key=>$value) {
                                $order = $value['node'];
                                $gid = $order['id'];
                                $parts = explode('/', $gid);
                                $latest_order_id = end($parts);
                                $latest_order_date = date('Y-m-d H:i:s', strtotime($order['createdAt']));
                                if($key > 0){break;}
                            }
                        }

                        break;
                    case 'walmart':

                        Log::info('System Walmart Latest Order: '.json_encode($latest_system_order));

                        $walmartService = new WalmartService();
                        $latest_order_detail = $walmartService->getLatestOrder($store);

                        Log::info('Walmart Latest Order: '.json_encode($latest_order_detail));
                        if($latest_order_detail){
                            $latest_order_id = $latest_order_detail['order_id'];
                            $latest_order_date = $latest_order_detail['order_date'];
                        }
                        break;
                    case 'ebay':

                        Log::info('System eBay Latest Order: '.json_encode($latest_system_order));
                        $ebayService = new EbayService();
                        $orders = $ebayService->getLatestOrders($store);
                        if (isset($orders['total']) && $orders['total'] > 0) {
                            foreach ($orders['orders'] as $key=>$order) {
                                $latest_order_id = $order['orderId'];
                                $latest_order_date = date('Y-m-d H:i:s', strtotime($order['creationDate']));
                                if($key > 0){break;}
                            }
                        }
                        break;
                }

                if($latest_system_order && $latest_order_id){
                    if($latest_system_order->order_id != $latest_order_id){
                        $content = "Order Sync issue detected for store: {$store->name} - {$store->storeType->name}. \n Last sync at {$latest_system_order->order_date}\n";
                        $content .= "System order id: {$latest_system_order->order_id} , Order date : {$latest_system_order->order_date} \n";
                        $content .= "Store order id: {$latest_order_id} , Order date : {$latest_order_date} \n";

                        Log::info('Message content send: '.$content);

                        //$this->sendMail($store, $content);

                    }
                }
            }
        }
        die;*/
        /*$user_id = Auth::id();
        Bus::chain([
            //new TransactionStatusCheckJob(),
            new SendNotificationForPendingTransactionsJob($user_id)
            //new SubmitShopifyProductsJob(),
            //new synccategorytospecification(),
            //new FetchShopifyOrders(),
            //new EbayListingItemSyncJob()
            //new SyncAllOrderStatusJob()
            //new FetchWalmartOrdersJob()
            //new FetchEmailMessagesJob()
        ])->dispatch(); die;*/

        $shipping_account = ShippingAccount::whereLike('name','%Endicia Label Server%')->first();
        /*$sync_detail = AccountTransactionSynchronization::orderBy('created_at','desc')->first();

        if($sync_detail){
            $startTime = date('Y-m-d H:i:s',strtotime($sync_detail->filter_end_date));
            if($startTime > date('Y-m-d H:i:s')){
                $startTime = date('Y-m-d H:i:s');
            }
            $endTime = date('Y-m-d H:i:s',strtotime('+25 day', strtotime($startTime)));
            if($endTime > date('Y-m-d H:i:s')){
                $endTime = date('Y-m-d H:i:s');
            }
        }else {
            $endTime   = date('Y-m-d H:i:s', strtotime('-3 months'));
            $startTime = date('Y-m-d H:i:s', strtotime('-25 days', strtotime($endTime)));
        }

        if(strtotime($startTime) == strtotime($endTime)){
            return;
        }

        //echo $startTime;
        //echo $endTime;

        $data = [
            'shipping_account_id' => $shipping_account->id,
            'filter_start_date' => date('Y-m-d H:i:s',strtotime($startTime)),
            'filter_end_date' => date('Y-m-d H:i:s',strtotime($endTime)),
        ];

        //echo '<pre>';
        //print_r($data);
        //echo '</pre>';die;

        $synchronize = AccountTransactionSynchronization::create($data);*/

        /*$transactions = AccountTransaction::with('shippingAccount')
        ->where('canceled', 0)
            ->orWhereNull('canceled')
            ->where('status','pending')
            ->orderBy('transaction_date','desc')
            ->limit(10)->get();

//        echo '<pre>';
//        print_r($transactions);
//        echo '<pre>';die;


        $trans = [
            'accountId' => $shipping_account->account_id,
            'passPhrase' => $shipping_account->pass_phrase,
            'requesterId' => $shipping_account->requester_id,
            'requestId' => (string)Str::uuid(),
            'transactions' => $transactions,
        ];



        $endiciaClient = new EndicialabelService();
        $response = $endiciaClient->getTrackingStatuses($trans);

        echo '<pre>';
        print_r($response);
        echo '</pre>';die;

        //Log::info('Transaction Status Response:'.json_encode($response));

        if(isset($response['status']) && $response['status'] == 0){
            Log::info('Status:'.$response['status']);
            $transactions = $response['transactionResult'][0]->Transaction;
            if(isset($transactions) && !empty($transactions)){
                $key = 0;
                foreach($transactions as $tres){

//                    echo '<pre>';
//                    print_r($tres);
//                    echo '</pre>';die;

                    $transactionType = (string) $tres['TransactionType'];
                    $transactionDateTime = (string) $tres['TransactionDateTime'];
                    $postmarkDate = (string) $tres['PostmarkDate'];
                    $refundStatus = (string) $tres['RefundStatus'];

                    $trackingNumber = (string) $tres->PIC;
                    $status_event = isset($tres->Status->StatusEvent[0]) ? (string) trim($tres->Status->StatusEvent[0]) : '';
                    $status_desription = isset($tres->Status->StatusDescription[0]) ? (string) trim($tres->Status->StatusDescription[0]) : '';
                    $status = 'Pending';
                    if($status_event != ''){
                        Log::info('Status Event:'.$status_event);
                        switch(trim($status_event)){
//                            case 'N':
//                                $status = 'New Item';
//                                break;
//                            case 'D':
//                                $status = 'Delivered';
//                                break;
//                            case 'O':
//                                $status = 'Out for Delivery';
//                                break;
//                            case 'P':
//                                $status = 'Available for Pickup';
//                                break;
//                            case 'R':
//                                $status = 'Returned to Sender';
//                                break;
//                            case 'U':
//                                $status = 'Undeliverable';
//                                break;
//                            case 'V':
//                                $status = 'Delivery Status Not Updated';
//                                break;
//                            case 'L':
//                                $status = 'Notice Left for Recipient';
//                                break;
//                            case 'F':
//                                $status = 'Forwarded to New Address';
//                                break;
//                            case 'E':
//                                $status = 'Dead Letter';
//                                break;
//                            case 'C':
//                                $status = 'Customer Recall';
//                                break;
//                            case 'I':
//                                $status = 'Scanned in Route';
//                                break;

                            case 'D':
                                $status = 'completed';
                                break;
                            default:
                                $status = 'pending';
                                break;
                        }
                    }

                    $transactionObj = AccountTransaction::where('tracking_number',$trackingNumber)->first();

                    if($transactionObj){
                        $canceled = 0;
                        if(strtolower($refundStatus) == 'refunded'){
                            $canceled = 1;
                        }
                        $updateRecords = [
                            'status' => $status,
                            'status_event' => $status_event,
                            'status_description' => $status_desription
                        ];

//                        echo '<pre>';
//                        print_r($updateRecords);
//                        echo '</pre>';die;

                        Log::info('Update Tracking status:'.json_encode($updateRecords));
                        $transactionObj->update($updateRecords);
                        Log::info('Update Tracking status:'.$trackingNumber.'- status : '.$status);
                    }

                    if($key == 0) {
                        $synchronize->update(['filter_end_date' => date('Y-m-d H:i:s', strtotime($transactionDateTime))]);
                        Log::info('Tracking:' . $trackingNumber);
                    }

                    $key++;
                }

            }
        }die;*/

        //die;
        /*$transactions = AccountTransaction::with('shippingAccount')->where('canceled', 0)->orWhereNull('canceled')
            ->where('transaction_date','<=',date('Y-m-d'))
            ->where('transaction_date','>=',date('Y-m-d',strtotime('-3 month')))
            ->orderBy('transaction_date','asc')
            ->limit(10)->get();
        //echo $transactions->toSql();die;
        $trans = [];
        if($transactions){
            foreach($transactions as $key=>$transaction){
                if($key == 0) {
                    $trans = [
                        'accountId' => $transaction->shippingAccount->account_id,
                        'passPhrase' => $transaction->shippingAccount->pass_phrase,
                        'requesterId' => $transaction->shippingAccount->requester_id,
                        'requestId' => (string)Str::uuid()
                    ];
                }

                $trans['transactions'][] = $transaction->tracking_number;
            }

            echo '<pre>';
            print_r($trans);
            echo '</pre>';

            $endiciaClient = new EndicialabelService();
            $response = $endiciaClient->getTransactionLists($trans);
            echo '<pre>';
            print_r($response);
            echo '</pre>';die;

            Log::info('Transaction Status Response:'.json_enocode($response));

            if(isset($response['status']) && $response['status'] == 0){
                if(isset($response['transactionResult'][0]['Transaction']) && !empty($response['transactionResult'][0]['Transaction'])){
                    foreach($response['transactionResult'][0]['Transaction'] as $tres){

                    }
                }
            }
        }*/

        /*$templateDetail = HdMessageTemplate::getOrderOperationTemplate(2, 0, 0);
        echo '<pre>';
        print_r($templateDetail);
        echo '</pre>';die;*/

        /*$imapConfig = [];
        $smtpConfig = [
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'phonil.ebay@gmail.com',
            'password' => 'qynvjqjqqaqqramo',
            'from_address' => 'phonil.ebay@gmail.com',
            'from_name' => 'Phonil Ebay',
        ];

        $previous = 'Reply from email center

On Fri, 29 Aug 2025 at 23:36, phonil <phonil.ebay@gmail.com> wrote:

> Reply from message page
>';

        $connector = new EmailMessageUtility($smtpConfig, $imapConfig);
        $response = $connector->sendEmail('jayesh@mjailab.com','Test account to test','Test Message For Check',$previous,'CAB3Dxwe17ba=xxUSyLkmUMmGLkxSvwPKpHXt+H8oZ89xaWnfaA@mail.gmail.com');
        die;*/
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
        /*$imapConfig = [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            'username' => 'odemobile.wm@gmail.com',
            'password' => 'tcogxvjrbwxwudgh',
        ];*/

        $imapConfig = [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl',
            //'username' => 'odemobile.shopify@gmail.com',
            //'username' => 'cs@odemobile.com',
            'username' => 'phonil.ebay@gmail.com',
            //'username' => 'accessorychoice.wmt@gmail.com',
            //'username' => 'snhdigital.ebay@gmail.com',
            //'password' => 'deczsvfmubmpxtsf',
            //'password' => 'qaym xjqg icea syxw',
            'password' => 'qynvjqjqqaqqramo',
            //'password' => 'hbmevmsyjuwqcpsm',
            //'password' => 'vtldhcsfeciyzhwm',
        ];



        $connector = new EmailMessageUtility($smtpConfig, $imapConfig);
        $receiveEmails = $connector->fetchEmails('Ebay', '2025-09-14 08:25:28','INBOX');

        //$store = Store::with('storeType')->where('id','0dd4de79-bba1-c47a-a7aa-822e6f015b8e')->first();
        $store = Store::with('storeType')->where('id','1968772b-cc55-d4e9-a126-7e81019be976')->first();
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
                $username = NULL;
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

                    if (isset($email['parsedData']['username'])) {
                        $username = html_entity_decode($email['parsedData']['username']);
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
                    'username' => trim($username),
                    'status' => HdTicketStatus::NEEDS_ACTION,
                    'related_order_id' => $related_order_id,
                    'subject' => preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim(mb_convert_encoding(htmlspecialchars_decode($email['subject']), 'UTF-8', 'UTF-8'))),
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

                Log::info('Email From : ' . $email['from'] . ' Message ID: ' . $email['message_id']);
                Log::info(' Reference ID: ' . json_encode($email['references']));
                /*$ticketDetail = Message::where('ticket_email', trim($email['from']))->orWhere('ticket_email', trim($email['reply_to']))->first();*/
                $query = Message::where('message_id', trim($email['message_id']));
                if (isset($email['references']) && $email['references'] != '') {
                    if(is_array($email['references'])){
                        $query->orWhereIn('message_id', $email['references']);


                    }else {
                        $query->orWhere('message_id', trim($email['references']));
                    }
                }
                if (isset($email['in_reply_to']) && $email['in_reply_to'] != '') {
                    $query->orWhere('message_id', trim($email['in_reply_to']));
                }
                Log::info(' Message Query: ' . $query->toSql());
                $ticketDetail = $query->first();

                Log::info('TicketDetail : ' .json_encode($ticketDetail));
                if ($ticketDetail) {
                    Log::info('Ticket Exist');
                    if ($order_id) {
                        $ticketDetail->order_id = $order_id;
                        $ticketDetail->ticket_type_id = $ticketTypeDetail->id;
                        $ticketDetail->save();
                    }
                    $hd_ticket = $ticketDetail;
                } else {
                    Log::info('New Ticket');
                    $hd_ticket = Message::create($ticketData);
                }

                Log::info('Ticket ID : ' .$hd_ticket->id);

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

                    Log::info('Message ID : ' .$hd_message->id);

                    if(isset($email['references']) && !empty($email['references']) && is_array($email['references'])){
                        foreach($email['references'] as $ref){
                            $refData = [
                                'ticket_id' => $hd_ticket->id,
                                'message_id' => $hd_message->id,
                                'reference' => $ref,
                            ];
                            HdMessageReference::updateOrCreate([
                                'ticket_id' => $hd_ticket->id,
                                'message_id' => $hd_message->id,
                                'reference' => $ref,
                            ], $refData);
                        }
                    }

                    if (isset($hd_message->id) && !empty($email['attachments'])) {

                        if(!empty($email['attachments'])){
                            $uniqueAttachments = [];
                            foreach($email['attachments'] as $attachment){
                                $fileName = $attachment->getName();
                                $fileMimeType = $attachment->getMimeType();
                                $fileSize = $attachment->getSize();
                                $attachmentID = $attachment->getId();

                                $key = $fileName . '-' . $fileSize;

                                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                $savePath = storage_path('app/public/email_attachment/');
                                if (!file_exists($savePath)) {
                                    mkdir($savePath, 0755, true);
                                }

                                //$uniqueFileName = uniqid('email_', true) . '.' . $extension;

                                try {
                                    if(!in_array($key,$uniqueAttachments)) {
                                        $uniqueAttachments[] = $key;

                                        $attachment->save($savePath, $fileName);

                                        $filedata = [
                                            'name' => $fileName,
                                            'ext' => $extension ?: 'jpg',
                                            'file_size' => $fileSize ?: 'Unknown',
                                            'location' => 'email_attachment/',
                                            'created_by' => 'admin',
                                        ];

                                        $fileDetail = SysFile::updateOrCreate([
                                            'name' => $fileName,
                                        ], $filedata);

                                        /*$fileDetail = SysFile::create($filedata);*/
                                        $file_id = $fileDetail->id;

                                        if ($hd_ticket->id && $file_id) {
                                            $attachementData = [
                                                'client_id' => NULL,
                                                'message_id' => $hd_message->id,
                                                'attachment_id' => $attachmentID,
                                                'file_id' => $file_id,
                                                'created_by' => 'admin',
                                            ];

                                            /*HdMessageAttachment::create($attachementData);*/

                                            HdMessageAttachment::updateOrCreate([
                                                'message_id' => $hd_message->id,
                                                'attachment_id' => $attachmentID,
                                                'file_id' => $file_id,
                                            ], $attachementData);

                                        }
                                    }

                                } catch (\Exception $e) {
                                    echo "Error saving attachment: " . $e->getMessage();
                                    Log::error('Error saving attachment: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
                                }
                            }
                        }
                    }
                }
                sleep(5);
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
