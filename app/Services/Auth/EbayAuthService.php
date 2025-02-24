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
        $this->clientId = 'CommonMr-Testing-SBX-76628eb6f-8cb87fba';
        $this->clientSecret = 'SBX-6628eb6f4151-c0a1-4b48-b869-3621';
        $this->redirectUri = 'https://localaselna.com/public/ebay/callback';
        $this->ebay_refresh_token = '';
        //$this->accessToken = $this->refreshToken();
        $this->accessToken = 'v^1.1#i^1#I^3#r^0#p^3#f^0#t^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';

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

            if (file_exists($savePath)) {
                return response()->download($savePath);
            }

            $errorResponse = json_decode($response->getBody()->getContents(), true);

            Log::info('Feed Response: ' . json_encode($errorResponse));

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
