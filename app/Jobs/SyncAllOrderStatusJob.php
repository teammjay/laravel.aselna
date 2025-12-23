<?php

namespace App\Jobs;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Services\Auth\AmazonAuthService;
use App\Services\Auth\EbayAuthService;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Auth\TemuAuthService;
use App\Services\Auth\WalmartAuthService;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class SyncAllOrderStatusJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $orderId;

    public $timeout = 30000;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId = null)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'All Orders Status']);

        try {
            $orderOperations = OrderOperation::with(['order', 'store'])
                ->where(function ($query) {
                    $query->whereNull('tracking_number')
                        ->orWhere('tracking_number', '')
                        ->orWhere('status', OrderOperationStatus::Created);
                })
                ->whereNotIn('status', [
                    OrderOperationStatus::MarkAsShipped,
                    OrderOperationStatus::Delivered,
                    OrderOperationStatus::Canceled
                ])
                ->when($this->orderId, function ($query) {
                    $query->where('order_id', $this->orderId);
                })
                ->orderByDesc('ship_date')
                ->get();
            //Log::info('Order Operations:'.json_encode($orderOperations));
            if (count($orderOperations ?? []) > 0) {
                foreach ($orderOperations as $operation) {
                    try {
                        $this->checkOrderStatus($operation);
                    } catch (Exception $exception) {
                        Log::error('Error syncing order status for OrderOperation ID ' . $operation->id . ': ' . $exception->getMessage());
                        continue;
                    }
                }
            }
        }catch (\Throwable $e) {
            Log::info('Error syncing order status for OrderOperation ID ' . $operation->id . ': ' . $e->getMessage());

            CriticalErrorService::report('All Orders Status Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    /**
     * @param mixed $operation
     * @return void
     */
    public function checkOrderStatus(mixed $operation): void
    {
        $order = $operation->order;
        Log::info('Order :' . json_encode($operation));
        $store = $operation->store;
        $storeType = $operation->store->storeType->name;
        if (!isset($order)) return;
        Log::info('Order ID:' . $order->order_id);
        Log::info('Store Type:' . $storeType);
        switch (strtolower($storeType)) {
            case 'shopify':

                $shopifyService = new ShopifyAuthService($store->secret_key, $store->service_url);
                $orderDetail = $shopifyService->getOrderDetail($order->order_id);

                if (isset($orderDetail) && !empty($orderDetail)) {
                    if (isset($orderDetail['fulfillments']) && count($orderDetail['fulfillments']) > 0) {

                        foreach ($orderDetail['fulfillments'] as $item) {
                            switch (strtolower($item['status'])) {
                                case 'fulfilled';
                                case 'success';
                                    $order->order_status = OrderStatus::Shipped;
                                    $operation->status = OrderOperationStatus::MarkAsShipped;
                                    break;
                                case 'cancelled';
                                    $order->order_status = OrderStatus::Canceled;
                                    $operation->status = OrderOperationStatus::Canceled;
                                    break;
                                case 'delivered';
                                    $order->order_status = OrderStatus::Shipped;
                                    $operation->status = OrderOperationStatus::Delivered;
                                    break;
                                case 'ready_for_pickup';
                                    $order->order_status = OrderStatus::Shipped;
                                    break;
                                case 'in_transit';
                                    $order->order_status = OrderStatus::Shipped;
                                    $operation->status = OrderOperationStatus::InTransit;
                                    break;
                            }

                            if (isset($item['trackingInfo']) && !empty($item['trackingInfo'][0])) {
                                $trackingNumber = $item['trackingInfo'][0]['number'] ?? '';
                                $operation->tracking_number = $trackingNumber;
                            }

                            $order->save();
                            $operation->save();
                        }

                    } else if ($orderDetail['displayFinancialStatus']) {
                        switch (strtolower($orderDetail['displayFinancialStatus'])) {
                            case 'refunded';
                                $order->order_status = OrderStatus::Refund;
                                $operation->status = OrderOperationStatus::REFUNDED;
                                break;
                        }

                        $order->save();
                        $operation->save();
                    } else if(isset($orderDetail['risk']['assessments'][0]['riskLevel']) && ($orderDetail['risk']['assessments'][0]['riskLevel'] == 'HIGH' || $orderDetail['risk']['assessments'][0]['riskLevel'] == 'MEDIUM')){
                        $sync_message = '';
                        if(isset($order['risk']['assessments'][0]['facts']) && is_array($order['risk']['assessments'][0]['facts'])){
                            foreach($order['risk']['assessments'][0]['facts'] as $facts){
                                $sync_message .= $facts['description'].PHP_EOL;
                            }

                            $operation->sync_message = $sync_message;
                            $operation->save();
                        }
                    } else {
                        Log::info('Shopify order detail error: ' . json_encode($orderDetail));
                    }
                }
                break;
            case 'walmart':

                $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
                $response = $authService->getOrderDetail($operation->order->purchase_order_id);

                Log::info('Walmart order response: ' . json_encode($response));

                if (isset($response)) {
                    $ordersItems = $response['order']['orderLines']['orderLine'];
                    if (!empty($ordersItems)) {
                        foreach ($ordersItems as $item) {
                            if (isset($response['order']) && isset($response['order']['purchaseOrderId']) && $response['order']['purchaseOrderId'] == $operation->order->purchase_order_id) {
                                if ($item['orderLineStatuses'] && $item['orderLineStatuses']['orderLineStatus']) {
                                    $lineStatus = $item['orderLineStatuses']['orderLineStatus'][0];
                                    if ($lineStatus['status'] === 'Shipped') {
                                        $order->order_status = OrderStatus::Shipped;
                                        $operation->status = OrderOperationStatus::MarkAsShipped;
                                    } else if ($lineStatus['status'] === 'Delivered') {
                                        $order->order_status = OrderStatus::Shipped;
                                        $operation->status = OrderOperationStatus::Delivered;
                                    } else if ($lineStatus['status'] === 'Cancelled') {
                                        $order->order_status = OrderStatus::Canceled;
                                        $operation->status = OrderOperationStatus::Canceled;
                                    } else if ($lineStatus['status'] === 'Refund') {
                                        $order->order_status = OrderStatus::Refund;
                                        $operation->status = OrderOperationStatus::REFUNDED;
                                    }

                                    if (isset($lineStatus['trackingInfo'])) {
                                        $trackingNumber = $lineStatus['trackingInfo']['trackingNumber'] ?? '';
                                        $operation->tracking_number = $trackingNumber;
                                    }

                                    if (isset($item['intentToCancel']) && $item['intentToCancel'] == "TRUE") {
                                        $order->order_status = OrderStatus::CanceledRequested;
                                        $operation->status = OrderOperationStatus::CanceledRequested;
                                    }

                                    $order->save();
                                    $operation->save();
                                }
                            } else {
                                Log::info('Walmart order detail error: ' . json_encode($response));
                            }
                        }
                    }

                } else {
                    Log::info('Walmart order detail error: No response received');
                }

                break;
            case 'ebay':

                $clientId = env('EBAY_CLIENT_ID');
                $clientSecret = env('EBAY_CLIENT_SECRET');
                $redirectUri = env('EBAY_REDIRECT_URI');

                $ebayAuthService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                $orderDetail = $ebayAuthService->getOrderDetail($order->order_id);

                if (!isset($orderDetail['error'])) {
                    if (isset($orderDetail['orderId']) && $orderDetail['orderId'] == $order->order_id) {
                        if (isset($orderDetail['lineItems'])) {
                            foreach ($orderDetail['lineItems'] as $item) {
                                switch (strtolower($item['lineItemFulfillmentStatus'])) {
                                    case 'fulfilled';
                                        $order->order_status = OrderStatus::Shipped;
                                        $operation->status = OrderOperationStatus::MarkAsShipped;
                                        break;
                                    case 'not_started';
                                        $order->order_status = OrderStatus::Pending;
                                        break;
                                }

                                if (isset($item['linkedOrderLineItems']['shipments']['shipmentTrackingNumber'])) {
                                    $trackingNumber = $item['linkedOrderLineItems']['shipments']['shipmentTrackingNumber'] ?? '';
                                    $operation->tracking_number = $trackingNumber;
                                }

                                $order->save();
                                $operation->save();
                            }
                        }

                        switch (strtolower($orderDetail['orderFulfillmentStatus'])) {
                            case 'fulfilled';
                                $order->order_status = OrderStatus::Shipped;
                                $operation->status = OrderOperationStatus::MarkAsShipped;
                                break;
                            case 'not_started';
                                $order->order_status = OrderStatus::Pending;
                                break;
                        }

                        if (isset($orderDetail['fulfillmentStartInstructions'])) {
                            $estimateShipmentDate = $orderDetail['fulfillmentStartInstructions'][0]['minEstimatedDeliveryDate'];
                            $estimateDeliveryDate = $orderDetail['fulfillmentStartInstructions'][0]['maxEstimatedDeliveryDate'];

                            $operation->expected_shipment_date = date('Y-m-d H:i:s', strtotime($estimateShipmentDate));
                            $operation->expected_delivery_date = date('Y-m-d H:i:s', strtotime($estimateDeliveryDate));
                            $operation->save();
                        }

                        if (isset($orderDetail['cancelStatus']) && $orderDetail['cancelStatus']['cancelState'] == 'CANCELED') {
                            $order->update(['order_status' => OrderStatus::Canceled]);
                            if (!empty($operation)) {
                                $operation->update(['status' => OrderOperationStatus::Canceled]);
                            }
                        } else if (isset($orderDetail['cancelStatus']) && $orderDetail['cancelStatus']['cancelState'] == 'IN_PROGRESS') {
                            $order->update(['order_status' => OrderStatus::CanceledRequested]);
                            if (!empty($operation)) {
                                $operation->update(['status' => OrderOperationStatus::CanceledRequested]);
                            }
                        }
                    }
                }
                break;
            case 'amazon':
                $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key, $store->api_key);
                $orderDetail = $amazonAuthService->getOrderInfo($order->order_id);
                Log::info('Amazon order detail: ' . json_encode($orderDetail));
                if (isset($orderDetail['payload'])) {
                    $amazonOrder = $orderDetail['payload'];
                    if (isset($amazonOrder['AmazonOrderId']) && $amazonOrder['AmazonOrderId'] == $order->order_id) {
                        if (isset($amazonOrder['FulfillmentChannel'])) {
                            if (isset($amazonOrder['OrderStatus'])) {
                                switch (strtolower($amazonOrder['OrderStatus'])) {
                                    case 'shipped';
                                        $order->order_status = OrderStatus::Shipped;
                                        $operation->status = OrderOperationStatus::MarkAsShipped;
                                        break;
                                    case 'canceled';
                                        $order->order_status = OrderStatus::Canceled;
                                        $operation->status = OrderOperationStatus::Canceled;
                                        break;
                                    case 'unshipped';
                                        $order->order_status = OrderStatus::Pending;
                                        break;
                                }
                            }
                        }
                    }
                    if (isset($amazonOrder['LatestShipDate'])) {
                        $operation->expected_shipment_date = date('Y-m-d H:i:s', strtotime($amazonOrder['LatestShipDate']));
                    }
                    if (isset($amazonOrder['EarliestShipDate'])) {
                        $operation->expected_delivery_date = date('Y-m-d H:i:s', strtotime($amazonOrder['EarliestShipDate']));
                    }
                    $order->save();
                    $operation->save();
                }
                break;
            case 'temu':
                $temuAuthService = new TemuAuthService();
                $orderDetail = $temuAuthService->getOrderInfo($order->order_id);
                Log::info('Temu order detail: ' . json_encode($orderDetail));

                if (isset($orderDetail) && isset($orderDetail['result'])){
                    $temuOrder = $orderDetail['result'];
                    if (isset($temuOrder['parentOrderMap']) && $temuOrder['parentOrderMap']['parentOrderSn'] == $order->order_id) {
                        if (isset($temuOrder['parentOrderMap']['parentOrderStatus'])) {
                            switch (strtolower($temuOrder['parentOrderMap']['parentOrderStatus'])) {
                                case 'shipped';
                                    $order->order_status = OrderStatus::Shipped;
                                    $operation->status = OrderOperationStatus::MarkAsShipped;
                                    break;
                                case 'canceled';
                                    $order->order_status = OrderStatus::Canceled;
                                    $operation->status = OrderOperationStatus::Canceled;
                                    break;
                                case 'pending';
                                case 'un_shipping';
                                    $order->order_status = OrderStatus::Pending;
                                    break;
                                case 'partially_shipped';
                                    $order->order_status = OrderStatus::PartiallyShipped;
                                    break;
                            }
                        }
                    }
                    $order->save();
                    $operation->save();
                }
                break;
        }
    }
}
