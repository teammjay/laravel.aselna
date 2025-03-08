<?php

namespace App\Services\Auth;

use App\Constants\ShopifyQueries;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class ShopifyAuthService
{

    protected $client;
    protected $accessToken;
    protected $endpoint;
    protected $apiVersion;

    public function __construct($clientSecret)
    {
        $this->client = new Client();
        $this->accessToken = $clientSecret;
        $this->endpoint = env('SHOPIFY_API_ENDPOINT');
        $this->apiVersion = env('SHOPIFY_API_VERSION');
    }

    public function makeGraphQLRequest($query, $variables)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];

        $body = [
            'query' => $query,
            'variables' => $variables
        ];

        try {
            $request = new Request('POST', $this->endpoint . '/admin/api/' . $this->apiVersion . '/graphql.json', $headers, json_encode($body));
            $res = $this->client->sendAsync($request)->wait();

            Log::info('Response: ' . $res->getBody());

            $result = json_decode($res->getBody(), true);

            return $result ?? null;
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function saveProduct($productData)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$saveProduct, $productData);
    }

    public function createProductVariant($productVariantData)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$createProductVariant, $productVariantData);
    }
}
