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
        $this->accessToken = 'v^1.1#i^1#f^0#p^3#I^3#r^0#t^H4sIAAAAAAAAAOVZf2wbVx2P86Nbu2VFGxo/NsC6dUIsO/vdT59PSyQncRqv+eHGbtKFofDu7p19yfnucu9dkitCigIao0j8sU2dVMHUaQP+2SaGypi0XxKaJsT+ACHE2gkGQwhoOyEhDUGhG7yzU9cJXRvbk2qB/7Heu++vz/fX+wXWd+2+64HxB/7eH7uu+8Q6WO+OxbgbwO5dfQM39XR/sq8LNBDETqzvW+/d6PnzPRhWbE+dQdhzHYziaxXbwWp1cpAJfEd1Ibaw6sAKwirR1UJmckLlE0D1fJe4umsz8dzoIJMGpgZEoMO0oOkyb9JZ56LMojvIiEDRNKTpUJD5VBrw9DvGAco5mECHDDI84CUW8CyXKgJRlRRVAglJlOaZ+CzyseU6lCQBmKGquWqV12+w9cqmQoyRT6gQZiiXGStMZ3Kj2aniPckGWUObfigQSAK8dTTiGig+C+0AXVkNrlKrhUDXEcZMcqimYatQNXPRmBbMr7pah4pkCmKa53lRSiPtQ3HlmOtXILmyHdGMZbBmlVRFDrFIeDWPUm9oi0gnm6MpKiI3Go/+DgbQtkwL+YNMdjhz36FCdoaJF/J5312xDGRESDlBFAGXTovMEEGYuhD5C4swRLgMLRtqm+pqMjedvU3fiOsYVuQ6HJ9yyTCitqPtHhIbPESJpp1pP2OSyK5GOrnuSTAfhbYWy4CUnSi6qELdEa8Orx6Hi4lxKRU+rNSQdDMlSFDRhJSpwC2pEdV6y+kxFEUok88nI1uQBkO2Av0lRDwb6ojVqXuDCvItQxUkkxcUE7GGnDZZMW2arCYZMsuZCAGENE1PK/9/WUKIb2kBQfVM2f6hCnWQKeiuh/Kubekhs52k2n8282INDzJlQjw1mVxdXU2sCgnXLyV5ALjk4cmJgl5GFcjUaa2rE7NWNWl1RLmwpZLQo9as0QSkyp0SMyT4Rh76JBwOQjouINumfxeTeIuFQ9tnPwDqiG1RPxSpos5COu5igoy2oBloxdLRgmVcM2RRrV8WHcu1hcx2S5YziUjZvXbYLosragu50baw0S4KSWehqjcWuchzmw2Ik1MsSKkAtAU243m5SiUgULNRrsNiKQpyShbagucFwTWsvsuiQkcWw+U1TlvGuC1o0eKrWtBUibuEHNo/o1rvsB46kx2byRbGF4rTB7JTbaGdQaZP1/FihLXT8jRzMDOWob/J/JGy5kwsD9hGuawPuE5acxUo8vNjyXLBm12CcwcnPYcrOYcmpDX93vwIWHH2H0g741KymHN5abg0ONiWkwpI91GHta6wJN9n35sbh/qyMjcLU+OOQIZzSWPg8Mjq0gFlZnpkNDsmlpMrQbY98MXNMuiwEvBribtQrdIFOmoLZLZU62dRrXcQSHreT9MDh8alJQA1UTEFieNlaEY/XeCVtpeoDqv4EbdScZ1Jny3SYwadYQvDh9mULPMK0mSTVXRNSZkabHPp+l9duXB0uOksaBE/pgKgZyWihTWhu5WkC+kpPppaqFoc3wlRUgtCqt9AfsJH0HAdO9w5Xymg6VTjbmCKav0KjJiewRK1QziF0qTWrcxN8FjOCj21uX7YisI6cxM8UNfdwCGtqNtkbYLDDGzTsu3ogN6Kwgb2Zsx0oB0SS8etx7B6C0Pdi61SmTQrh85VkE/5dUggPeC1kMC47HpelIU69HcIvVovpknrBQZ69carOWMto3b92CrYOj/tEpbdthSv7DqodSnVPXxNEjQMunFoOYh1i6KrwraF1C60W6oFy4n6Lm6CxYNhtfIMC3vRqtFEYyGokjB8aDZTdxFTE+Q+okbBnWfqNqZWQ+G4xDItvSYDBxrWfctroV4+UE4rwcW0iTcV2hpDXVV79zTIsHykk4XAtzprN1HbHi5M0hEJnEV223aRXQxRuezTWr/JtdtyQeTiTryFy2cKhbnpmfbu4UbRSqft+9NK9GZpKKxh8Bwr0j0+qwk6YpFsSBKPkMyD9s46HXfzyKWEdEpWBF7eKa5tEw0vHf/11JXc+uI81FX9cRuxn4KN2GvdsRgYBSw3AD63q+dQb8+NDKatOoGhY2juWsKCZoLucxy6MPkosYRCD1p+9y1dry933b2+Zzz5/aP3bwwUF8Ou6xsevk98AXy8/vS9u4e7oeEdHNx+6Usft/dj/bwEeC4FREmRwDy449LXXu7W3o8en70u/uqbP/v5L4+/tPSdb4z8aOqZm8+D/jpRLNbX1bsR6wpuTzz2tvWp42+ffuhm4bkfzrPhlx4qmMEZfGc5+6vHhLdu++vj46OfKS+dH8j35eTfvfr5xy88f+uDr5/zzF3k5f2JPczK1JOn/vD+j1985lhubmzyudD78o3q/ZPvHX1jGrwQF9+ITQlv+l9fe8T5zT/2nZ04M75+6vQdjxa/9Ym/XJjZS/ijc/1h7w96nj03+xX0k+S/7lJiJ765/+S8W/ysd+6F3d+bMx/+4uyxp+ce/tvZs/BPt3362e69H+n5d/e7aw++9bWJk+/czWn7ihdeekc4wJ9Wznz1yPljf3zl26V/PnpncuAY99pT5KnTJ3/xe+W3T/j9r3R/Vzy4op3a4z+pXt+3/l7i10/bhQLz7vtP1GL6H8mcTImSIAAA';

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
