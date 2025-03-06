<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class EbayAuthService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    private $redirectUri;
    private $ebay_refresh_token;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.sandbox.ebay.com/']);
        //$this->client = new Client(['base_uri' => 'https://api.ebay.com/']);
        $this->clientId = 'CommonMr-Testing-SBX-76628eb6f-8cb87fba';
        $this->clientSecret = 'SBX-6628eb6f4151-c0a1-4b48-b869-3621';
        $this->redirectUri = 'https://localaselna.com/public/ebay/callback';
        $this->ebay_refresh_token = '';
        //$this->accessToken = $this->refreshToken();
        $this->accessToken = 'v^1.1#i^1#f^0#r^0#p^3#I^3#t^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';

        //$this->accessToken = 'v^1.1#i^1#p^3#f^0#I^3#r^1#t^Ul4xMF8wOkE2NEQwMDY5QTNGRUVCODAzMUI0MEZGQjA4REZGMzM0XzFfMSNFXjI2MA==';

    }

    // Exchange Authorization Code for Access Token
    private function getAccessToken($authorizationCode)
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

            Log::info('Token Created ' . $data);

            return $data ?? null;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Refresh eBay Access Token
    public function refreshToken()
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
                    'refresh_token' => $this->ebay_refresh_token,
                    'scope' => 'https://api.ebay.com/oauth/api_scope/sell.inventory',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

           /* echo '<pre>';
            print_r($data);
            echo '</pre>';die;*/

            if (isset($data['access_token'])) {

                Log::info('Token Refresh ' . $data);
                //$this->storeAccessToken($user->id, $data);
                return $data['access_token'];
            }

            return response()->json(['error' => 'Failed to refresh token'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Redirect User to eBay Authorization Page
    public function redirectToEbay()
    {
        $scope = urlencode('https://api.ebay.com/oauth/api_scope/sell.inventory');
        $authUrl = "https://auth.sandbox.ebay.com/oauth2/authorize?client_id={$this->clientId}&redirect_uri={$this->redirectUri}&response_type=code&scope={$scope}";
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
            //return redirect('/dashboard')->with('success', 'eBay account connected!');
        }

        return response()->json(['error' => 'Failed to get access token'], 400);
    }

    public function createTask() {

        try {
            $response = $this->client->post('sell/feed/v1/task', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
                ],
                'json' => [
                    'schemaVersion' => '1240',
                    'feedType' => 'LMS_ADD_FIXED_PRICE_ITEM'
                ],
            ]);

            $headerValue = $response->getHeader('location');

            if(isset($headerValue[0]) && $headerValue[0] !=''){
                $split_arr = explode('/',$headerValue[0]);
                $taskId = end($split_arr);
                Log::info('Task Created ' . $taskId);
                return $taskId;
            }

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
            }
        }
    }

    public function getResultFeed(string $taskId, string $listingId) {

        $savePath = public_path('result-feeds/ebay/'.$listingId.'.zip');
        try {
            $response = $this->client->get('sell/feed/v1/task/' . $taskId . '/download_result_file', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
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
                return $savePath;
            }

            /*echo '<pre>';
            print_r($headerValue);
            echo '</pre>';die;*/

            return $response->getStatusCode() === 200 ? $savePath : false;

        }catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
            }
        }

    }

    public function downloadInputFeed(string $taskId, string $listingId) {

        $savePath = public_path('input-feeds/ebay/'.$listingId.'.xml');
        try {
            $response = $this->client->get('sell/feed/v1/task/' . $taskId . '/download_input_file', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Accept' => 'application/octet-stream',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
                ],
                'sink' => $savePath,
            ]);

            $headerValue = $response->getHeaders();

            Log::info('Feed Status: ' . json_encode($headerValue));

            /*echo '<pre>';
            print_r($headerValue);
            echo '</pre>';die;*/

            return $response->getStatusCode() === 200 ? $savePath : false;

        }catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
            }
        }

    }

    public function getTaskDetails(string $taskId)
    {
        try {
            $response = $this->client->get("sell/feed/v1/task/".$taskId, [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
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
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
            }
        }
    }

    public function uploadFile(string $taskId, string $fileName, string $filePath)
    {
        try {
            $response = $this->client->post("sell/feed/v1/task/".$taskId."/upload_file", [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
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
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
            }
        }
    }

    public function getOrders()
    {
        if (!$this->accessToken) {
            return ['error' => 'Access token not found'];
        }

        try {
            $response = $this->client->get('/sell/fulfillment/v1/order', [
                'verify' => false,
                'headers' => [
                    'Authorization' => "Bearer $this->accessToken",
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'limit' => 50, // Fetch max 50 orders
                ],
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->handleApiErrors($errorBody);
            } else {
                throw new \Exception('Unexpected error occurred: ' . $e->getMessage());
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
                        throw new \Exception('Custom error: Duplicate request');
                    case 12345:
                        throw new \Exception('Custom error: Missing required parameter');
                    case 160001:
                        throw new \Exception('There was a problem with an eBay internal system or process. Contact eBay Developer Technical Support for assistance.');
                    case 160002:
                        throw new \Exception('The authentication scope {scope} is incorrect for feedType. Refer to documentation for details about the scopes.');
                    case 160003:
                        throw new \Exception('Task {taskID} does not exist.');
                    case 160017:
                        throw new \Exception('The \'feedType\' is missing or invalid.');
                    case 160018:
                        throw new \Exception('The \'schemaVersion\' is invalid.');
                    case 160020:
                        throw new \Exception('The format of the file to be uploaded is invalid. {additionalInfo}');
                    case 160022:
                        throw new \Exception('You are not allowed to access this resource. Contact eBay Developer Technical Support for assistance.');
                    case 160023:
                        throw new \Exception('Task {taskID} cannot upload a file.');
                    case 160024:
                        throw new \Exception('You have reached the maximum number of feed tasks that can be queued or processed concurrently. Wait for current tasks to complete before adding tasks. For more information, see the documentation for this API.');
                    case 160025:
                        throw new \Exception('You have exceeded the maximum number of records or tasks that can be created or processed in the period (hourly or daily). Wait until the present period ends before trying again. Please reference the API documentation for more information.');
                    case 160026:
                        throw new \Exception('This task has expired and you cannot use it to upload a file. You must upload a file within a hour of creating the task.');
                    case 160027:
                        throw new \Exception('The X-EBAY-C-MARKETPLACE-ID header is missing or invalid.');
                    case 160028:
                        throw new \Exception('The file you tried to upload is too large. Please try using a smaller file. For more information, see the documentation for this API.');
                    case 160030:
                        throw new \Exception('This resource is not applicable for the feed type {feedTypeValue}. For more information, see the documentation for this API.');
                    case 160100:
                        throw new \Exception('Payload not found. Make sure your file is uploaded inside the Request Body under the key name "file".');
                    default:
                        throw new \Exception("eBay API Error: {$error['message']}");
                }
            }
        } else {
            throw new \Exception('Unexpected API error structure');
        }
    }

}
