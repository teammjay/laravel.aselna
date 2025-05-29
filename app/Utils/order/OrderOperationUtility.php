<?php

namespace App\Utils\order;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\ShippingLabelCreationType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Services\Auth\EbayAuthService;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Auth\WalmartAuthService;
use App\Services\Marketplace\Service\ShopifyService;
use App\Utils\EndicialabelService;
use Illuminate\Support\Facades\Log;

class OrderOperationUtility
{
    public static function processShipment($orderOperation, $request)
    {
        if (isset($orderOperation->order)) {
            if (isset($orderOperation->order->contingent) && $orderOperation->order->contingent) {
                $orderOperation->status = OrderOperationStatus::Error;
                $orderOperation->save();
                return false;
            }
        }

        $carrier_id = $request->input('carrier_name');
        $carrier_package = $request->input('carrier_package');
        $carrier_service_id = $request->input('shipping_carrier_service');
        $tracking_number = $request->input('tracking_number');
        $shipment_from = $request->input('shipment_from');
        $weight = $request->input('weight');
        $weight_type = $request->input('weight_type');
        $order_id = $request->input('order_id');

        $updateRecord = array(
            'tracking_number' => $tracking_number,
            'shipping_carrier_id' => $carrier_id,
            'shipping_carrier_service_id' => $carrier_service_id,
            'shipping_package_id' => $carrier_package,
            'ship_from_id' => $shipment_from,
            'weight_value' => $weight,
            'weight_weight_unit' => $weight_type,
        );
        $orderOperation->update($updateRecord);


        Log::info('Updated record :: ' . json_encode($updateRecord));

        $label_generation = $request->input('label_generation');
        if ($label_generation) {
            $orderOperation->label_creation_type = ShippingLabelCreationType::getShippingLabelCreationTypeId($label_generation);
        } else {
            $orderOperation->label_creation_type = ShippingLabelCreationType::NoLabel;
        }

        $orderOperation->save();

        return self::instantSynchronization($orderOperation);
    }

    public static function instantSynchronization($orderOperation)
    {
        $operationType = $orderOperation->operation_type;
        Log::info('Instant synchronization for operation type: ' . $operationType);

        if ($operationType === OrderOperationType::OrderCancellation) {
            self::cancelOrder($orderOperation);
            return true;
        }

        if (OrderOperationType::isReturn($operationType)) {
            self::refundOrder($orderOperation);
            return true;
        }

        if (OrderOperationType::isShipment($operationType)) {
            return self::createShipment($orderOperation);
        }
        return false;
    }

    public static function createLabel($orderOperation)
    {
        $shipmentDetails = [];
        $errorMessages = ShippingUtility::assignSolutionService($orderOperation);

        $operation_type = $orderOperation->operation_type;
        $shipFrom = $orderOperation->shipFrom;

        if (OrderOperationType::isReturn($operation_type)) {
            $shipmentDetails['fromAddress'] = self::getAddress($shipFrom->returnAddress);
            $shipmentDetails['toAddress'] = self::mapCustomerAddress($orderOperation);
        } else {
            $shipmentDetails['fromAddress'] = self::getAddress($shipFrom->fromAddress);
            $shipmentDetails['toAddress'] = self::mapCustomerAddress($orderOperation);
        }

        $shippingPackage = $orderOperation->shippingPackage;
        if (isset($shippingPackage)) {
            if ($shippingPackage->id == '718d3e95-a328-e6a8-c96b-3638fec677f7') {
                $shipmentDetails['dimensions'] = [
                    'length' => $orderOperation->own_packaging_dimensions_length,
                    'width' => $orderOperation->own_packaging_dimensions_width,
                    'height' => $orderOperation->own_packaging_dimensions_height,
                    'unit' => $orderOperation->own_packaging_dimensions_length_unit,
                ];
            } else {
                $shipmentDetails['dimensions'] = [
                    'length' => $shippingPackage->dimensions_length,
                    'width' => $shippingPackage->dimensions_width,
                    'height' => $shippingPackage->dimensions_height,
                    'unit' => $shippingPackage->dimensions_length_unit,
                ];
            }
        }

//        if (isset($orderOperation->carre))

        Log::info('Shipment details for label creation: ' . json_encode($shipmentDetails));

        $endiciaClient = new EndicialabelService();
        $response = $endiciaClient->createLabel($shipmentDetails);

        Log::info($response);
    }

    public static function cancelOrder($orderOperation)
    {
        $store = $orderOperation->store;
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':

                $orderId = $orderOperation->order_id;
                $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('order_id', $orderId)->first();

                $shopifyServie = new ShopifyAuthService($store->secret_key);
                $response = $shopifyServie->cancelOrder($orderOperation->order->purchase_order_id, $shipmentDetail->order->order_id);

                if (!empty($response['data']['orderCancel']['orderCancelUserErrors'])) {
                    foreach ($response['data']['orderCancel']['orderCancelUserErrors'] as $error) {
                        //echo "Error: {$error['message']}\n";
                        Log::info('Shopify cancer order error:' . json_encode($error));
                        return false;
                    }
                } else {
                    //echo "Order canceled successfully!";
                    Log::info('Shopify cancer order successful:');
                    return true;
                }
                break;
            case 'walmart':
                $finalData = [];
                $count = 1;
                foreach ($orderOperation->operationItem as $item) {
                    $orderLine = [
                        'lineNumber' => $count,
                        'orderLineStatuses' => [
                            'orderLineStatus' => [
                                [
                                    'status' => 'Cancelled',
                                    'cancellationReason' => 'CUSTOMER_REQUESTED_SELLER_TO_CANCEL',
                                    'statusQuantity' => [
                                        'unitOfMeasure' => 'EACH',
                                        'amount' => $item->quantity
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $finalData['orderCancellation']['orderLines']['orderLine'][] = $orderLine;
                    $count++;
                }
                $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
                $authService->cancelOrder($orderOperation->order->purchase_order_id, $finalData);
                break;
            case 'ebay':

                $clientId = env('EBAY_CLIENT_ID');
                $clientSecret = env('EBAY_CLIENT_SECRET');
                $redirectUri = env('EBAY_REDIRECT_URI');

                $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                $payload = [
                    'cancelReason' => 'BUYER_CANCEL_OR_ADDRESS_ISSUE',
                    'legacyOrderId' => $orderOperation->order->order_id,
                ];
                $response = $authService->cancelOrder($payload);

                if (!isset($response['cancelId'])) {

                    Log::info('Shopify cancer order error:' . json_encode($response['warnings']));
                    return false;
                } else {
                    //echo "Order canceled successfully!";
                    Log::info('Shopify cancer order successful:');
                    return true;
                }

                break;
        }
    }

    private static function refundOrder($orderOperation)
    {

    }

    private static function createShipment($orderOperation)
    {
        Log::info('Creating shipment for order operation: ' . $orderOperation->label_creation_type);
        if ($orderOperation->label_creation_type == ShippingLabelCreationType::CreateLabel) {
            self::createLabel($orderOperation);
            // TODO Create a label
        }
        $store = $orderOperation->store;
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':
                $shopifyServie = new ShopifyService();
                $response = $shopifyServie->updateFullfillment($orderOperation->order_id);

                if (!isset($response['errors'])) {
                    $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped]);
                    if (isset($orderOperation->order_id)) {
                        $order = Order::findOrFail($orderOperation->order_id);
                        $order->update(['order_status' => OrderStatus::Shipped]);
                    }
                }


                break;
            case 'walmart':
                $finalData = [];
                $count = 1;
                foreach ($orderOperation->operationItem as $item) {
                    $orderLine = [
                        'lineNumber' => $count,
                        'orderLineStatuses' => [
                            'orderLineStatus' => [
                                [
                                    'status' => 'Shipped',
                                    'statusQuantity' => [
                                        'unitOfMeasurement' => 'EACH',
                                        'amount' => $item->quantity
                                    ],
                                    'trackingInfo' => [
                                        "shipDateTime" => date('Y-m-d\TH:i:sP'),
                                        "carrierName" => [
                                            "carrier" => $orderOperation->carrier->name,
                                        ],
                                        "methodCode" => 'Standard',
                                        "trackingNumber" => $orderOperation->tracking_number,
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $finalData['orderShipment']['orderLines']['orderLine'][] = $orderLine;
                    $count++;
                }

                $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
                $response = $authService->shipOrder($orderOperation->order->purchase_order_id, $finalData);

                Log::info('Walmart ship order response: ' . json_encode($response));
                foreach ($response['order'] as $order) {
                    if (isset($order['orderId']) && $order['orderId'] == $orderOperation->order->purchase_order_id) {
                        Log::info('Walmart ship order successful: ' . json_encode($order));
                        break;
                    }
                }

                if (isset($response['order'][0])) {
                    $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped]);
                    if (isset($orderOperation->order_id)) {
                        $order = Order::findOrFail($orderOperation->order_id);
                        $order->update(['order_status' => OrderStatus::Shipped]);
                    }
                    return true;
                }
                return false;
            case 'ebay':

                $payload = [
                    "shippedDate" => now()->toIso8601ZuluString(),
                    "shippingCarrierCode" => $orderOperation->carrier->name,
                    "trackingNumber" => $orderOperation->tracking_number
                ];

                foreach ($orderOperation->operationItem as $item) {

                    $payload["lineItems"][] = [
                        "lineItemId" => $item->orderItem->store_sku,
                        "quantity" => $item->quantity,
                    ];

                }

                /*echo '<pre>';
                print_r($payload);
                echo '</pre>';die;*/

                $clientId = env('EBAY_CLIENT_ID');
                $clientSecret = env('EBAY_CLIENT_SECRET');
                $redirectUri = env('EBAY_REDIRECT_URI');

                $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                $result = $authService->createShippingFulfillment($orderOperation->order->order_id, $payload);

                if (!isset($result['error'])) {
                    $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped]);
                    if (isset($orderOperation->order_id)) {
                        $order = Order::findOrFail($orderOperation->order_id);
                        $order->update(['order_status' => OrderStatus::Shipped]);
                    }
                }

                break;
        }
        return true;
    }

    /**
     * @param mixed $shipFrom
     * @return array
     */
    public static function getAddress(mixed $shipAddress): array
    {
        if (is_null($shipAddress)) {
            return [];
        }
        return [
            'name' => $shipAddress->name,
            'address1' => $shipAddress->address1,
            'address2' => $shipAddress->address2 ?? '',
            'city' => $shipAddress->city,
            'state' => $shipAddress->state,
            'postalCode' => $shipAddress->postal_code,
            'country' => $shipAddress->country,
            'phone' => $shipAddress->phone,
        ];
    }

    /**
     * @param $orderOperation
     * @return array
     */
    public static function mapCustomerAddress($orderOperation): array
    {
        if (!isset($orderOperation->order->customerAddress)) {
            return [];
        }
        return [
            'name' => $orderOperation->order->customerAddress->name,
            'address1' => $orderOperation->order->customerAddress->address1,
            'address2' => $orderOperation->order->customerAddress->address2 ?? '',
            'city' => $orderOperation->order->customerAddress->city,
            'state' => $orderOperation->order->customerAddress->state,
            'postalCode' => $orderOperation->order->customerAddress->postal_code,
            'country' => $orderOperation->order->customerAddress->country,
            'phone' => $orderOperation->order->customerAddress->phone,
        ];
    }
}
