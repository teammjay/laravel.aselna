<?php

namespace App\Services\Auth;

use App\Services\Marketplace\Service\GoogleShoppingService;
use Google\Client;
use Illuminate\Support\Facades\Log;
use Google\Service\ShoppingContent;

class GoogleShoppingAuthService
{
    protected $client;
    protected $service;
    protected $merchantId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google_credentials.json'));
        $this->client->setScopes([ShoppingContent::CONTENT]);
        $this->client->setAccessType('offline'); // Required to get a refresh token
        $this->client->setPrompt('consent');

        //$authUrl = $this->client->createAuthUrl();

        //echo "Open the following URL in your browser and authorize the app: $authUrl\n";die;


        /*$authCode = "4/0ASVgi3JVIurEdgSxKM5sul5NN9aVDTD8MZYHYrOhEflOA9-n9_VtbrmWTVJByT0cik2zjw"; // Paste the copied code here
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);

        // Save the token in a file
        file_put_contents(storage_path('app/public/google_access_token.json'), json_encode($accessToken));

        echo "Access token saved successfully.\n";die;*/

        // Load the stored access token
        $this->loadAccessToken();

        // Initialize Shopping Content Service
        $this->service = new ShoppingContent($this->client);
        $this->merchantId = '5551897674';
    }

    public function loadAccessToken()
    {
        $tokenPath = storage_path('app/google_access_token.json');

        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);

            // Refresh the token if it has expired
            if ($this->client->isAccessTokenExpired()) {
                $this->refreshAccessToken();
            }
        }
    }

    public function refreshAccessToken()
    {
        if ($this->client->getRefreshToken()) {
            $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

            // Save the new token
            file_put_contents(storage_path('app/google_access_token.json'), json_encode($newAccessToken));
            $this->client->setAccessToken($newAccessToken);
        } else {
            throw new \Exception("No refresh token available. Reauthenticate the app.");
        }
    }


    public function uploadFeed($csvFilePath)
    {
        $client = $this->getClient();
        $service = new ShoppingContent($client);

        try {
            // Define the feed configuration
            $datafeed = new ShoppingContent\Datafeed();
            $datafeed->setName('CSV Feed');
            $datafeed->setContentType('products');
            $datafeed->setFileName(basename($csvFilePath));

            // Create a Datafeed object in Google Merchant Center
            $merchantId = $this->merchantId;
            $feedResponse = $service->datafeeds->insert($merchantId, $datafeed);

            // Upload the feed
            $service->datafeeds->update(
                $merchantId,
                $datafeed->getId(),
                file_get_contents($csvFilePath)
            );

            Log::info('Upload Feed Detail ' . json_decode($feedResponse));

            // Return success response
            return [
                'success' => true,
                'message' => 'Feed uploaded successfully!',
                'datafeedId' => $feedResponse->getId(),
                'name' => $feedResponse->getName(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'errors' => [$e->getMessage()],
            ];
        }

    }

    public function getFeedStatus($datafeedId)
    {
        try {
            $client = $this->getClient();
            $service = new ShoppingContent($client);

            $status = $service->datafeedstatuses->get($this->merchantId, $datafeedId);

            Log::info('Feed Status :' . $status);

            return [
                'success' => true,
                'status' => $status->getProcessingStatus(),
                'errors' => $status->getErrors() ?? [],
                'warnings' => $status->getWarnings() ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve feed status.',
                'error' => $e->getMessage(),
            ];
        }
    }

}
