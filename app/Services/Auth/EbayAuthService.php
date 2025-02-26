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
