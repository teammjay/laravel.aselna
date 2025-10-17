<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\UserAccount;
use App\Services\Marketplace\Service\EbayService;
use App\Services\Marketplace\Service\ShopifyService;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Jobs\Concerns\TrackableJob;

class SendNotificationOrderSyncFailJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id)
    {
        $this->userId = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->updateTrackingRecord(['tags' => 'Send Notification Order Sync Fail']);

        $stores = Store::with('storeType')->where('store_status','>',0)->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {

                Log::info("Store: : {$store->name} - {$store->storeType->name}");
                Log::info("Store ID: ".$store->id);

                $latest_system_order = Order::where('store_id',$store->id)->orderBy('order_date','desc')->first();
                $latest_order_id = '';
                $latest_order_date = '';
                switch (strtolower($store->storeType->name)) {
                    case 'shopify':

                        //$filterStartDateFormatted = date('Y-m-d\TH:i:s\Z', strtotime('-2 days'));

                        Log::info('System Shopify Latest Order: '.json_encode($latest_system_order));

                        $shopifyService = new ShopifyService();
                        $orders = $shopifyService->getLatestOrders($store);

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

                        $this->sendMail($store, $content);

                    }
                }
            }
        }
    }

    private function sendMail($store, $content){
        $userId = $this->userId;
        Log::info('User ID : '.$userId);
        $userDetail = User::findOrFail($userId);
        $sendTo = $userDetail->send_to_email;
        $cc_emails = '';
        if($userDetail->cc_email != ''){
            $cc_emails = array_filter(array_map('trim', explode(PHP_EOL, $userDetail->cc_email)));
            /*if(is_array($ccEmails)){
                $cc_emails = implode(',', $ccEmails);
            }else{
                $cc_emails = $ccEmails;
            }*/
        }
        $userMessageAccount = UserAccount::with('outgoingAccount')->where('user_id', $userId)->where('active', 1)->first();

        if (isset($userMessageAccount->outgoingAccount)) {
            if ($userMessageAccount->outgoingAccount->requires_ssl) {
                $encryption = 'ssl';
            }
            if ($userMessageAccount->outgoingAccount->requires_tls) {
                $encryption = 'tls';
            }

            Config::set('mail.mailers.custom_smtp', [
                'transport' => 'smtp',
                'host' => $userMessageAccount->outgoingAccount->server,
                'port' => $userMessageAccount->outgoingAccount->port,
                'encryption' => $encryption,
                'username' => $userMessageAccount->outgoingAccount->username,
                'password' => $userMessageAccount->outgoingAccount->password,
                'timeout' => null,
                'auth_mode' => null,
            ]);

            Config::set('mail.from.address', $userMessageAccount->outgoingAccount->email);
            Config::set('mail.from.name', $userMessageAccount->name);

            Log::info('Sending Store Order Sync Issue Mail to: '.$sendTo.' CC: '. json_encode($cc_emails));

            Mail::mailer('custom_smtp')->raw($content,
                function ($message) use ($store, $sendTo, $cc_emails) {
                    $message->to($sendTo)
                        ->cc($cc_emails)
                        ->subject("Store Order Sync Issue Alert : {$store->name} - {$store->storeType->name}");
                }
            );

            Log::info('Store Order Sync Issue Mail sent successfully', [
                'recipient' => $sendTo,
                'timestamp' => now()
            ]);
        }
    }

}
