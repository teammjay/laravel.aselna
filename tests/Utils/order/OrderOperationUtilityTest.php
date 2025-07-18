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
        $order = OrderOperation::findOrFail('4e846c8d-ee64-4f64-a11a-669a0c62cacc'); // Assuming an order with ID 1 exists

        $utility = new OrderOperationUtility();
        $utility->createLabel($order);

//        new ShipmentDocumentsCreator($order);
    }
}
