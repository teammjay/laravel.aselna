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
        $xmlString = $this->buildXmlRequest($request);
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

    private function buildXmlRequest(array $request)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $xml->addChild('soap:Body', null, 'http://schemas.xmlsoap.org/soap/envelope/');
        $request = $body->addChild('GetPostageLabel', null, 'www.envmgr.com/LabelService');
        $labelRequest = $request->addChild('LabelRequest');

        $labelRequest->addAttribute('Test', 'YES');
        $labelRequest->addAttribute('LabelType', 'Default');

        $labelRequest->addChild('RequesterID', 'lxxx');
        $labelRequest->addChild('AccountID', '2533763');
        $labelRequest->addChild('PassPhrase', 'ajshd823rha!d33');

        $dimensions = $labelRequest->addChild('MailpieceDimensions');
        $dimensions->addChild('Length', '1');
        $dimensions->addChild('Width', '1');
        $dimensions->addChild('Height', '1');

        return $xml->asXML();
    }
}
