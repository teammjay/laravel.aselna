<?php

namespace App\Utils\order;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\ShippingLabelCreationType;
use App\Constants\WeightUnit;
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
        if (isset($errorMessages) && !empty($errorMessages)) {
            return $errorMessages;
        }

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

        $shipmentDetails['MailClass'] = $orderOperation->shipping_solution_carrier_service->solution_code;
        $shipmentDetails['MailpieceShape'] = $orderOperation->shipping_solution_carrier_package->solution_code;
        $shipmentDetails['transactionId'] = $orderOperation->order->order_id;

        $shipmentDetails['non_machinable'] = false;

        $shipmentDetails['weight'] = [
            'value' => $orderOperation->weight_value,
            'unit' => WeightUnit::getUnitName($orderOperation->weight_weight_unit),
        ];

        if ($orderOperation->shipping_solution_carrier_package) {
            $shipmentDetails['packageType'] = $orderOperation->shipping_solution_carrier_package->solution_code;
            $shipmentDetails['label_size'] = $orderOperation->shipping_solution_carrier_package->label_size;
        }

        if($orderOperation->shipping_account) {
            $shipmentDetails['accountId'] = $orderOperation->shipping_account->account_id;
            $shipmentDetails['passPhrase'] = $orderOperation->shipping_account->pass_phrase;
            $shipmentDetails['requesterId'] = $orderOperation->shipping_account->requester_id;
        } else {
            Log::error('Shipping account not found for order operation: ' . $orderOperation->id);
            return 'Shipping account not found for order operation.';
        }

        $shipmentDetails['ImageRotation'] = null;

        $shipmentDetails['labelFormat'] = 'JPEG';

        $shipmentDetails['returnAddress'] = [
            'address1' => '590 Hatton Ln',
            'address2' => '',
            'city' => 'Junction City',
            'state' => 'OR',
            'postalCode' => '97448',
        ];

        Log::info('Shipment details for label creation: ' . json_encode($shipmentDetails));

        $endiciaClient = new EndicialabelService();
        $response = $endiciaClient->createLabel($shipmentDetails,$orderOperation);

        Log::info($response);
        return null;
    }

    public static function cancelOrder($orderOperation, $reason = 'CUSTOMER_REQUESTED_SELLER_TO_CANCEL')
    {
        $store = $orderOperation->store;
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':

                $orderId = $orderOperation->order_id;
                $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('order_id', $orderId)->first();

                $shopifyServie = new ShopifyAuthService($store->secret_key, $store->service_url);
                $response = $shopifyServie->cancelOrder($shipmentDetail->order->order_id, $reason);

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
                                    'cancellationReason' => $reason,
                                    'statusQuantity' => [
                                        'unitOfMeasurement' => 'EACH',
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
                $response = $authService->cancelOrder($orderOperation->order->purchase_order_id, $finalData);
                if (isset($response)) {
                    Log::info('Walmart cancel order response: ' . json_encode($response));
                    if (isset($response['order'][0]) && isset($response['order'][0]['purchaseOrderId']) && $response['order'][0]['purchaseOrderId'] == $orderOperation->order->purchase_order_id) {
                        Log::info('Walmart cancel order successful: ' . json_encode($response['order'][0]));
                        $orderOperation->update(['status' => OrderOperationStatus::Canceled]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Canceled]);
                        }
                        return true;
                    } else {
                        Log::info('Walmart cancel order error: ' . json_encode($response));
                        return false;
                    }
                } else {
                    Log::info('Walmart cancel order error: No response received');
                    return false;
                }
            case 'ebay':

                $clientId = env('EBAY_CLIENT_ID');
                $clientSecret = env('EBAY_CLIENT_SECRET');
                $redirectUri = env('EBAY_REDIRECT_URI');

                $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                $payload = [
                    'cancelReason' => ($reason != '') ? $reason : 'BUYER_ASKED_CANCEL',
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
            $errors = self::createLabel($orderOperation);
            if (isset($errors) && !empty($errors)) {
                Log::error('Error creating label: ' . $errors);
                return false;
            }
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

                if (isset($response)) {
                    Log::info('Walmart cancel order response: ' . json_encode($response));
                    if (isset($response['order'][0]) && isset($response['order'][0]['purchaseOrderId']) && $response['order'][0]['purchaseOrderId'] == $orderOperation->order->purchase_order_id) {
                        Log::info('Walmart cancel order successful: ' . json_encode($response['order'][0]));
                        $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Shipped]);
                        }
                        return true;
                    } else {
                        Log::info('Walmart cancel order error: ' . json_encode($response));
                        return false;
                    }
                } else {
                    Log::info('Walmart cancel order error: No response received');
                    return false;
                }
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
            'address1' => $shipAddress->address_line_1,
            'address2' => $shipAddress->address_line_2 ?? '',
            'city' => $shipAddress->city,
            'state' => $shipAddress->state_or_region,
            'postalCode' => $shipAddress->postal_code,
            'country' => $shipAddress->country_code,
            'phone' => $shipAddress->phone,
            'company' => $shipAddress->company
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
            'name' => $orderOperation->order->customerAddress->full_name,
            'address1' => $orderOperation->order->customerAddress->address_line_1,
            'address2' => $orderOperation->order->customerAddress->address_line_2 ?? '',
            'city' => $orderOperation->order->customerAddress->city,
            'state' => $orderOperation->order->customerAddress->state,
            'postalCode' => $orderOperation->order->customerAddress->zip,
            'country' => $orderOperation->order->customerAddress->country,
            'phone' => $orderOperation->order->customerAddress->phone,
            'comapany' => $orderOperation->order->customerAddress->company ?? ''
        ];
    }
}
