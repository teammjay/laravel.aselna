<?php

namespace App\Services\Auth;

use App\Services\Marketplace\Service\GoogleShoppingService;
use Google\Client;
use Illuminate\Support\Facades\Log;
use Google\Service\ShoppingContent;

class GoogleShoppingAuthService
{
    protected $merchantID;

    public function __construct()
    {
        $this->merchantID = '';
    }

    public function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Google Shopping API');
        $client->setScopes(['https://www.googleapis.com/auth/content']);
        $client->setAuthConfig(storage_path('app/google_credentials.json'));
        $client->setAccessType('offline');

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        }

        return $client;
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
            $merchantId = $this->merchantID;
            $feedResponse = $service->datafeeds->insert($merchantId, $datafeed);

            // Upload the feed
            $service->datafeeds->update(
                $merchantId,
                $datafeed->getId(),
                file_get_contents($csvFilePath)
            );

            Log::info('Upload Feed Detail ' . $feedResponse);

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

            $status = $service->datafeedstatuses->get($this->merchantID, $datafeedId);

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
