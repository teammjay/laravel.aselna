<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class ShopifyAuthService
{

    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $endpoint;

    public function __construct($clientId, $clientSecret)
    {
        $this->client = new Client();
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->endpoint = env('SHOPIFY_API_ENDPOINT');
    }

    public function authenticate()
    {
        Log::info('Authenticating Shopify');
        Log::info('Client ID: ' . $this->clientId);
        Log::info('Client Secret: ' . $this->clientSecret);
        Log::info('Endpoint: ' . $this->endpoint);


        return $data ?? null;
    }

    public function saveProducts($productData)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);


    }
}
