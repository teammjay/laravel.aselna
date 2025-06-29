<?php

namespace App\Utils;

use App\Models\AccountTransaction;
use App\Models\OrderOperation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function createLabel(array $request, $orderOperationId)
    {
        Log::info('Creating Endicia label with request: ' . json_encode($request));
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
            return $this->parseSoapResponse($body, $request, $orderOperationId);
        } catch (Exception $e) {
            // Handle SOAP request errors
            if ($e instanceof RequestException && $e->hasResponse()) {
                Log::error('SOAP Error Response: ' . $e->getResponse()->getBody()->getContents());
            }
            throw new Exception('Error creating label: ' . $e->getMessage());
        }
    }


    public function parseSoapResponse($response, $shippingDetail, $orderOperationId)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            return ['error' => 'Failed to parse XML'];
        }

        $xml->registerXPathNamespace('ns', 'www.envmgr.com/LabelService');

        $base64Image = (string)$xml->xpath('//ns:Base64LabelImage')[0];
        $trackingNumber = (string)$xml->xpath('//ns:TrackingNumber')[0];
        $transactionCost = (string)$xml->xpath('//ns:FinalPostage')[0];
        $transactionID = (string)$xml->xpath('//ns:TransactionID')[0];
        $transactionDate = (string)$xml->xpath('//ns:TransactionDateTime')[0];

        Log::info('Tracking Number: ' . $trackingNumber);
        $orderOperation = OrderOperation::findOrFail($orderOperationId);
        $orderOperation->tracking_number = $trackingNumber;
        $orderOperation->save();

        /* create transaction entry*/
        if (env('APP_ENV') !== 'local') {
            $address = $shippingDetail['fromAddress']['name'] . ' ' . $shippingDetail['fromAddress']['address1'] . ' ' . $shippingDetail['fromAddress']['address2'] . ' ' . $shippingDetail['fromAddress']['city'] . ' ' . $shippingDetail['fromAddress']['state'] . ' ' . $shippingDetail['fromAddress']['postalCode'] . ' ' . $shippingDetail['fromAddress']['country'];
            $transactionData = [
                'shipping_account_id' => $shippingDetail['account_info'],
                'tracking_number' => $trackingNumber,
                'transaction_date' => date('Y-m-d H:i:s', strtotime($transactionDate)),
                'transaction_id' => $transactionID,
                'amount' => $transactionCost,
                'address' => $address,
                'canceled' => 0,
            ];

            AccountTransaction::updateOrCreate([
                'tracking_number' => $trackingNumber,
                'transaction_id' => $transactionID,
            ], $transactionData);
        }

        try {
            $imageData = base64_decode($base64Image);
            $imagePath = storage_path('app/public/labels/' . $trackingNumber . '.png');
            file_put_contents($imagePath, $imageData);

            if (isset($shippingDetail['RubberStamp3'])) {
                $this->generateStampImage($imagePath, $shippingDetail['RubberStamp3']);
            }

            $imageUrl = asset('storage/labels/' . $trackingNumber . '.png');
            Log::info('Label image saved to: ' . $imageUrl);

            DB::connection('aselna_printer_db')->table('PrintJobs')->insert([
                'id' => (string)Str::uuid(),
                'fileName' => $trackingNumber . '.png',
                'filePath' => $imageUrl,
                'status' => 'pending',
                'createdAt' => now(),
                'updatedAt' => now(),
                'fileSize' => 0,
                'printerId' => $shippingDetail['printerId'] ?? null,
                'systemId' => $shippingDetail['systemId'] ?? null,
            ]);


        } catch (Exception $e) {
            Log::error('Error saving label image: ' . $e->getMessage());
            return ['error' => 'Failed to save label image'];
        }

        Log::info('Label image saved to: ' . json_encode($xml->xpath('//ns:LabelType')));

        return [
            'trackingNumber' => $trackingNumber,
            // 'labelType' => (string)$xml->xpath('//ns:LabelType')[0] ?? null,
            'error' => null,
        ];
    }

    private function generateStampImage($imagePath, $text)
    {
        try {
            // Load the original image
            $image = imagecreatefrompng($imagePath);
            if (!$image) {
                throw new Exception('Failed to load image');
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Set up text properties
            $textColor = imagecolorallocate($image, 0, 0, 0); // Black text
            $fontSize = 20;
            $fontFile = storage_path('app/fonts/arial.ttf');
            $x = 50;
            $y = $height - 90;

            if (!file_exists($fontFile)) {
                Log::info('Custom font file not found, using default font.');
                imagestring($image, 5, $x, $y, $text, $textColor);
            } else {
                Log::info('Custom font file found, using default font.');

                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
            }

            imagepng($image, $imagePath);
            imagedestroy($image);

            Log::info('Added text directly to label image: ' . $imagePath);
            return $imagePath;
        } catch (Exception $e) {
            Log::error('Error editing label image: ' . $e->getMessage());
            throw $e;
        }
    }

    private
    function buildXmlRequest(array $shipmentDetails)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $xml->addChild('soap:Body', null, 'http://schemas.xmlsoap.org/soap/envelope/');
        $request = $body->addChild('GetPostageLabel', null, 'www.envmgr.com/LabelService');
        $labelRequest = $request->addChild('LabelRequest');

        $labelRequest->addChild('Test', 'YES');
        $labelRequest->addChild('ImageFormat', $shipmentDetails['labelFormat']);
        $labelRequest->addChild('LabelSize', $shipmentDetails['label_size']);
        $labelRequest->addChild('ImageResolution', $shipmentDetails['image_resolution'] ?? '203');
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
//        if (isset($shipmentDetails['RubberStamp3'])) {
//            $labelRequest->addChild('RubberStamp3', $shipmentDetails['RubberStamp3']);
//        }

        return $xml->asXML();
    }

    public function cancelLabel(array $request)
    {
        $xmlString = $this->buildCancelXmlRequest($request);
        Log::info('Endicia Cancel Label Request XML: ' . $xmlString);

        try {
            $response = $this->client->post('https://LabelServer.Endicia.com/LabelService/EwsLabelService.asmx', [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '"www.envmgr.com/LabelService/GetRefund"',
                ],
                'body' => $xmlString,
                'verify' => false,
            ]);

            $body = $response->getBody()->getContents();
            Log::info('Endicia Label Response XML: ' . $body);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);

            if ($xml === false) {
                return ['error' => 'Failed to parse XML'];
            }

            $xml->registerXPathNamespace('ns', 'www.envmgr.com/LabelService');

            $refunds = (string)$xml->xpath('//ns:Refund');
            $returnData = [];
            foreach ($refunds as $refund) {
                $transactionId = (string)$refund['TransactionId'];
                $PicNumber = (string)$refund['PicNumber'];
                $status = (string)$refund->RefundStatus;
                $message = (string)$refund->RefundStatusMessage;

                $returnData[] = [
                    'transactionId' => $transactionId,
                    'PicNumber' => $PicNumber,
                    'status' => $status,
                    'message' => $message,
                ];
            }

            return $returnData;

        } catch (Exception $e) {
            // Handle SOAP request errors
            if ($e instanceof RequestException && $e->hasResponse()) {
                Log::error('SOAP Error Response: ' . $e->getResponse()->getBody()->getContents());
            }
//            throw new Exception('Error creating label: ' . $e->getMessage());
        }
    }

    private function buildCancelXmlRequest(array $labelDetails)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"/>');
        $body = $xml->addChild('soap:Body', null, 'http://schemas.xmlsoap.org/soap/envelope/');
        $request = $body->addChild('GetRefund', null, 'www.envmgr.com/LabelService');
        $labelRequest = $request->addChild('RefundRequest');

        $labelRequest->addChild('RequesterID', $labelDetails['requesterId']);
        $labelRequest->addChild('RequestID', $labelDetails['requestId']);
        $CertifiedIntermediary = $labelRequest->addChild('CertifiedIntermediary');

        $CertifiedIntermediary->addChild('AccountID', $labelDetails['accountId']);
        $CertifiedIntermediary->addChild('PassPhrase', $labelDetails['passPhrase']);

        $picNumbers = $labelRequest->addChild('PicNumbers');
        $picNumbers->addChild('PicNumber', $labelDetails['pic_no']);

        //$transactionIDs = $labelRequest->addChild('TransactionIds');
        //$transactionIDs->addChild('TransactionId', $labelDetails['transaction_id']);

        return $xml->asXML();

    }
}
