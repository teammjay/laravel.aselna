<?php

namespace App\Utils;

use App\Models\OrderOperation;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
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

        $this->client = new Client();
    }

    public function createLabel(array $request,$orderOperationId)
    {
        $xmlString = $this->buildXmlRequest($request);
        Log::info('Endicia Label Request XML: ' . $xmlString);

        try {
            $response = $this->client->post('https://LabelServer.Endicia.com/LabelService/EwsLabelService.asmx', [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '"www.envmgr.com/LabelService/GetPostageLabel"',
                ],
                'body' => $xmlString,
                'verify' => false,
            ]);

            $body = $response->getBody()->getContents();
            Log::info('Endicia Label Response XML: ' . $body);
            return $this->parseSoapResponse($body, $orderOperationId);
        } catch (Exception $e) {
            // Handle SOAP request errors
            if ($e->hasResponse()) {
                Log::error('SOAP Error Response: ' . $e->getResponse()->getBody()->getContents());
            }
            throw new Exception('Error creating label: ' . $e->getMessage());
        }
    }

    public function parseSoapResponse($response, $orderOperationId)
    {

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            return ['error' => 'Failed to parse XML'];
        }

        $xml->registerXPathNamespace('ns', 'www.envmgr.com/LabelService');

        $base64Image = (string)$xml->xpath('//ns:Base64LabelImage')[0];
        $trackingNumber = (string)$xml->xpath('//ns:TrackingNumber')[0];

        Log::info('Tracking Number: ' . $trackingNumber);
        $orderOperation = OrderOperation::findOrFail($orderOperationId);
        $orderOperation->tracking_number = $trackingNumber;
        $orderOperation->save();

        try {
            $imageData = base64_decode($base64Image);
            file_put_contents(storage_path('app/public/labels/' . $trackingNumber . '.png'), $imageData);

        } catch (Exception $e) {
            Log::error('Error saving label image: ' . $e->getMessage());
            return ['error' => 'Failed to save label image'];
        }

        return [
            'trackingNumber' => $trackingNumber,
            'labelType' => (string)$xml->xpath('//ns:LabelType')[0] ?? null,
            'error' => null,
        ];
    }

    private function buildXmlRequest(array $shipmentDetails)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $xml->addChild('soap:Body', null, 'http://schemas.xmlsoap.org/soap/envelope/');
        $request = $body->addChild('GetPostageLabel', null, 'www.envmgr.com/LabelService');
        $labelRequest = $request->addChild('LabelRequest');

        $labelRequest->addChild('Test', 'YES');
//        $labelRequest->addAttribute('LabelType', $shipmentDetails['']);

        $labelRequest->addChild('RequesterID', $shipmentDetails['requesterId']);
        $labelRequest->addChild('AccountID', $shipmentDetails['accountId']);
        $labelRequest->addChild('PassPhrase', $shipmentDetails['passPhrase']);

        $labelRequest->addChild('MailClass', $shipmentDetails['MailClass']);
        $labelRequest->addChild('MailpieceShape', $shipmentDetails['MailpieceShape'] ?? 'Package');
        $labelRequest->addChild('WeightOz', $shipmentDetails['weight']['value']);

//        $labelRequest->addChild('MailpieceShape','');

        $labelRequest->addChild('PartnerTransactionID', $shipmentDetails['transactionId']);

        $labelRequest->addChild('Machinable', $shipmentDetails['non_machinable'] ? 'true' : 'false');

        if ($shipmentDetails['dimensions']) {
            $dimensions = $labelRequest->addChild('MailpieceDimensions');
            $dimensions->addChild('Length', $shipmentDetails['dimensions']['length']);
            $dimensions->addChild('Width', $shipmentDetails['dimensions']['width']);
            $dimensions->addChild('Height', $shipmentDetails['dimensions']['height']);
        }

        if ($shipmentDetails['fromAddress']) {
            $labelRequest->addChild('FromName', $shipmentDetails['fromAddress']['name']);
            $labelRequest->addChild('FromCompany', $shipmentDetails['fromAddress']['company'] ?? '');
            $labelRequest->addChild('FromAddress1', $shipmentDetails['fromAddress']['address1']);
            $labelRequest->addChild('FromCity', $shipmentDetails['fromAddress']['city']);
            $labelRequest->addChild('FromState', $shipmentDetails['fromAddress']['state']);
            $labelRequest->addChild('FromPostalCode', $shipmentDetails['fromAddress']['postalCode']);
        }

        if ($shipmentDetails['toAddress']) {
            $labelRequest->addChild('ToName', $shipmentDetails['toAddress']['name']);
            $labelRequest->addChild('ToCompany', $shipmentDetails['toAddress']['company'] ?? '');
            $labelRequest->addChild('ToAddress1', $shipmentDetails['toAddress']['address1']);
            $labelRequest->addChild('ToCity', $shipmentDetails['toAddress']['city']);
            $labelRequest->addChild('ToState', $shipmentDetails['toAddress']['state']);
            $labelRequest->addChild('ToPostalCode', $shipmentDetails['toAddress']['postalCode']);
        }

        if (isset($shipmentDetails['returnAddress'])) {
//            $labelRequest->addChild('ReturnName', $shipmentDetails['returnAddress']['name']);
//            $labelRequest->addChild('ReturnCompany', $shipmentDetails['returnAddress']['company'] ?? '');
            $labelRequest->addChild('ReturnAddress1', $shipmentDetails['returnAddress']['address1']);
            $labelRequest->addChild('ReturnToCity', $shipmentDetails['returnAddress']['city']);
            $labelRequest->addChild('ReturnToState', $shipmentDetails['returnAddress']['state']);
//            $labelRequest->addChild('ReturnToCountryCode', $shipmentDetails['returnAddress']['countryCode']);
            $labelRequest->addChild('ReturnToPostalCode', $shipmentDetails['returnAddress']['postalCode']);
        }

        if (isset($shipmentDetails['RubberStamp1'])) {
            $labelRequest->addChild('RubberStamp1', $shipmentDetails['RubberStamp1']);
        }
        if (isset($shipmentDetails['RubberStamp2'])) {
            $labelRequest->addChild('RubberStamp2', $shipmentDetails['RubberStamp2']);
        }
        if (isset($shipmentDetails['RubberStamp3'])) {
            $labelRequest->addChild('RubberStamp3', $shipmentDetails['RubberStamp3']);
        }

        return $xml->asXML();
    }
}
