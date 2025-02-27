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
        $this->accessToken = 'v^1.1#i^1#f^0#r^0#p^3#I^3#t^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';

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
