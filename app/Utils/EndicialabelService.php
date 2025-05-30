<?php

namespace App\Utils;

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

    public function createLabel(array $request)
    {
        $xmlString = $this->buildXmlRequest($request);
        Log::info('Endicia Label Request XML: ' . $xmlString);

        try {
            $response = $this->client->post('https://elstestserver.endicia.com/LabelService/EwsLabelService.asmx', [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => 'http://www.envmgr.com/LabelService/GetPostageLabel',
                ],
                'body' => $xmlString,
                'verify' => false,
            ]);

            $body = $response->getBody()->getContents();
            return $this->parseSoapResponse($body);
        } catch (Exception $e) {
            // Handle SOAP request errors
            throw new Exception('Error creating label: ' . $e->getMessage());
        }
    }

    private function parseSoapResponse($response)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            return ['error' => 'Failed to parse XML'];
        }

        return json_decode(json_encode($xml), true);
    }

    private function buildXmlRequest(array $shipmentDetails)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $xml->addChild('soap:Body', null, 'http://schemas.xmlsoap.org/soap/envelope/');
        $request = $body->addChild('GetPostageLabel', null, 'www.envmgr.com/LabelService');
        $labelRequest = $request->addChild('LabelRequest');

        $labelRequest->addChild('Test', 'YES');
//        $labelRequest->addAttribute('LabelType', $shipmentDetails['']);

        $labelRequest->addChild('RequesterID', 'lxxx');
        $labelRequest->addChild('AccountID', '2533763');
        $labelRequest->addChild('PassPhrase', 'ajshd823rha!d33');

        $labelRequest->addChild('MailClass', $shipmentDetails['MailClass']);
        $labelRequest->addChild('WeightOz', $shipmentDetails['weight']['value']);

        $labelRequest->addChild('MailpieceShape','Package');

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

        return $xml->asXML();
    }
}
