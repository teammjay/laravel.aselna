<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Services\Marketplace\Service\GoogleShoppingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class FetchGoogleShoppingOrdersjob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Google Shopping Orders']);

            // get google shopping order
            $googleService = new GoogleShoppingService();
            $orders = $googleService->getOrders();
//        Log::info('google shopping Orders: ' . json_encode($orders));

            if (!isset($orders['error'])) {
                foreach($orders['resources'] as $order){
//                Log::info('google shopping Order: ' . json_encode($order));
                    $firstname = $lastname = '';
                    if(isset($order['customer']['fullName'])){
                        $nameArr = explode(' ',$order['customer']['fullName']);
                        $firstname = $nameArr[0];
                        $lastname = $nameArr[1];
                    }

                    $orderData = [
                        'order_id' => $order['id'],
                        'order_date' => date('Y-m-d H:i:s',strtotime($order['placedDate'])),
                        'order_status' => OrderStatus::getOrderStatusId($order['status']),
                        'order_total' => $order['totalPrice']['value'],
                        'buyer_first_name' => $firstname,
                        'buyer_last_name' => $lastname,
                        'buyer_email' => $order['customer']['email'],
                        'purchase_order_id' => $order['orderNumber'],
                        'store_id' => 'c476797e-9682-cc87-c841-5ecc63b9b002',
                    ];
                    Log::info('Order Data: ' . json_encode($orderData));

                    $order_id = Order::updateOrCreate([
                        'order_id' => $order['id'],
                    ],$orderData);

                    /* get order items*/
                    if (isset($order['lineItems'])) {
                        foreach($order['lineItems'] as $item){
                            Log::info('Google Order Item: ' . json_encode($item));
//                        $itemDetail = ListingItem::where('sku',$item['SellerSKU']);
                            $orderItemData = [
                                'title' => $item['productName'],
                                'order_id' => $order_id->id,
//                            'product_id' => $itemDetail->storeProduct->product->product_id,
//                            'client_id' => $itemDetail->client,
//                            'listing_item_id' => $itemDetail->id,
                                'sku' => '',
                                'store_sku' => '',
                                'quantity' => $item['quantityOrdered'],
                                'id1' => $item['productId'],
                                'item_price' => $item['price']['value'],
                                'shipping_price' => '0',
                                'item_tax' => $item['tax']['value'],
                                'shipping_tax' => '0',
                                'shipping_quantity' => $item['quantityShipped'],
                            ];

                            $order_item_id = OrderItem::updateOrCreate([
                                'order_id' => $order_id->id
                            ],$orderItemData);
                        }
                    }
                    return;
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
        }
    }
}
