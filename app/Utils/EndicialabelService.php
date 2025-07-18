<?php

namespace App\Utils;

use App\Constants\OrderOperationType;
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
            $address = $shippingDetail['toAddress']['name'] . ' ' . $shippingDetail['toAddress']['address1'] . ' ' . $shippingDetail['toAddress']['address2'] . ' ' . $shippingDetail['toAddress']['city'] . ' ' . $shippingDetail['toAddress']['state'] . ' ' . $shippingDetail['toAddress']['postalCode'] . ' ' . $shippingDetail['toAddress']['country'];
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

            $operation_type = $orderOperation->operation_type;
            if (OrderOperationType::isReturn($operation_type)) {
                $imagePath = storage_path('app/public/labels/' . $trackingNumber . '.jpg');
            }else{
                $imagePath = storage_path('app/public/labels/' . $trackingNumber . '.txt');
            }
            file_put_contents($imagePath, $imageData);

            if (OrderOperationType::isReturn($operation_type)) {

                if (isset($shippingDetail['RubberStamp3'])) {
                    $this->generateStampImage($imagePath, $shippingDetail['RubberStamp3']);
                }

            }else {

                if (isset($shippingDetail['RubberStamp3'])) {
                    $this->generateStampText($imagePath,$imageData, $shippingDetail['RubberStamp3']);
                }

                $imageUrl = asset('storage/labels/' . $trackingNumber . '.txt');
                Log::info('Label image saved to: ' . $imageUrl);

                DB::connection('aselna_printer_db')->table('PrintJobs')->insert([
                    'id' => (string)Str::uuid(),
                    'fileName' => $trackingNumber . '.txt',
                    'filePath' => $imageUrl,
                    'status' => 'pending',
                    'createdAt' => now(),
                    'updatedAt' => now(),
                    'fileSize' => 0,
                    'printerId' => $shippingDetail['printerId'] ?? null,
                    'systemId' => $shippingDetail['systemId'] ?? null,
                ]);
            }


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

    private function generateStampText($filePath,$fileContent, $text)
    {
        // get the text from filePAth and add text one second last line
        try {
            if ($fileContent === false) {
                throw new Exception('Failed to read file content');
            }

            $lines = explode("\n", trim($fileContent));
//            Log::info('Lines in file: ' . count($lines));
            if (count($lines) < 2) {
                throw new Exception('File does not contain enough lines to add stamp');
            }

            $lastLine = array_pop($lines);
            // Remove the last line from the array

            // Add the text to the second last line
            $lines[] = '^FO30,1016^FB507,2,3,L,0^A0N,30,30^FD';
            $lines[] = ' ' . $text;
            $lines[] = '^FS';

            $lines[] = $lastLine; // Add the last line back

            // Write the modified content back to the file
            file_put_contents($filePath, implode("\n", $lines));

//            Log::info('Added text directly to label image: ' . $filePath);
            return $filePath;
        } catch (Exception $e) {
            Log::error('Error editing label image: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateStampImage($imagePath, $text)
    {
        try {

            $mime = mime_content_type($imagePath);

            switch ($mime) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                case 'text/plain':
                    $image = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($imagePath);
                    break;
                default:
                    Log::error("Unsupported image type: $mime");
                    throw new Exception("Unsupported image type: $mime");
            }
            // Load the original image
            //$image = imagecreatefrompng($imagePath);
            if (!$image) {
                throw new ExcepgenerateStampImagetion('Failed to load image');
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Set up text properties
            $textColor = imagecolorallocate($image, 0, 0, 0); // Black text
            $fontSize = 20;
            $fontFile = storage_path('app/fonts/arial.ttf');
            $x = 10;
            $y = $height - 5;

            if (!file_exists($fontFile)) {
                Log::info('Custom font file not found, using default font.');
                imagestring($image, 5, $x, $y, $text, $textColor);
            } else {
                Log::info('Custom font file found, using default font.');
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
            }


            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($image, $imagePath);
                    break;
                case 'image/png':
                case 'text/plain':
                    imagepng($image, $imagePath);
                    break;
                case 'image/gif':
                    $image = imagegif($image, $imagePath);
                    break;
                default:
                    Log::error("Unsupported image type: $mime");
                    throw new Exception("Unsupported image type: $mime");
            }
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

//        $labelRequest->addChild('Test', 'YES');
        $labelRequest->addAttribute('ImageFormat', $shipmentDetails['labelFormat']);
        $labelRequest->addAttribute('LabelSize', $shipmentDetails['label_size']);
        $labelRequest->addAttribute('ImageResolution', $shipmentDetails['image_resolution'] ?? '203');
        $labelRequest->addAttribute('ImageRotation', $shipmentDetails['ImageRotation'] ?? 'Rotate180');
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
            if (isset($shipmentDetails['fromAddress']['address2']) && $shipmentDetails['fromAddress']['address2'] !== '') {
                $labelRequest->addChild('FromAddress2', $shipmentDetails['fromAddress']['address2']);
            }
            if (isset($shipmentDetails['fromAddress']['address3']) && $shipmentDetails['fromAddress']['address3'] !== '') {
                $labelRequest->addChild('FromAddress3', $shipmentDetails['fromAddress']['address3']);
            }
            $labelRequest->addChild('FromCity', $shipmentDetails['fromAddress']['city']);
            $labelRequest->addChild('FromState', $shipmentDetails['fromAddress']['state']);
            $labelRequest->addChild('FromPostalCode', $shipmentDetails['fromAddress']['postalCode']);
        }

        if ($shipmentDetails['toAddress']) {
            $labelRequest->addChild('ToCompany', $shipmentDetails['toAddress']['name']);
//            $labelRequest->addChild('', $shipmentDetails['toAddress']['company'] ?? '');
            $labelRequest->addChild('ToAddress1', $shipmentDetails['toAddress']['address1']);
            if (isset($shipmentDetails['toAddress']['address2']) && $shipmentDetails['toAddress']['address2'] !== '') {
                $labelRequest->addChild('ToAddress2', $shipmentDetails['toAddress']['address2']);
            }
            if (isset($shipmentDetails['toAddress']['address3']) && $shipmentDetails['toAddress']['address3'] !== '') {
                $labelRequest->addChild('ToAddress3', $shipmentDetails['toAddress']['address3']);
            }
            $labelRequest->addChild('ToCity', $shipmentDetails['toAddress']['city']);
            $labelRequest->addChild('ToState', $shipmentDetails['toAddress']['state']);
            $labelRequest->addChild('ToPostalCode', $shipmentDetails['toAddress']['postalCode']);
        }

        if (isset($shipmentDetails['returnAddress'])) {
//            $labelRequest->addChild('ReturnName', $shipmentDetails['returnAddress']['name']);
//            $labelRequest->addChild('ReturnCompany', $shipmentDetails['returnAddress']['company'] ?? '');
//            $labelRequest->addChild('ReturnAddress1', $shipmentDetails['returnAddress']['address1']);
//            $labelRequest->addChild('ReturnToCity', $shipmentDetails['returnAddress']['city']);
//            $labelRequest->addChild('ReturnToState', $shipmentDetails['returnAddress']['state']);
//            $labelRequest->addChild('ReturnToCountryCode', $shipmentDetails['returnAddress']['countryCode']);
//            $labelRequest->addChild('ReturnToPostalCode', $shipmentDetails['returnAddress']['postalCode']);
            $labelRequest->addChild('ReturnName', $shipmentDetails['fromAddress']['name']);
            $labelRequest->addChild('ReturnCompany', $shipmentDetails['fromAddress']['company'] ?? '');
            $labelRequest->addChild('ReturnAddress1', $shipmentDetails['fromAddress']['address1']);
            if (isset($shipmentDetails['fromAddress']['address2']) && $shipmentDetails['fromAddress']['address2'] !== '') {
                $labelRequest->addChild('ReturnAddress2', $shipmentDetails['fromAddress']['address2']);
            }
            if (isset($shipmentDetails['fromAddress']['address3']) && $shipmentDetails['fromAddress']['address3'] !== '') {
                $labelRequest->addChild('ReturnAddress3', $shipmentDetails['fromAddress']['address3']);
            }
            $labelRequest->addChild('ReturnToCity', $shipmentDetails['fromAddress']['city']);
            $labelRequest->addChild('ReturnToState', $shipmentDetails['fromAddress']['state']);
            $labelRequest->addChild('ReturnToPostalCode', $shipmentDetails['fromAddress']['postalCode']);
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

            $refund = (string)$xml->xpath('//ns:Refund')[0];
            $RequestID = (string)$xml->xpath('//ns:RequestID')[0];
            $RefundStatus = (string)$xml->xpath('//ns:RefundStatus')[0];
            $Message = (string)$xml->xpath('//ns:RefundStatusMessage')[0];
            Log::info('Endicia Label Response Parser: ' . json_encode($RequestID));
            Log::info('Endicia Label Response Parser: ' . json_encode($RefundStatus));
            Log::info('Endicia Label Response Parser: ' . json_encode($Message));
            $returnData = [
                'status' => $RefundStatus,
                'requestID' => $RequestID,
                'message' => $Message
            ];

            Log::info('Return Data: ' . json_encode($returnData));

            return $returnData;

        } catch (Exception $e) {
            // Handle SOAP request errors
            if ($e instanceof RequestException && $e->hasResponse()) {
                Log::error('SOAP Error Response: ' . $e->getResponse()->getBody()->getContents());
            }
//            throw new Exception('Error creating label: ' . $e->getMessage());

            Log::info('Return Data Error: ' . json_encode($e->getMessage()));
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
