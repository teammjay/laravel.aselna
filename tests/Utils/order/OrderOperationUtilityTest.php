<?php

namespace Tests\Utils\order;

use App\Models\OrderOperation;
use App\Models\ShippingCarrierService;
use App\Utils\order\OrderOperationUtility;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
class OrderOperationUtilityTest extends TestCase
{
    public function testCreateLabel()
    {
//        $order = OrderOperation::findOrFail('009d877e-4085-453b-9964-20586cc1ec52'); // Assuming an order with ID 1 exists
//
//        $utility = new OrderOperationUtility();
//        $utility->createLabel($order);
//
//        new ShipmentDocumentsCreator($order);
//
//        $order_detail = (object)[ 'requested_shipment_service' => '' ]; // Mock order detail
//        $order_detail->requested_shipment_service = 'Priority Mail';
//        if (stripos($order_detail->requested_shipment_service, 'Priority Mail') === 0) {
//            $shipping_carrier_service = ShippingCarrierService::where('name', 'Priority')->first()->id;
//        }
//        if (stripos($order_detail->requested_shipment_service, 'USPS First Class') === 0) {
//            $shipping_carrier_service = ShippingCarrierService::where('name', 'First Class')->first()->id;
//        }
//        Log::info('Shipping Carrier Service ID: ' . $shipping_carrier_service);
//        Log::info('Test Create Label executed successfully.');
    }
}
