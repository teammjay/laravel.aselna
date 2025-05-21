<?php

namespace App\Utils\order;

use App\Constants\OrderOperationStatus;
use App\Constants\OrderOperationType;
use App\Constants\OrderStatus;
use App\Constants\ShippingLabelCreationType;
use App\Services\Marketplace\Service\ShopifyService;
use Illuminate\Support\Facades\Log;

class OrderOperationUtility
{
    public static function processShipment($orderOperation, $request)
    {
        if (isset($orderOperation->order)) {
            if (isset($orderOperation->order->contingent) && $orderOperation->order->contingent) {
                $orderOperation->status = OrderOperationStatus::Error;
                $orderOperation->save();
                return;
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

        $label_generation = $request->input('label_generation');
        if ($label_generation) {
            $updateRecord['label_creation_type'] = ShippingLabelCreationType::getShippingLabelCreationTypeId($label_generation);
        } else {
            $updateRecord['label_creation_type'] = ShippingLabelCreationType::NoLabel;
        }

        Log::info('Updated record :: ' . json_encode($updateRecord));

        $orderOperation->update($updateRecord);

        $instantSync = self::instantSynchronization($orderOperation);
        if ($instantSync) {

        }

    }

    public static function instantSynchronization($orderOperation)
    {
        $operationType = $orderOperation->operation_type;

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
    }

    public static function createLabel()
    {

    }

    private static function cancelOrder($orderOperation)
    {

    }

    private static function refundOrder($orderOperation)
    {

    }

    private static function createShipment($orderOperation)
    {
        Log::info('Create shipment :: ' . json_encode($orderOperation));
        if ($orderOperation->label_creation_type == ShippingLabelCreationType::CreateLabel) {
            // TODO Create a label
        }
        $storeType = $orderOperation->store->storeType->name;
        switch (strtolower($storeType)) {
            case 'shopify':
                $shopifyServie = new ShopifyService();
                $shopifyServie->updateFullfillment($orderOperation->order_id);
                break;
            case 'walmart':
                break;
            case 'ebay':
                break;
        }
        return true;
    }
}
