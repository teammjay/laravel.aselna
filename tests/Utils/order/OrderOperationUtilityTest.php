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
        $order = OrderOperation::findOrFail('009d877e-4085-453b-9964-20586cc1ec52'); // Assuming an order with ID 1 exists

        $utility = new OrderOperationUtility();
        $utility->createLabel($order);

//        new ShipmentDocumentsCreator($order);
    }
}
