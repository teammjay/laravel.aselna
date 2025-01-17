<?php

namespace App\Services\Auth;

use Google\Client;
use Illuminate\Support\Facades\Log;
use Google\Service\ShoppingContent;

class GoogleShoppingAuthService
{
    protected $merchantID;
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
        $this->merchantID = '';
    }

    public function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Laravel Google Shopping API');
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
        $client = $this->googleService->getClient();
        $service = new ShoppingContent($client);

        // Define the feed configuration
        $datafeed = new ShoppingContent\Datafeed();
        $datafeed->setName('CSV Feed');
        $datafeed->setContentType('products');
        $datafeed->setFileName(basename($csvFilePath));
        $datafeed->setTargetCountry('US');
        $datafeed->setContentLanguage('en');

        // Create a Datafeed object in Google Merchant Center
        $merchantId = $this->merchantID;
        $service->datafeeds->insert($merchantId, $datafeed);

        // Upload the feed
        $service->datafeeds->update(
            $merchantId,
            $datafeed->getId(),
            file_get_contents($csvFilePath)
        );

        return "Feed uploaded successfully!";
    }

}
