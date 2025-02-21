<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchWalmartOrdersJob implements ShouldQueue
{
    use Queueable;

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
        // get amazon order
        $walmartService = new WalmartService();
        $orders = $walmartService->getOrders();

        if (!isset($orders['errors']) && isset($orders['elements']['order'])) {

            foreach($orders['elements']['order'] as $order){
                $firstname = $lastname = '';
                $orderTotal = 0;
                $taxTotal = 0;
                $discountTotal = 0;
                $orderTotals = 0;
                foreach ($order['orderLines']['orderLine'] as $line) {
                    $itemTotal = 0;
                    foreach ($line['charges']['charge'] as $charge) {
                        $amount = (float) $charge['chargeAmount']['amount'];

                        if ($charge['chargeType'] === 'PRODUCT') {
                            $itemTotal += $amount;
                        }

                        if ($charge['chargeType'] === 'PRODUCT_TAX' || $charge['chargeType'] === 'SHIPPING_TAX') {
                            $taxTotal += $amount;
                        }

                        if ($charge['chargeType'] === 'SHIPPING') {
                            $orderTotal += $amount;
                        }

                        if ($charge['chargeType'] === 'DISCOUNT') {
                            $discountTotal += $amount; // Discount is negative, so adding reduces total
                        }
                    }

                    $orderTotal += $itemTotal;
                }

                $orderTotals = $orderTotal + $taxTotal - $discountTotal;

                if(isset($order['postalAddress']['name'])){
                    $nameArr = explode(' ',$order['postalAddress']['name']);
                    $firstname = $nameArr[0];
                    $lastname = $nameArr[1];
                }
                $orderData = [
                    'order_id' => $order['purchaseOrderId'],
                    'order_date' => date('Y-m-d H:i:s',strtotime($order['orderDate'])),
                    'order_status' => $order['OrderStatus'],
                    'order_total' => $orderTotals,
                    'buyer_first_name' => $firstname,
                    'buyer_last_name' => $lastname,
                    'buyer_email' => '',
                    'purchase_order_id' => $order['purchaseOrderId'],
                ];

                $order_id = Order::updateOrCreate([
                    'order_id' => $order['purchaseOrderId'],
                ],$orderData);

                /* get order items*/
                $ordersItems = $order['orderLines']['orderLine'];
                if (!!empty($ordersItems)) {
                    foreach($ordersItems as $item){
                        $itemDetail = ListingItem::with('storeProduct','storeProduct.product')->where('sku',$item['product_info']['sku']);
                        $orderItemData = [
                            'title' => $item['item']['productName'],
                            'order_id' => $order_id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                            'client_id' => $itemDetail->client,
                            'listing_item_id' => $itemDetail->id,
                            'sku' => $item['item']['sku'],
                            'store_sku' => $item['item']['upc'],
                            'quantity' => $item['orderLineQuantity']['amount'],
                            'id1' => '',
                            'item_price' => ($item['charges']['charge']['chargeType'] == 'PRODUCT') ? $item['charges']['charge']['chargeAmount']['amount'] : 0,
                            'shipping_price' => ($item['charges']['charge']['chargeType'] == 'SHIPPING') ? $item['charges']['charge']['chargeAmount']['amount'] : 0,
                            'item_tax' => ($item['charges']['charge']['chargeType'] == 'PRODUCT_TAX') ? $item['charges']['charge']['tax']['taxAmount']['amount'] : 0,
                            'shipping_tax' => ($item['charges']['charge']['chargeType'] == 'SHIPPING_TAX') ? $item['charges']['charge']['tax']['taxAmount']['amount'] : 0,
                            'shipping_quantity' => $item['orderLineQuantity']['amount'],
                        ];

                        $order_item_id = OrderItem::updateOrCreate([
                            'order_id' => $order_id,
                            'product_id' => $itemDetail->storeProduct->product->product_id,
                        ],$orderItemData);
                    }

                }

            }
        }
    }
}
