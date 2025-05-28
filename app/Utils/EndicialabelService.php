<?php

namespace App\Utils;

use SoapClient;
use Exception;

class EndicialabelService
{
    protected $client;
    protected $accountId;
    protected $passphrase;

    public function __construct()
    {
        $this->accountId = env('ENDICIA_ACCOUNT_ID');
        $this->passphrase = env('ENDICIA_PASSPHRASE');

        $wsdl = 'https://elstestserver.endicia.com/LabelService/EwsLabelService.asmx?wsdl';

        try {
            $this->client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
            ]);
        } catch (Exception $e) {
            // Handle SOAP client initialization errors
            throw new Exception('Failed to initialize SOAP client: ' . $e->getMessage());
        }
    }

    public function createLabel(array $request)
    {
//        $request = [
//            'RequesterID' => $this->accountId,
//            'AccountID' => $this->accountId,
//            'PassPhrase' => $this->passphrase,
//            'MailClass' => $shipmentDetails['MailClass'],
//            'WeightOz' => $shipmentDetails['WeightOz'],
//            'MailpieceShape' => $shipmentDetails['MailpieceShape'],
//            'Services' => $shipmentDetails['Services'] ?? '',
//            'Value' => $shipmentDetails['Value'] ?? 0,
//            'Description' => $shipmentDetails['Description'] ?? '',
//            'PartnerCustomerID' => $shipmentDetails['PartnerCustomerID'] ?? '',
//            'PartnerTransactionID' => $shipmentDetails['PartnerTransactionID'] ?? '',
//            'ToName' => $shipmentDetails['ToName'],
//            'ToCompany' => $shipmentDetails['ToCompany'] ?? '',
//            'ToAddress1' => $shipmentDetails['ToAddress1'],
//            'ToAddress2' => $shipmentDetails['ToAddress2'] ?? '',
//            'ToCity' => $shipmentDetails['ToCity'],
//            'ToState' => $shipmentDetails['ToState'],
//            'ToPostalCode' => $shipmentDetails['ToPostalCode'],
//            'ToCountry' => $shipmentDetails['ToCountry'],
//            'ToPhone' => $shipmentDetails['ToPhone'] ?? '',
//            'FromName' => $shipmentDetails['FromName'],
//            'FromCompany' => $shipmentDetails['FromCompany'] ?? '',
//            'ReturnAddress1' => $shipmentDetails['ReturnAddress1'],
//            'ReturnAddress2' => $shipmentDetails['ReturnAddress2'] ?? '',
//            'FromCity' => $shipmentDetails['FromCity'],
//            'FromState' => $shipmentDetails['FromState'],
//            'FromPostalCode' => $shipmentDetails['FromPostalCode'],
//            'FromCountry' => $shipmentDetails['FromCountry'],
//            'FromPhone' => $shipmentDetails['FromPhone'] ?? '',
//            'PrintLayout' => $shipmentDetails['PrintLayout'] ?? 'Normal',
//            'ImageFormat' => $shipmentDetails['ImageFormat'] ?? 'PDF',
//            'ImageResolution' => $shipmentDetails['ImageResolution'] ?? 300,
//            'LabelType' => $shipmentDetails['LabelType'] ?? 'Default',
//            'Test' => $shipmentDetails['Test'] ?? false,
//        ];
        if (env('APP_ENV') === 'local') {
            return;
        } else {
            $request['Test'] = false; // Disable test mode for production
        }

        $request['RequesterID'] = $this->accountId;
        $request['AccountID'] = $this->accountId;
        $request['PassPhrase'] = $this->passphrase;


        try {
            $response = $this->client->GetPostageLabel(['LabelRequest' => $request]);

            if (isset($response->LabelRequestResponse->Base64LabelImage)) {
                return base64_decode($response->LabelRequestResponse->Base64LabelImage);
            } else {
                throw new Exception('Label image not found in the response.');
            }
        } catch (Exception $e) {
            // Handle SOAP request errors
            throw new Exception('Error creating label: ' . $e->getMessage());
        }
    }
}
