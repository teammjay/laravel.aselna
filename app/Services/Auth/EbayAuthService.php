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

    public function __construct($clientId, $clientSecret,$redirectUri,$refresh_token='')
    {
//        $this->client = new Client(['base_uri' => 'https://api.sandbox.ebay.com/']);
        $this->client = new Client(['base_uri' => 'https://api.ebay.com/']);
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

            $feedType = $this->getfeedType($listingInfo->feed_type);
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

    public function getOrders()
    {
        $accessToken = $this->refreshToken();

        try {

            $startDate = now()->subDays(7)->toIso8601String();
            $endDate = now()->toIso8601String();

            $response = $this->client->get('/sell/fulfillment/v1/order', [
                'verify' => false,
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'creationdate'=> "$startDate..$endDate",
                    'limit' => 50, // Fetch max 50 orders
                ],
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

    public function getfeedType($feedType)
    {
        switch ($feedType) {
            case FeedTypes::CREATE:
                return 'LMS_ADD_FIXED_PRICE_ITEM';
            // case FeedTypes::IMAGE_UPDATE:
            //     return 'MPItem';
            case FeedTypes::DELETE:
                return 'LMS_END_ITEM';
            case FeedTypes::INVENTORY:
                return 'LMS_REVISE_INVENTORY_STATUS';
            case FeedTypes::PRICE:
                return 'LMS_REVISE_INVENTORY_STATUS';
            default:
                return '';
        }
    }

}
