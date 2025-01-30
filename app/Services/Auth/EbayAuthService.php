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
        $this->accessToken = 'v^1.1#i^1#r^0#p^3#I^3#f^0#t^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';

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
                    'schemaVersion' => '1238',
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
