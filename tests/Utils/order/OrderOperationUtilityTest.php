<?php

namespace Tests\Utils\order;

use App\Models\OrderOperation;
use App\Utils\order\OrderOperationUtility;
use App\Utils\shipment\ShipmentDocumentsCreator;
use Tests\TestCase;
class OrderOperationUtilityTest extends TestCase
{
    public function testCreateLabel()
    {
        $order = OrderOperation::findOrFail('3a0c272b-2194-4b79-a74f-b0462c8353f3'); // Assuming an order with ID 1 exists

//        $utility = new OrderOperationUtility();
//        $utility->createLabel($order);

//        new ShipmentDocumentsCreator($order);
    }
}
