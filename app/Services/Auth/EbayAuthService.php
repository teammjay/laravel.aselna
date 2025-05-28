<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Constants\FeedTypes;

class EbayAuthService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $scope;
    private $redirectUri;
    private $ebay_refresh_token;
    private $accessToken;
    private $expireTime;

    public function __construct($clientId, $clientSecret,$redirectUri,$refresh_token='')
    {
//        $this->client = new Client(['base_uri' => 'https://api.sandbox.ebay.com/']);
        $this->client = new Client(['base_uri' => 'https://api.ebay.com/']);
        if (env('APP_ENV') == 'local') {
            $clientId = env('EBAY_CLIENT_ID');
            $clientSecret = env('EBAY_CLIENT_SECRET');
            $redirectUri = env('EBAY_REDIRECT_URI');
        }
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        if($refresh_token != '' || $refresh_token != NULL) {
            $this->ebay_refresh_token = $refresh_token;
        }

        $this->scope = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/buy.order.readonly https://api.ebay.com/oauth/api_scope/buy.guest.order https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.marketplace.insights.readonly https://api.ebay.com/oauth/api_scope/commerce.catalog.readonly https://api.ebay.com/oauth/api_scope/buy.shopping.cart https://api.ebay.com/oauth/api_scope/buy.offer.auction https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.email.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.phone.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.address.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.name.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.status.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/sell.item.draft https://api.ebay.com/oauth/api_scope/sell.item https://api.ebay.com/oauth/api_scope/sell.reputation https://api.ebay.com/oauth/api_scope/sell.reputation.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly https://api.ebay.com/oauth/api_scope/sell.stores https://api.ebay.com/oauth/api_scope/sell.stores.readonly';

    }

    // Exchange Authorization Code for Access Token
    public function getAccessToken($authorizationCode)
    {
        try {
            $response = $this->client->post("identity/v1/oauth2/token", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $authorizationCode,
                    'redirect_uri' => $this->redirectUri,
                ]
            ]);



            $data = json_decode($response->getBody(), true);

            Log::info('Token Created ' . $response->getBody());

            return $data;

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    // Refresh eBay Access Token
    private function refreshToken()
    {
        try {

            if(isset($this->accessToken) && $this->expireTime > time()) {
                Log::info('Exist Token ' . $this->accessToken);
                return $this->accessToken;
            }

            $response = $this->client->post("identity/v1/oauth2/token", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->ebay_refresh_token
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            Log::info('Token Refresh ' . $response->getBody());

            if (isset($data['access_token'])) {
                $this->accessToken = $data['access_token'];
                $this->expireTime = time() + $data['expires_in'];
                return $data['access_token'];
            }

            return response()->json(['error' => 'Failed to refresh token'], 400);
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
            /*return response()->json(['error' => $e->getMessage()], 400);*/
        }
    }

   /* // Redirect User to eBay Authorization Page
    public function redirectToEbay()
    {
        $scope = urlencode('https://api.ebay.com/oauth/api_scope/sell.inventory');
        $authUrl = "https://auth.sandbox.ebay.com/oauth2/authorize?client_id={$this->clientId}&redirect_uri={$this->redirectUri}&response_type=code&scope={$this->scope}";
        return redirect()->away($authUrl);
    }

    // Handle eBay Callback
    public function handleCallback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json(['error' => 'Authorization code not found'], 400);
        }

        $authorizationCode = $request->get('code');
        $tokenData = $this->getAccessToken($authorizationCode);


        if (isset($tokenData['access_token'])) {

            $this->accessToken = $tokenData['access_token'];
            $this->ebay_refresh_token = $tokenData['refresh_token'];
            //$this->storeAccessToken(Auth::user()->id, $tokenData);
            return redirect('/stores');
        }

        return response()->json(['error' => 'Failed to get access token'], 400);
    }*/

    public function createTask($version,$listingInfo) {

        try {
            $accessToken = $this->refreshToken();
            Log::info('Access Token ' . $accessToken);

            $feedType = $this->getfeedType($listingInfo);
            Log::info('Feed Type ' . $feedType);

            $response = $this->client->post('sell/feed/v1/task', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
                ],
                'json' => [
                    'schemaVersion' => $version,
                    'feedType' => $feedType
                ],
            ]);
            Log::info('Task Created ' . $response->getBody());

            $headerValue = $response->getHeader('location');

            if(isset($headerValue[0]) && $headerValue[0] !=''){
                $split_arr = explode('/',$headerValue[0]);
                $taskId = end($split_arr);
                Log::info('Task Created ' . $taskId);
                return $taskId;
            }

        } catch (RequestException $e) {
            Log::info('Task Created Error ' . $e->getMessage());
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }
    }

    public function getResultFeed(string $taskId, string $listingId) {

        $savePath = public_path('result-feeds/ebay/'.$listingId.'.zip');
        try {
            $accessToken = $this->refreshToken();
            $response = $this->client->get('sell/feed/v1/task/' . $taskId . '/download_result_file', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/octet-stream',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
                ],
                'sink' => $savePath,
            ]);

            $errorResponse = json_decode($response->getBody()->getContents(), true);

            Log::info('Feed Response: ' . json_encode($errorResponse));

            $headerValue = $response->getHeaders();

            Log::info('Feed Status: ' . json_encode($headerValue));

            if (file_exists($savePath)) {
                response()->download($savePath);
                return 'result-feeds/ebay/'.$listingId.'.zip';
            }

            return $response->getStatusCode() === 200 ? 'result-feeds/ebay/'.$listingId.'.zip' : false;

        }catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }

    }

    public function downloadInputFeed(string $taskId, string $listingId) {

        $savePath = public_path('input-feeds/ebay/'.$listingId.'.xml');
        try {
            $accessToken = $this->refreshToken();
            $response = $this->client->get('sell/feed/v1/task/' . $taskId . '/download_input_file', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/octet-stream',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
                ],
                'sink' => $savePath,
            ]);

            $headerValue = $response->getHeaders();

            Log::info('Feed Status: ' . json_encode($headerValue));

            return $response->getStatusCode() === 200 ? $savePath : false;

        }catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
                Log::error('Error: ' . $e->getMessage());
                return null;
            }
        }

    }

    public function getTaskDetails(string $taskId)
    {
        try {
            $accessToken = $this->refreshToken();
            $response = $this->client->get("sell/feed/v1/task/".$taskId, [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            Log::info('Task Detail ' . $response->getBody());

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }
    }

    public function uploadImageToEPS($imageUrl, $imageName)
    {
        $accessToken = $this->refreshToken();

        $headers = [
            'X-EBAY-API-COMPATIBILITY-LEVEL' => '1147',
            'X-EBAY-API-CALL-NAME'           => 'UploadSiteHostedPictures',
            'X-EBAY-API-SITEID'              => '0',
            'Content-Type'                   => 'text/xml',
            'X-EBAY-API-IAF-TOKEN'           => $accessToken,
        ];

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?>
<UploadSiteHostedPicturesRequest xmlns="urn:ebay:apis:eBLBaseComponents"></UploadSiteHostedPicturesRequest>');

        $xml->addChild('PictureName',$imageName);
        $xml->addChild('ExternalPictureURL',$imageUrl);
        $xml->addChild('PictureSet','Supersize');
        $xmlContent = $xml->asXML();

        $response = $this->client->post('ws/api.dll', [
            'headers' => $headers,
            'body'    => $xmlContent,
        ]);

        $body = $response->getBody()->getContents();
        Log::info('UploadSiteHostedPictures Response ' . $response->getBody());
        $xml = simplexml_load_string($body);

        if (isset($xml->SiteHostedPictureDetails->FullURL)) {

            $imageUrl = (string)$xml->SiteHostedPictureDetails->FullURL;
            $maxSize = 0;

            foreach ($xml->SiteHostedPictureDetails->PictureSetMember as $member) {
                $size = (int) $member->PictureHeight;
                if ($size > $maxSize) {
                    $maxSize = $size;
                    $imageUrl = (string) $member->MemberURL;
                }
            }
            return $imageUrl;
        }

        return null;
    }

    public function uploadFile(string $taskId, string $fileName, string $filePath)
    {
        try {
            $accessToken = $this->refreshToken();

            $response = $this->client->post("sell/feed/v1/task/".$taskId."/upload_file", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'multipart' => [
                    [
                        'name' => 'fileName',
                        'contents' => $fileName,
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => $fileName,
                    ],
                ],
            ]);

            Log::info('Upload File Detail ' . $response->getBody());
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }
    }

    public function getOrders($sync_start_date,$cursor = null)
    {
        $accessToken = $this->refreshToken();
        try {
            $queryData = [];

            if (isset($cursor)) {
                parse_str(parse_url($cursor, PHP_URL_QUERY), $queryData);
            } else {
                $startDate = $sync_start_date;
                $endDate = gmdate('Y-m-d\TH:i:s\Z', strtotime(now()));

                $queryData = [
                    'filter'=> "creationdate:[$startDate..$endDate]",
                    'limit' => 10, // Fetch max 100 orders
                ];
            }

            Log::info('Query Data: ' . json_encode($queryData));

            $response = $this->client->get('/sell/fulfillment/v1/order', [
                'verify' => false,
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type' => 'application/json',
                ],
                'query' => $queryData,
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }
    }

    protected function handleApiErrors(array $errorResponse)
    {
        if (isset($errorResponse['errors'])) {
            foreach ($errorResponse['errors'] as $error) {
                Log::error("eBay API Error: [{$error['errorId']}] {$error['message']}");

                // Optional: Map error codes to custom exceptions
                switch ($error['errorId']) {
                    case 11000:
                        $message = 'Custom error: Duplicate request';
                        /*throw new \Exception('Custom error: Duplicate request');*/
                    case 12345:
                        $message = 'Custom error: Missing required parameter';
                        //throw new \Exception('Custom error: Missing required parameter');
                    case 160001:
                        $message = 'There was a problem with an eBay internal system or process. Contact eBay Developer Technical Support for assistance.';
                        //throw new \Exception('There was a problem with an eBay internal system or process. Contact eBay Developer Technical Support for assistance.');
                    case 160002:
                        $message = 'he authentication scope {scope} is incorrect for feedType. Refer to documentation for details about the scopes.';
                        //throw new \Exception('The authentication scope {scope} is incorrect for feedType. Refer to documentation for details about the scopes.');
                    case 160003:
                        $message = 'Task {taskID} does not exist.';
                        //throw new \Exception('Task {taskID} does not exist.');
                    case 160017:
                        $message = 'The \'feedType\' is missing or invalid.';
                        //throw new \Exception('The \'feedType\' is missing or invalid.');
                    case 160018:
                        $message = 'The \'schemaVersion\' is invalid.';
                        //throw new \Exception('The \'schemaVersion\' is invalid.');
                    case 160020:
                        $message = 'The format of the file to be uploaded is invalid. {additionalInfo}';
                        //throw new \Exception('The format of the file to be uploaded is invalid. {additionalInfo}');
                    case 160022:
                        $message = 'You are not allowed to access this resource. Contact eBay Developer Technical Support for assistance.';
                        //throw new \Exception('You are not allowed to access this resource. Contact eBay Developer Technical Support for assistance.');
                    case 160023:
                        $message = 'Task {taskID} cannot upload a file.';
                        //throw new \Exception('Task {taskID} cannot upload a file.');
                    case 160024:
                        $message = 'You have reached the maximum number of feed tasks that can be queued or processed concurrently. Wait for current tasks to complete before adding tasks. For more information, see the documentation for this API.';
                        //throw new \Exception('You have reached the maximum number of feed tasks that can be queued or processed concurrently. Wait for current tasks to complete before adding tasks. For more information, see the documentation for this API.');
                    case 160025:
                        $message = 'You have exceeded the maximum number of records or tasks that can be created or processed in the period (hourly or daily). Wait until the present period ends before trying again. Please reference the API documentation for more information.';
                        //throw new \Exception('You have exceeded the maximum number of records or tasks that can be created or processed in the period (hourly or daily). Wait until the present period ends before trying again. Please reference the API documentation for more information.');
                    case 160026:
                        $message = 'This task has expired and you cannot use it to upload a file. You must upload a file within a hour of creating the task.';
                        //throw new \Exception('This task has expired and you cannot use it to upload a file. You must upload a file within a hour of creating the task.');
                    case 160027:
                        $message = 'The X-EBAY-C-MARKETPLACE-ID header is missing or invalid.';
                        //throw new \Exception('The X-EBAY-C-MARKETPLACE-ID header is missing or invalid.');
                    case 160028:
                        $message = 'The file you tried to upload is too large. Please try using a smaller file. For more information, see the documentation for this API.';
                        //throw new \Exception('The file you tried to upload is too large. Please try using a smaller file. For more information, see the documentation for this API.');
                    case 160030:
                        $message = 'This resource is not applicable for the feed type {feedTypeValue}. For more information, see the documentation for this API.';
                       /* throw new \Exception('This resource is not applicable for the feed type {feedTypeValue}. For more information, see the documentation for this API.');*/
                    case 160100:
                        $message = 'Payload not found. Make sure your file is uploaded inside the Request Body under the key name "file".';
                        /*throw new \Exception('Payload not found. Make sure your file is uploaded inside the Request Body under the key name "file".');*/
                    default:
                        $message = $error['message'];
                        /*throw new \Exception("eBay API Error: {$error['message']}");*/
                }

                Log::error("eBay API Error: [{$error['errorId']}] {$message}");
                return null;
            }
        } else {
            Log::error('Error: Unexpected API error structure' );
            return null;
            //throw new \Exception('Unexpected API error structure');
        }
    }

    public function getfeedType($feedInfo)
    {
        $feedType = $feedInfo->feed_type;
        switch ($feedType) {
            case FeedTypes::CREATE:
                if(isset($feedInfo->re_list) && ($feedInfo->re_list == 1)){
                    return 'LMS_REVISE_FIXED_PRICE_ITEM';
                }else {
                    return 'LMS_ADD_FIXED_PRICE_ITEM';
                }
            case FeedTypes::SHIPMENT_UPDATE:
                return 'LMS_SET_SHIPMENT_TRACKING_INFO';
            // case FeedTypes::IMAGE_UPDATE:
            //     return 'MPItem';
            case FeedTypes::DELETE:
                return 'LMS_END_FIXED_PRICE_ITEM';
            case FeedTypes::INVENTORY:
                return 'LMS_REVISE_INVENTORY_STATUS';
            case FeedTypes::PRICE:
                return 'LMS_REVISE_INVENTORY_STATUS';
            default:
                return '';
        }
    }

    public function createShippingFulfillment($orderId, array $data)
    {
        try {

            $accessToken = $this->refreshToken();

            $response = $this->client->post("/sell/fulfillment/v1/order/{$orderId}/shipping_fulfillment", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data,
            ]);

            Log::info('Fullfillment Response: ' . $response->getBody());

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = json_decode($e->getResponse()->getBody(), true);
                return ['error' => $error];
            }
            return ['error' => $e->getMessage()];
        }
    }

    public function cancelOrder($data)
    {

        try {
            $accessToken = $this->refreshToken();

            $response = $this->client->post("/post-order/v2/cancellation", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $data,
            ]);

            Log::info('Cancel Order Response: ' . $response->getBody());

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = json_decode($e->getResponse()->getBody(), true);
                return ['error' => $error];
            }
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchListingInfoBySku($sku)
    {

        try {

            $accessToken = $this->refreshToken();

            $response = $this->client->post("/sell/inventory/v1/inventory_item/".$sku, [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type'  => 'application/json',
                ]
            ]);

            Log::info('Inventory Item Detail ' . $response->getBody());
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }

    }

    public function getListingItems($skuList){

        try {

            $accessToken = $this->refreshToken();

            if (!isset($accessToken)) return null;

            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><GetSellerListRequest xmlns="urn:ebay:apis:eBLBaseComponents"></GetSellerListRequest>');
            $skuArray = $xml->addChild('SKUArray');
            foreach ($skuList as $sku) {
                $skuArray->addChild('SKU', $sku);
            }
            $pagination = $xml->addChild('Pagination');
            $pagination->addChild('EntriesPerPage', '100');
            $pagination->addChild('PageNumber', '1');
//            $xml->addChild('IncludeItemSpecifics','true');

            $xml->addChild('StartTimeFrom', gmdate('Y-m-d\TH:i:s\Z', strtotime('-119 days')));
            $xml->addChild('StartTimeTo', gmdate('Y-m-d\TH:i:s\Z', strtotime('now')));

            $outPutSelector = $xml->addChild('GranularityLevel', 'Coarse');

            $xmlRequest = $xml->asXML();

            $response = $this->client->post('ws/api.dll', [
                'verify' => false,
                'headers' => [
                    'X-EBAY-API-COMPATIBILITY-LEVEL' => '967',
                    'X-EBAY-API-CALL-NAME' => 'GetSellerList',
                    'X-EBAY-API-SITEID' => '0',
                    'X-EBAY-API-IAF-TOKEN' => $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => $xmlRequest
            ]);

            $body = $response->getBody()->getContents();
            $xml = simplexml_load_string($body);
            $data = json_decode(json_encode($xml), true);

//            Log::info('Item Detail ' . json_encode($xml));

            return $data;

        }catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                Log::error('Error: ' . $e->getMessage());
                return null;
                /*throw new \Exception('Unexpected error occurred: ' . $e->getMessage());*/
            }
        }
    }
}
