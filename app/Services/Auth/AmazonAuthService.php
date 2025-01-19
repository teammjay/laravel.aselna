<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AmazonAuthService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.amazon.com/auth/o2/token',
        ]);
    }

    public function getAccessToken()
    {
        $response = $this->client->post('/auth/o2/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => env('AMAZON_REFRESH_TOKEN'),
                'client_id' => env('AMAZON_CLIENT_ID'),
                'client_secret' => env('AMAZON_CLIENT_SECRET'),
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true)['access_token'];
    }

    public function uploadProductFeed($feedContent)
    {
        $accessToken = $this->getAccessToken();
        Log::info('Access Token Created ' . $accessToken);
        return $accessToken;

//        $response = $this->client->post('/feeds/2021-06-30/documents', [
//            'headers' => [
//                'Authorization' => "Bearer {$accessToken}",
//            ],
//            'json' => [
//                'contentType' => 'text/xml; charset=UTF-8',
//            ],
//        ]);
//
//        $uploadDetails = json_decode($response->getBody()->getContents(), true);
//        $uploadUrl = $uploadDetails['url'];
//
//        $this->client->put($uploadUrl, [
//            'headers' => ['Content-Type' => 'text/xml; charset=UTF-8'],
//            'body' => $feedContent,
//        ]);

        return $uploadDetails['feedDocumentId'];
    }

    public function submitFeed(mixed $generatedFeedId)
    {
        $accessToken = $this->getAccessToken();

        $response = $this->client->post('/feeds/2021-06-30/feeds', [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'marketplaceIds' => [env('AMAZON_MARKETPLACE_ID')],
                'feedType' => 'POST_PRODUCT_DATA',
                'inputFeedDocumentId' => $generatedFeedId,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
