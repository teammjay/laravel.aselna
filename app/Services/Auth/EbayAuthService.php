<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class EbayAuthService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $endpoint;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.ebay.com/']);
        $this->clientId = '';
        $this->clientSecret = '';
        $this->accessToken = $this->getAccessToken();
    }

    public function getAccessToken() {

        $response = $this->client->post('identity/v1/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.ebay.com/oauth/api_scope',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'] ?? null;

    }

    public function createTask() {

        try {
            $response = $this->client->post('sell/feed/v1/task', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                    'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US'
                ],
                'json' => [
                    'schemaVersion' => '1.0',
                    'feedType' => 'LMS_ORDER_ACK'
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

    public function getResultFile(string $taskId) {

        $savePath = storage_path('app/results/ebay/result_file_'.$taskId.'.csv');

        $response = $this->client->get('sell/feed/v1/task/'.$taskId.'/download_result_file', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
            'sink' => $savePath,
        ]);

        return $response->getStatusCode() === 200
            ? $savePath
            : false;

    }

    public function getTaskDetails(string $taskId)
    {
        try {
            $response = $this->client->get("sell/feed/v1/task/{$taskId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
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

    public function uploadFile(string $taskId, string $fileName, string $filePath)
    {
        try {
            $response = $this->client->post("sell/feed/v1/task/{$taskId}/upload_file", [
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
