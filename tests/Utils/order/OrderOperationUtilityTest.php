<?php

namespace Tests\Utils\order;

use App\Models\OrderOperation;
use App\Models\ShippingCarrierService;
use App\Utils\EndicialabelService;
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
        $shipmentDetailsData = '{
  "store_id": "6c7009ce-eac1-a995-a986-27d5df0103ac",
  "printerId": "3d8bf7ee-a516-47ec-89e3-b9d2d4cdf478",
  "systemId": "3f9e76a70ffb549cc023d3025a0a4ecec34f8d13b41c3b2fc1edf16bd6ee0c3b",
  "fromAddress": {
    "name": null,
    "address1": "222 Universal Dr",
    "address2": "Bldg 3",
    "address3": "",
    "city": "North Haven",
    "state": "CT",
    "postalCode": "06473",
    "country": "US",
    "phone": null,
    "company": "Fulfillment Center"
  },
  "toAddress": {
    "name": "P&M Noonan",
    "address1": "70 Summer Street",
    "address2": "",
    "address3": "",
    "city": "Oakland",
    "state": "Maine",
    "postalCode": "04963",
    "country": "United States",
    "phone": null,
    "comapany": ""
  },
  "dimensions": {
    "length": 20,
    "width": 10,
    "height": 5,
    "unit": 1
  },
  "MailClass": "GroundAdvantage",
  "MailpieceShape": "Parcel",
  "transactionId": "6670515503298",
  "weight": {
    "value": 8,
    "unit": "Ounces"
  },
  "non_machinable": 0,
  "packageType": "Parcel",
  "label_size": "4x5",
  "accountId": "1257538",
  "passPhrase": "Ag7ycm1274r2!a8hser2m",
  "requesterId": "Iweb",
  "account_info": "6af8e570-2505-ebab-a60f-3a7f0a5e8da6",
  "ImageRotation": "Rotate180",
  "labelFormat": "ZPLII",
  "returnAddress": {
    "address1": "590 Hatton Ln",
    "address2": "",
    "city": "Junction City",
    "state": "OR",
    "postalCode": "97448"
  },
  "RubberStamp1": "OdeWeb 187158",
  "RubberStamp3": "(F58 x 1)"
}';

        $shipmentDetails = json_decode($shipmentDetailsData, true);

        $endiciaLabelService = new EndicialabelService();
        $xmlString = $endiciaLabelService->buildXmlRequest($shipmentDetails, 1);
        Log::info('Endicia Label Request XML: ' . $xmlString);
    }
}
