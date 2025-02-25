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
        $this->accessToken = 'v^1.1#i^1#r^0#p^3#f^0#I^3#t^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';

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
