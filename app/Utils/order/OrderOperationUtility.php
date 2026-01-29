<?php

namespace App\Utils\order;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\ShippingLabelCreationType;
use App\Constants\WeightUnit;
use App\Jobs\SyncFailedMarketplaceShipmentJob;
use App\Models\AccountTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\ShippingAccountPurchase;
use App\Services\Auth\EbayAuthService;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Auth\TemuAuthService;
use App\Services\Auth\WalmartAuthService;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Service\AmazonService;
use App\Services\Marketplace\Service\ShopifyService;
use App\Services\Marketplace\Service\TemuService;
use App\Services\Marketplace\Service\WalmartService;
use App\Utils\EndicialabelService;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderOperationUtility
{
    public static function processShipment($orderOperation, $request): array
    {
        if (isset($orderOperation->order)) {
            if (isset($orderOperation->order->contingent) && $orderOperation->order->contingent) {
                $orderOperation->status = OrderOperationStatus::Error;
                $orderOperation->save();
                return ['success' => false, 'message' => 'Contingent order cannot be processed.'];
            }
        }

        $label_generation = $request->input('label_generation');
        $replacement = $request->input('replacement') ?? '';
        if ($label_generation && !isset($request['resubmit'])) {
            $carrier_id = $request->input('carrier_name');
            $carrier_package = $request->input('carrier_package');
            $carrier_service_id = $request->input('shipping_carrier_service');
            $tracking_number = $request->input('tracking_number');
            $shipment_from = $request->input('shipment_from');
            $weight = $request->input('weight');
            $weight_type = $request->input('weight_type');
            $order_id = $request->input('order_id');

            $updateRecord = array(
                'shipping_carrier_id' => $carrier_id,
                'shipping_carrier_service_id' => $carrier_service_id,
                'shipping_package_id' => $carrier_package,
                'ship_from_id' => $shipment_from,
                'weight_value' => $weight,
                'weight_weight_unit' => $weight_type,
            );
            if (isset($tracking_number) && $tracking_number != '') {
                $updateRecord['tracking_number'] = $tracking_number;
            }
            if (isset($request['products_rubber_stamp']) && $request['products_rubber_stamp'] != '') {
                $updateRecord['products_rubber_stamp'] = $request['products_rubber_stamp'];
            }
            $orderOperation->update($updateRecord);
            Log::info('Updated record :: ' . json_encode($updateRecord));
        }

        Log::info('Label generation type: ' . $label_generation);
        if (isset($label_generation)) {
            $orderOperation->label_creation_type = $label_generation;
        } else {
            $orderOperation->label_creation_type = ShippingLabelCreationType::NoLabel;
        }

        $orderOperation->save();

        Log::info('Order Operation Id :: ' . $orderOperation->id);

        $orderOperation = OrderOperation::with('order', 'order.orderItem', 'order.customer', 'order.customerAddress', 'store', 'store.storeType', 'carrier', 'shipFrom', 'shipFrom.returnAddress', 'shipFrom.fromAddress', 'operationItem', 'operationItem.orderItem')->where('id', $orderOperation->id)->first();

        return self::instantSynchronization($orderOperation, $replacement);
    }

    public static function instantSynchronization($orderOperation, $replacement = ''): array
    {
        $operationType = $orderOperation->operation_type;
        Log::info('Instant synchronization for operation type: ' . $operationType);

        if ($operationType === OrderOperationType::OrderCancellation) {
            self::cancelOrder($orderOperation);
            return ['success' => true, 'message' => ''];
        }

        if (OrderOperationType::isReturn($operationType)) {
            self::returnReplaceOrder($orderOperation);
            return ['success' => true, 'message' => ''];
        }

        if (isset($replacement) && $replacement > 0) {
            self::returnReplaceOrder($orderOperation);
            return ['success' => true, 'message' => ''];
        }

        if (OrderOperationType::isRefund($operationType)) {
            self::refundOrder($orderOperation);
            return ['success' => true, 'message' => ''];
        }

        if (OrderOperationType::isShipment($operationType)) {
            return self::createShipment($orderOperation);
        }
        return ['success' => false, 'message' => 'No Operation Type found for instant synchronization.'];
    }

    public static function createLabel($orderOperation)
    {
        Log::info('Tracking number for order operation: ' . $orderOperation->tracking_number);
        if (isset($orderOperation->tracking_number) && $orderOperation->tracking_number != '') {
            return [];
        }

        $shipmentDetails = [];
        $errorMessages = ShippingUtility::assignSolutionService($orderOperation);
        if (isset($errorMessages) && !empty($errorMessages)) {
            return $errorMessages;
        }

        /* check endicia available limit*/
        self::checkCredit($orderOperation);

        $shipmentDetails['store_id'] = $orderOperation->store_id ?? null;

        $shippingDocumentCreator = new ShipmentDocumentsCreator($orderOperation);
        //    $imageFormat = $shippingDocumentCreator->getLabelFormat();
        //    $rotate = $shippingDocumentCreator->getImageRotate();
        //    Log::info('Image format for label: ' . $imageFormat);

        $printer = $shippingDocumentCreator->getPrinter();
        if (isset($printer)) {
            $shipmentDetails['printerId'] = $printer->id;
            $shipmentDetails['systemId'] = $printer->internal_name;

        } else {
            Log::error('Printer not found for order operation: ' . $orderOperation->id);
            return 'Printer not found for order operation.';
        }

        $operation_type = $orderOperation->operation_type;
        $shipFrom = $orderOperation->shipFrom;

        if (OrderOperationType::isReturn($operation_type)) {
            if($orderOperation->return_label_set_from_address == 2){
                $shipmentDetails['fromAddress'] = self::getAddress($shipFrom->returnAddress);
            }else{
                //$shipmentDetails['fromAddress'] = self::getAddress($shipFrom->fromAddress);
                $shipmentDetails['fromAddress'] = self::mapCustomerAddress($orderOperation);
            }

            $shipmentDetails['toAddress'] = self::getAddress($shipFrom->returnAddress);
            $shipmentDetails['is_return'] = true;
        } else {
            $shipmentDetails['fromAddress'] = self::getAddress($shipFrom->fromAddress);
            $shipmentDetails['toAddress'] = self::mapCustomerAddress($orderOperation);
        }

        Log::info('Image creation error 1');

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
        Log::info('Image creation error 2');

        $shipmentDetails['MailClass'] = $orderOperation->shipping_solution_carrier_service->solution_code;
        $shipmentDetails['MailpieceShape'] = $orderOperation->shipping_solution_carrier_package->solution_code;
        $shipmentDetails['transactionId'] = $orderOperation->order->order_id;

//        if($shipmentDetails['MailpieceShape'] == 'Letter') {
//            $shipmentDetails['MailpieceShape'] = 'ENVELOPE';
//        }

        Log::info('Image creation error 1');

        $shipmentDetails['weight'] = [
            'value' => $orderOperation->weight_value,
            'unit' => WeightUnit::getUnitName($orderOperation->weight_weight_unit),
        ];

        if ($orderOperation->shipping_carrier_package) {
            $shipmentDetails['non_machinable'] = $orderOperation->shipping_carrier_package->non_machinable;
        }

        if ($orderOperation->shipping_solution_carrier_package) {
            $shipmentDetails['packageType'] = $orderOperation->shipping_solution_carrier_package->solution_code;
            $shipmentDetails['label_size'] = $orderOperation->shipping_solution_carrier_package->label_size;
        }

        if ($orderOperation->shipping_account) {
            $shipmentDetails['accountId'] = $orderOperation->shipping_account->account_id;
            $shipmentDetails['passPhrase'] = $orderOperation->shipping_account->pass_phrase;
            $shipmentDetails['requesterId'] = $orderOperation->shipping_account->requester_id;
            $shipmentDetails['account_info'] = $orderOperation->shipping_account->id;
        } else {
            Log::error('Shipping account not found for order operation: ' . $orderOperation->id);
            return 'Shipping account not found for order operation.';
        }

        if (OrderOperationType::isReturn($operation_type)) {
            $shipmentDetails['ImageRotation'] = 'None';
            $shipmentDetails['labelFormat'] = 'JPEG';
        } else {
            $shipmentDetails['ImageRotation'] = 'Rotate180';
            $shipmentDetails['labelFormat'] = 'ZPLII';
        }


        $shipmentDetails['returnAddress'] = [
            'address1' => '590 Hatton Ln',
            'address2' => '',
            'city' => 'Junction City',
            'state' => 'OR',
            'postalCode' => '97448',
        ];

        self::setRubberStampInfo($orderOperation, $shipmentDetails);

        Log::info('Shipment details for label creation: ' . json_encode($shipmentDetails));

        $endiciaClient = new EndicialabelService();
        $response = $endiciaClient->createLabel($shipmentDetails, $orderOperation->id);
        Log::info('Response from Endicia label creation: ' . json_encode($response));
        if (isset($response['trackingNumber'])) {
            Log::info('Label created successfully for order operation: ' . $orderOperation->id);
            // Unsetting because of the issue with the label creation
            unset($orderOperation->shipping_carrier_package);
            unset($orderOperation->shipping_account);
            unset($orderOperation->shipping_solution_carrier_package);
            unset($orderOperation->shipping_solution_carrier_service);

            $orderOperation->tracking_number = $response['trackingNumber'];
            $orderOperation->status = OrderOperationStatus::LabelCreated;
            $orderOperation->save();

            if (!OrderOperationType::isReturn($operation_type)) {
                $result = self::waitForOperationCompleted($response['trackingNumber']);

                if (!$result) {
                    DB::connection('aselna_printer_db')->table('PrintJobs')
                        ->where('fileName', $response['trackingNumber'] . '.txt')
                        ->update(['status' => 'failed']);

                   // AccountTransaction::where('tracking_number', $response['trackingNumber'])
                      //  ->update(['status' => 'failed']);

                    Log::error('Label creation operation did not complete successfully for order operation: ' . $orderOperation->id);
                    return ['Error creating label. Printing Failed.'];
                } else {
                    //AccountTransaction::where('tracking_number', $response['trackingNumber'])
                        //->update(['status' => 'completed']);
                }
            }

            $orderOperation->tracking_number = $response['trackingNumber'];
            $orderOperation->status = OrderOperationStatus::LabelPrinted;
            $orderOperation->save();

            self::updateMergedOrderIfExists($orderOperation, $response['trackingNumber']);

            return [];
        } else {
            Log::error('Error creating label for order operation: ' . $orderOperation->id . ' - ' . json_encode($response));
            return $response['error'] ?? 'Error In Endicia Response.';
        }
    }

    public static function checkCredit($orderOperation){

        try {
            if (isset($orderOperation->shipping_account) && $orderOperation->shipping_account != '') {
                if ($orderOperation->shipping_account->auto_recharge) {
                    $shipmentDetails['accountId'] = $orderOperation->shipping_account->account_id;
                    $shipmentDetails['passPhrase'] = $orderOperation->shipping_account->pass_phrase;
                    $shipmentDetails['requesterId'] = $orderOperation->shipping_account->requester_id;
                    $shipmentDetails['account_info'] = $orderOperation->shipping_account->id;

                    $endiciaClient = new EndicialabelService();
                    $response = $endiciaClient->getAccountStatus($shipmentDetails);

                    if (!empty($response)) {
                        if (isset($response['status']) && $response['status'] == 0) {
                            if (isset($response['postageBalance'])) {
                                $postageBalance = $response['postageBalance'];
                                Log::info('Postage Balance : ' . json_encode($postageBalance));

                                if (isset($postageBalance) && $postageBalance < 100) {
                                    $purchaseCredit = 500 - $postageBalance;

                                    /* purchase credit*/
                                    $shipmentDetails['credit'] = $purchaseCredit;
                                    $shipmentDetails['requestId'] = (string) Str::uuid();
                                    $response = $endiciaClient->purchaseCredit($shipmentDetails);

                                    Log::info('uto Purchase credit Response:' . json_encode($response));

                                    if (isset($response['status']) && $response['status'] == 0) {

                                        $purchaseData = [
                                            'shipping_account_id' => $orderOperation->shipping_account->id,
                                            'purchase_date' => date('Y-m-d H:i:s'),
                                            'amount' => $purchaseCredit,
                                            'errors' => NULL,
                                        ];
                                    } else {
                                        $purchaseData = [
                                            'shipping_account_id' => $orderOperation->shipping_account->id,
                                            'purchase_date' => date('Y-m-d H:i:s'),
                                            'amount' => $purchaseCredit,
                                            'errors' => (isset($response['message']) && $response['message'] != '') ? $response['message'] : NULL,
                                        ];
                                    }
                                    ShippingAccountPurchase::create($purchaseData);
                                    Log::info('Auto Purchase credit : ' . json_encode($purchaseCredit));
                                }
                            }
                        }
                    }
                } else {
                    Log::error('Shipping account auto recharge disabled');
                }
            } else {
                Log::error('Shipping account not found for purchase auto credit');
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Error processing check transaction credit', [
                'marketplace' => '',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['order_operation' => $orderOperation]);

            Log::error('Error processing check transaction credit: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    public static function waitForOperationCompleted($trackingNumber, $maxRetries = 30)
    {
        $retry = 0;
        $waitSeconds = 2;
        do {
            $printJob = DB::connection('aselna_printer_db')->table('PrintJobs')
                ->where('fileName', $trackingNumber . '.txt')
                ->where('status', 'completed')->count();

            if ($printJob > 0) {
                return true;
            }

            $printJob = DB::connection('aselna_printer_db')->table('PrintJobs')
                ->where('fileName', $trackingNumber . '.txt')
                ->where('status', 'failed')->count();

            if ($printJob > 0) {
                return false;
            }
            sleep($waitSeconds);
            $retry++;
        } while ($retry < $maxRetries);

        return true;
    }

    public static function setRubberStampInfo($orderOperation, &$shipmentDetails)
    {
        $storeType = $orderOperation->store->storeType;

        // Group products by code and sum quantities
        Log::info('Rubber stamp Ordero peration item: ' . json_encode($orderOperation->operationItem));
        $productMap = [];
        foreach ($orderOperation->operationItem as $item) {
            $orderItem = $item->orderItem;
            $product = $item->orderItem->product;
            $code = $product ? $product->product_sku : $item->sku;
            /*$code = isset($item->sku) ? $item->sku : $product->product_sku;*/

            if (!isset($productMap[$code])) {
                $productMap[$code] = 0;
            }
            $productMap[$code] += $item->quantity;
        }

        // Get or generate product codes line
        $codesLine = $orderOperation->products_rubber_stamp ?? '';

        if (empty(trim($codesLine))) {
            $codes = [];

            // Sort product codes alphabetically
            ksort($productMap);

            foreach ($productMap as $code => $quantity) {
                $codes[] = "({$code} x {$quantity})";
            }

            $codesLine = implode(' ', $codes);
        }

        // Get store name for label
        $storeName = $orderOperation->store->label_order_prefix ?? $storeType->name;

        // Create order ID line
        $orderIds = [];
        foreach ($orderOperation->order->orderItem as $item) {
            $orderId = self::getOrderIdForStamp($orderOperation->order, $storeType, false);
            if (!in_array($orderId, $orderIds)) {
                $orderIds[] = $orderId;
            }
        }

//        $orderIdLine = $storeName . ' ' . implode(' ', $orderIds);
        $orderIdLine = $storeName . ' ' . $orderIds[0];

        // If order ID line is too long, use shortened version
        if (strlen($orderIdLine) > 50) {
            $orderIds = [];
            foreach ($orderOperation->order->orderItem as $item) {
                $orderId = self::getOrderIdForStamp($orderOperation->order, $storeType, true);
                if (!in_array($orderId, $orderIds)) {
                    $orderIds[] = $orderId;
                }
            }

//            $orderIdLine = $storeName . ' ' . implode(' ', $orderIds);
            $orderIdLine = $storeName . ' ' . $orderIds[0];
        }

        // Add RMA number if present
        $rma = $orderOperation->rma_number;
        if (!empty($rma)) {
            $shipmentDetails['RubberStamp1'] = "RMA {$rma} {$orderIdLine}";
        } else {
            $shipmentDetails['RubberStamp1'] = $orderIdLine;
        }

        // Assign product codes to appropriate rubber stamp fields
        self::assignProductCodesStamps($codesLine, $shipmentDetails);
    }

    private static function getOrderIdForStamp($order, $storeType, $shorten = false)
    {
        $orderId = $order->order_id;

        // Format order ID based on store type and shorten flag
        switch (strtolower($storeType->name)) {
            case 'ebay':
                return $shorten ? substr($orderId, -8) : $orderId;
            case 'amazon':
                return $shorten ? substr($orderId, -5) : $orderId;
            case 'walmart':
                return $shorten ? substr($orderId, -6) : $orderId;
            case 'shopify':
                $orderId = $order->purchase_order_id;
                return $shorten ? substr($orderId, -6) : $orderId;
            default:
                return $shorten ? substr($orderId, -5) : $orderId;
        }
    }

    private static function assignProductCodesStamps($codesLine, &$request)
    {
        // Split code line if it's too long for a single rubber stamp field
        if (strlen($codesLine) <= 50) {
            $request['RubberStamp3'] = $codesLine;
        }
        /*if (strlen($codesLine) <= 50) {
            $request['RubberStamp3'] = $codesLine;
        } else if (strlen($codesLine) <= 100) {
            $request['RubberStamp2'] = substr($codesLine, 0, 50);
            $request['RubberStamp3'] = substr($codesLine, 50);
        } else if (strlen($codesLine) <= 150) {
            $request['RubberStamp2'] = substr($codesLine, 0, 50);
            $request['RubberStamp3'] = substr($codesLine, 50, 50);
        } else {
            $request['RubberStamp2'] = substr($codesLine, 0, 50);
            $request['RubberStamp3'] = substr($codesLine, 50, 50);
        }*/
    }


    public static function cancelOrder($orderOperation, $reason = 'CUSTOMER_REQUESTED_SELLER_TO_CANCEL')
    {
        Log::info('Cancel order for operation id: ' . $orderOperation->id);
        $store = $orderOperation->store;
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':

                $orderId = $orderOperation->order_id;
                $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('order_id', $orderId)->first();

                $shopifyServie = new ShopifyAuthService($store->secret_key, $store->service_url);
                $response = $shopifyServie->cancelOrder($shipmentDetail->order->order_id, $reason);

                if (!empty($response['data']['orderCancel']['orderCancelUserErrors']) || !empty($response['errors'])) {
                    if(isset($response['data']['orderCancel']['orderCancelUserErrors'])) {
                        foreach ($response['data']['orderCancel']['orderCancelUserErrors'] as $error) {
                            //echo "Error: {$error['message']}\n";
                            Log::info('Shopify cancer order error:' . json_encode($error));
                            $data['message'] = isset($error['message']) ? $error['message'] : json_encode($error);
                            $data['status'] = true;
                            return $data;
                            //return false;
                        }
                    }

                    if(isset($response['errors'])) {
                        foreach ($response['errors'] as $error) {
                            //echo "Error: {$error['message']}\n";
                            Log::info('Shopify cancer order error:' . json_encode($error));
                            $data['message'] = isset($error['message']) ? $error['message'] : json_encode($error);
                            $data['status'] = true;
                            return $data;
                            //return false;
                        }
                    }
                } else {
                    //echo "Order canceled successfully!";

                    if (isset($orderOperation->order->orderItem)) {
                        foreach ($orderOperation->order->orderItem as $o_item) {
                            $item_qty = $o_item->quantity;
                            $orderItem = OrderItem::findOrFail($o_item->id);
                            $orderItem->update(['canceled_quantity' => $item_qty]);
                        }
                    }
                    Log::info('Shopify cancer order successful:');
                    $data['message'] = 'Shopify cancer order successful';
                    $data['status'] = true;
                    return $data;
                    //return true;
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
                Log::info('Walmart cancel order request: '. json_encode($finalData));

                $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
                $response = $authService->cancelOrder($orderOperation->order->purchase_order_id, $finalData);
                if (isset($response)) {
                    Log::info('Walmart cancel order response: ' . json_encode($response));
                    if (isset($response['order']) && isset($response['order']['purchaseOrderId']) && $response['order']['purchaseOrderId'] == $orderOperation->order->purchase_order_id) {
                        Log::info('Walmart cancel order successful: ' . json_encode($response['order']));
                        $orderOperation->update(['status' => OrderOperationStatus::Canceled]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Canceled]);
                        }
                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['canceled_quantity' => $item_qty]);
                            }
                        }

                        $data['status'] = true;
                        return $data;
                        //return true;
                    } else {
                        Log::info('Walmart cancel order error: ' . json_encode($response));
                        $data['message'] = 'Walmart cancel order error';
                        $data['status'] = false;
                        return $data;
                        //return false;
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
                $createdCancellation = $authService->checkCancellation($orderOperation->order->order_id);
                if(isset($createdCancellation) && !isset($createdCancellation['error'])) {
                    $cancellation = $createdCancellation['cancellations'][0];

                    if ($cancellation['cancelStatus'] == 'CANCEL_REQUESTED'
                        && $cancellation['cancelState'] == 'APPROVAL_PENDING') {
                        Log::info('Cancellation :: ' . json_encode($cancellation));
                        $response = $authService->approveCancellation($cancellation['cancelId']);

                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['canceled_quantity' => $item_qty]);

                                $data['status'] = true;
                                return $data;
                            }
                        } else {
                            $data['status'] = false;
                            $data['message'] = 'Cancellation code Need to be implemented for this type of data';
                            return $data;
                        }
                    }
                } else {
                    $payload = [
                        'cancelReason' => ($reason != '') ? $reason : 'BUYER_ASKED_CANCEL',
                        'legacyOrderId' => $orderOperation->order->order_id,
                    ];
                    $response = $authService->cancelOrder($payload);

                    if (!isset($response['cancelId'])) {

                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['canceled_quantity' => $item_qty]);
                            }
                        }
                        Log::info('eBay cancer order error:' . json_encode($response['warnings']));
                        $data['message'] = json_encode($response['warnings']);
                        $data['status'] = false;
                        return $data;
                        //return false;
                    } else {
                        //echo "Order canceled successfully!";
                        Log::info('Shopify cancer order successful:');
                        $data['status'] = true;
                        return $data;
                        //return true;
                    }
                }

                break;
            case 'amazon' :
                $amazonService = new AmazonService();
                $response = $amazonService->cancelOrder($orderOperation, $store);

                if (isset($orderOperation->order->orderItem) && isset($response['status']) && $response['status'] == 'Success') {
                    foreach ($orderOperation->order->orderItem as $o_item) {
                        $item_qty = $o_item->quantity;
                        $orderItem = OrderItem::findOrFail($o_item->id);
                        $orderItem->update(['canceled_quantity' => $item_qty]);
                    }
                    $data['status'] = true;
                    return $data;
                } else {
                    $data['status'] = false;
                    $data['message'] = $response['message'] ?? 'Cancellation not working from API End';
                    return $data;
                }
                break;
            case 'temu':

                $order_id = $orderOperation->order_id;

                $temuService = new TemuAuthService();
                $response = $temuService->cancelOrder($order_id, $store);

                if (isset($orderOperation->order->orderItem) && isset($response['success']) && $response['success']) {
                    foreach ($orderOperation->order->orderItem as $o_item) {
                        $item_qty = $o_item->quantity;
                        $orderItem = OrderItem::findOrFail($o_item->id);
                        $orderItem->update(['canceled_quantity' => $item_qty]);
                    }
                    $data['status'] = true;
                    return $data;
                } else {
                    $data['status'] = false;
                    $data['message'] = $response['message'] ?? 'Cancellation not working from API End';
                    return $data;
                }

                break;
        }

        $data['status'] = false;
        return $data;
    }

    private static function returnReplaceOrder($orderOperation)
    {

        if ($orderOperation->label_creation_type == ShippingLabelCreationType::CreateLabel) {
            $errors = self::createLabel($orderOperation);
            if (isset($errors) && !empty($errors)) {
                Log::error('Error creating label: ' . json_encode($errors));
                //throw new \Exception('Error creating label: ' . json_encode($errors));
                return false;
            } else {

                $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped]);
                if (isset($orderOperation->order_id)) {
                    $order = Order::findOrFail($orderOperation->order_id);
                    $order->update(['order_status' => OrderStatus::Shipped]);
                }

                if (isset($orderOperation->order->orderItem)) {
                    foreach ($orderOperation->order->orderItem as $o_item) {
                        $item_qty = $o_item->quantity;
                        $orderItem = OrderItem::findOrFail($o_item->id);
                        $orderItem->update(['shipped_quantity' => $item_qty]);
                        if ($orderOperation->operation_type == OrderOperationType::OrderReplacement) {
                            $orderItem->update(['replaced_quantity' => $item_qty]);
                        }
                    }
                }

                return true;
            }
        }

        return false;
    }

    private static function refundOrder($orderOperation)
    {
        $store = $orderOperation->store;
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':
                $orderId = $orderOperation->order_id;
                $order_id = $orderOperation->purchase_order_id;
                $shipmentDetail = OrderOperation::with('operationItem', 'order', 'order.customerAddress', 'carrierService', 'store', 'operationItem.orderItem', 'operationItem.orderItem.product')->where('order_id', $orderId)->first();

                /*$shopifyAuthServie = new ShopifyAuthService($store->secret_key, $store->service_url);
                $order = $shopifyAuthServie->getOrderDetail($orderId);

                Log::info('Shopify order rdetail:'.json_encode($order));
                echo '<pre>';
                print_r($order);
                echo '<pre>';

                die;

                if (!$order) {
                    return response()->json(['error' => 'Order not found'], 404);
                }

                $orderId = (int) last(explode('/', $order['id']));
                $lineItem = $order['lineItems']['edges'][0]['node'];
                $lineItemId = (int) last(explode('/', $lineItem['id']));
                $quantity = (int) $lineItem['quantity'];
                $amount = $order['transactions'][0]['amount']['amount'];
                $transactionId = (int) last(explode('/', $order['transactions'][0]['id']));


                $response = $shopifyAuthServie->orderRefund($orderId, $lineItemId, $quantity, $transactionId, $amount);

                Log::info('Shopify order refunded response:'.json_encode($response));*/

                $shopifyServie = new ShopifyService();
                $response = $shopifyServie->refundOrder($shipmentDetail->order->order_id, $store);

                Log::info('Shopify order refunded response:' . json_encode($response));

                if ($response) {
                    //echo "Order canceled successfully!";
                    if (isset($orderOperation->order->orderItem)) {
                        foreach ($orderOperation->order->orderItem as $o_item) {
                            $item_qty = $o_item->quantity;
                            $orderItem = OrderItem::findOrFail($o_item->id);
                            $orderItem->update(['refund_quantity' => $item_qty]);
                        }
                    }
                    Log::info('Shopify order refunded successful:');
                    return true;
                } else {
                    return false;
                }
                break;
            case 'walmart':
                $finalData = [
                    'orderRefund' => [
                        'purchaseOrderId' => $orderOperation->order->purchase_order_id
                    ]
                ];
                $count = 1;
                foreach ($orderOperation->operationItem as $item) {
                    Log::info('Itema data for refund operation :: ' . json_encode($item));
                    $orderLine = [
                        'lineNumber' => $count,
                        'refundReason' => 'CustomerReturn',
                        'refunds' => [
                            'refund' => [
                                [
                                    'refundCharges' => [
                                        'refundCharge' => [
                                            'refundReason' => 'CustomerReturn',
                                            'charge' => [
                                                [
                                                    'chargeType' => 'PRODUCT',
                                                    'chargeName' => 'ItemPrice',
                                                    'chargeAmount' => [
                                                        'currency' => 'USD',
                                                        'amount' => $orderOperation->order->order_total,
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $finalData['orderRefund']['orderLines']['orderLine'][] = $orderLine;
                    $count++;
                }

                Log::info('Final data for the refund operation :: ' . json_encode($finalData));

                $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
                $response = $authService->refundOrder($orderOperation->order->purchase_order_id, $finalData);

                Log::info('Walmart refund order response: ' . json_encode($response));

                if (isset($response)) {
                    $orderResp = $response['order'];
                    Log::info('Walmart refund order response: ' . json_encode($orderResp));
                    if (isset($orderResp) && isset($orderResp['purchaseOrderId']) && $orderResp['purchaseOrderId'] == $orderOperation->order->purchase_order_id) {
                        Log::info('Walmart refund order successful: ' . json_encode($orderResp));
                        $orderOperation->update(['status' => OrderOperationStatus::REFUNDED]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Refund]);
                        }
                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['refund_quantity' => $item_qty]);
                            }
                        }
                        return true;
                    } else {
                        Log::info('Walmart refund order error: ' . json_encode($response));
                        return false;
                    }
                } else {
                    Log::info('Walmart refund order error: No response received');
                    return false;
                }

                break;
            case 'ebay':

                $payload = [
                    "reasonForRefund" => "BUYER_CANCEL"
                ];

                foreach ($orderOperation->operationItem as $item) {

                    $payload["refundItems"][] = [
                        "lineItemId" => $item->orderItem->store_sku,
                        "refundAmount" => $item->item_price,
                    ];

                }

                /*echo '<pre>';
                print_r($payload);
                echo '</pre>';die;*/

                $clientId = env('EBAY_CLIENT_ID');
                $clientSecret = env('EBAY_CLIENT_SECRET');
                $redirectUri = env('EBAY_REDIRECT_URI');

                $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                $result = $authService->issueRefund($orderOperation->order->order_id, $payload);

                if (!isset($result['error'])) {
                    $orderOperation->update(['status' => OrderOperationStatus::REFUNDED]);
                    if (isset($orderOperation->order_id)) {
                        $order = Order::findOrFail($orderOperation->order_id);
                        $order->update(['order_status' => OrderStatus::Refund]);
                    }

                    if (isset($orderOperation->order->orderItem)) {
                        foreach ($orderOperation->order->orderItem as $o_item) {
                            $item_qty = $o_item->quantity;
                            $orderItem = OrderItem::findOrFail($o_item->id);
                            $orderItem->update(['refund_quantity' => $item_qty]);
                        }
                    }

                    return true;
                }else{
                    return false;
                }

                break;
            case 'temu':

                $orderId = $orderOperation->order_id;

                $temuService = new TemuService();
                $result = $temuService->refundOrder($orderId);
                if (isset($result['success']) && $result['success']) {
                    $orderOperation->update(['status' => OrderOperationStatus::REFUNDED]);
                    if (isset($orderId)) {
                        $order = Order::findOrFail($orderId);
                        $order->update(['order_status' => OrderStatus::Refund]);
                    }

                    if (isset($orderOperation->order->orderItem)) {
                        foreach ($orderOperation->order->orderItem as $o_item) {
                            $item_qty = $o_item->quantity;
                            $orderItem = OrderItem::findOrFail($o_item->id);
                            $orderItem->update(['refund_quantity' => $item_qty]);
                        }
                    }
                    return true;
                }

                break;
        }

        return false;
    }

    public static function createShipment($orderOperation): array
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            Log::info('Creating shipment for order operation: ' . $orderOperation->label_creation_type);
            if ($orderOperation->label_creation_type == ShippingLabelCreationType::CreateLabel) {
                $errors = self::createLabel($orderOperation);
                if (isset($errors) && !empty($errors)) {
                    //DB::rollBack();

                    CriticalErrorService::report('Error creating/priting label', [
                        'marketplace' => '',
                        'reason' => json_encode($errors) ?? 'Unknown',
                        'method' => __METHOD__
                    ],['order_operation' => $orderOperation]);

                    throw new \Exception($errors);
                }
            }
            $store = $orderOperation->store;
            $storeType = $orderOperation->store->storeType->name;
            $success = false;
            $message = '';

            switch (strtolower($storeType)) {
                case 'shopify':
                    $shopifyServie = new ShopifyService();
                    $response = $shopifyServie->updateFullfillment($orderOperation->order_id, $orderOperation);

                    if (!isset($response['errors'])) {
                        $success = true;
                        $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped, 'ship_date' => now()]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Shipped]);
                        }

                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['shipped_quantity' => $item_qty]);
                            }
                        }

                        $orderOperations = OrderOperation::with('order', 'order.orderItem')->where('is_merged', true)
                            ->where('merged_with', $orderOperation->id)
                            ->get();

                        if($orderOperations) {
                            foreach ($orderOperations as $oop) {
                                $response = $shopifyServie->updateFullfillment($oop->order_id, $orderOperation);

                                if (!isset($response['errors'])) {
                                    $oop->update(['status' => OrderOperationStatus::MarkAsShipped, 'ship_date' => now()]);
                                    if (isset($oop->order_id)) {
                                        $order = Order::findOrFail($oop->order_id);
                                        $order->update(['order_status' => OrderStatus::Shipped]);
                                    }

                                    if (isset($oop->order->orderItem)) {
                                        foreach ($oop->order->orderItem as $o_item) {
                                            $item_qty = $o_item->quantity;
                                            $orderItem = OrderItem::findOrFail($o_item->id);
                                            $orderItem->update(['shipped_quantity' => $item_qty]);
                                        }
                                    }
                                    $success = true;
                                } else {
                                    $success = false;
                                }
                            }
                        }
                    } else {
                        $success = false;
                    }
                    break;

                case 'walmart':
                    $walmartServie = new WalmartService();
                    $is_success = $walmartServie->shipmentUpdate($orderOperation, $orderOperation, $store);
                    $orderOperations = OrderOperation::where('is_merged', true)
                        ->where('merged_with', $orderOperation->id)
                        ->get();
                    if($orderOperations) {
                        foreach ($orderOperations as $o_item) {
                            $walmartServie->shipmentUpdate($orderOperation, $o_item, $store);
                        }
                    }
                    $success = $is_success;
                    break;

                case 'ebay':
                    $payload = [
                        "shippedDate" => now()->toIso8601ZuluString(),
                        "shippingCarrierCode" => $orderOperation->carrier->name,
                        "trackingNumber" => $orderOperation->tracking_number
                    ];

                    foreach ($orderOperation->order->orderItem as $item) {
                        $payload["lineItems"][] = [
                            "lineItemId" => $item->store_sku,
                            "quantity" => $item->quantity,
                        ];
                    }

                    $clientId = env('EBAY_CLIENT_ID');
                    $clientSecret = env('EBAY_CLIENT_SECRET');
                    $redirectUri = env('EBAY_REDIRECT_URI');

                    $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
                    $result = $authService->createShippingFulfillment($orderOperation->order->order_id, $payload);

                    if (!isset($result['error'])) {
                        $success = true;
                        $orderOperation->update(['status' => OrderOperationStatus::MarkAsShipped,'ship_date' => now()]);
                        if (isset($orderOperation->order_id)) {
                            $order = Order::findOrFail($orderOperation->order_id);
                            $order->update(['order_status' => OrderStatus::Shipped]);
                        }
                        if (isset($orderOperation->order->orderItem)) {
                            foreach ($orderOperation->order->orderItem as $o_item) {
                                $item_qty = $o_item->quantity;
                                $orderItem = OrderItem::findOrFail($o_item->id);
                                $orderItem->update(['shipped_quantity' => $item_qty]);
                            }
                        }

                        $orderOperations = OrderOperation::with('order', 'order.orderItem', 'operationItem')->where('is_merged', true)
                            ->where('merged_with', $orderOperation->id)
                            ->get();

                        if (count($orderOperations ?? []) > 0) {
                            foreach ($orderOperations as $oop) {
                                $payload2 = [
                                    "shippedDate" => now()->toIso8601ZuluString(),
                                    "shippingCarrierCode" => $orderOperation->carrier->name,
                                    "trackingNumber" => $orderOperation->tracking_number
                                ];

                                foreach ($oop->order->orderItem as $item) {
                                    $payload2["lineItems"][] = [
                                        "lineItemId" => $item->store_sku,
                                        "quantity" => $item->quantity,
                                    ];
                                }

                                $response = $authService->createShippingFulfillment($oop->order->order_id, $payload2);

                                if (!isset($response['errors'])) {

                                    $oop->update(['status' => OrderOperationStatus::MarkAsShipped,'ship_date' => now()]);
                                    if (isset($oop->order_id)) {
                                        $order = Order::findOrFail($oop->order_id);
                                        $order->update(['order_status' => OrderStatus::Shipped]);
                                    }
                                    if (isset($oop->order->orderItem)) {
                                        foreach ($oop->order->orderItem as $o_item) {
                                            $item_qty = $o_item->quantity;
                                            $orderItem = OrderItem::findOrFail($o_item->id);
                                            $orderItem->update(['shipped_quantity' => $item_qty]);
                                        }
                                    }
                                    $success = true;
                                } else {
                                    $success = false;
                                }
                            }
                        }
                    } else {
                        Log::info('eBay ship fulfillment error: ' . json_encode($result['error'] ?? 'Unknown error'));
                        $success = false;
                    }
                    break;

                case 'amazon' :
                    $amazonService = new AmazonService();
                    $is_success = $amazonService->createShipment($orderOperation,$orderOperation, $store);
                    $orderOperations = OrderOperation::where('is_merged',true)
                        ->where('merged_with',$orderOperation->id)
                        ->get();
                    if($orderOperations) {
                        foreach ($orderOperations as $o_item) {
                            $amazonService->createShipment($orderOperation, $o_item, $store);
                        }
                    }
                    $success = $is_success;
                    break;
                case 'temu' :
                    $temuService = new TemuService();
                    $is_success = $temuService->createShipment($orderOperation,$orderOperation, $store);

                    $orderOperations = OrderOperation::where('is_merged',true)
                        ->where('merged_with',$orderOperation->id)
                        ->get();
                    if($orderOperations) {
                        foreach ($orderOperations as $o_item) {
                            $temuService->createShipment($orderOperation, $o_item, $store);
                        }
                    }
                    $success = $is_success;
                    break;
                case 'newegg' :
                    // TODO NewEgg Order Implementation
                    break;
            }

            // If everything went well, commit the transaction
            Log::info('Creating shipment for order operation status: ' . $success);
            if ($success) {
                DB::commit();
                return ['success' => true, 'message' => ''];
            } else {
                // If there was an API error but no exception, still rollback
                DB::commit();
                $order_operation = OrderOperation::with('store')->select('id','tracking_number','status')->where('id',$orderOperation->id)->first();
                if(isset($order_operation->store->auto_resubmit_shipment) && $order_operation->store->auto_resubmit_shipment == 1) {
                    if ($order_operation->tracking_number && $order_operation->status == OrderOperationStatus::LabelPrinted) {
                        dispatch(new SyncFailedMarketplaceShipmentJob($order_operation->id))->delay(now()->addMinutes(15));
                    }
                }
                return ['success' => false, 'message' => $message];
            }
        } catch (\Exception $e) {
            // If anything went wrong, rollback the transaction
            DB::commit();
            Log::error('Error in createShipment: ' . $e->getMessage());
            $order_operation = OrderOperation::with('store')->select('id','tracking_number','status')->where('id',$orderOperation->id)->first();
            if(isset($order_operation->store->auto_resubmit_shipment) && $order_operation->store->auto_resubmit_shipment == 1) {
                if ($order_operation->tracking_number && $order_operation->status == OrderOperationStatus::LabelPrinted) {
                    dispatch(new SyncFailedMarketplaceShipmentJob($order_operation->id))->delay(now()->addMinutes(15));
                }
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
            'name' => $shipAddress->full_name,
            'address1' => $shipAddress->address_line_1,
            'address2' => $shipAddress->address_line_2 ?? '',
            'address3' => $shipAddress->address_line_3 ?? '',
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
            'name' => (isset($orderOperation->order->customerAddress) && $orderOperation->order->customerAddress->full_name != '') ? $orderOperation->order->customerAddress->full_name : $orderOperation->order->customer->full_name,
            'address1' => $orderOperation->order->customerAddress->address_line_1,
            'address2' => $orderOperation->order->customerAddress->address_line_2 ?? '',
            'address3' => $orderOperation->order->customerAddress->address_line_3 ?? '',
            'city' => $orderOperation->order->customerAddress->city,
            'state' => $orderOperation->order->customerAddress->state_code ?? $orderOperation->order->customerAddress->state,
            'postalCode' => explode('-', $orderOperation->order->customerAddress->zip ?? '')[0],
            'country' => $orderOperation->order->customerAddress->country,
            'phone' => $orderOperation->order->customerAddress->phone,
            'comapany' => $orderOperation->order->customerAddress->company ?? ''
        ];
    }

    private static function updateMergedOrderIfExists($orderOperation, $tracking_number)
    {
        $orderOperations = OrderOperation::where('is_merged', true)
            ->where('merged_with', $orderOperation->id)
            ->get();

        foreach ($orderOperations as $tempOrderOperation) {
            $tempOrderOperation->tracking_number = $tracking_number;
            $tempOrderOperation->save();
        }
    }
}
