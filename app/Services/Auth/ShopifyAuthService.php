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
        $this->client = new Client(['verify' => false]);
        $this->accessToken = $clientSecret;
        $this->accessToken = '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';
        //$this->endpoint = env('SHOPIFY_API_ENDPOINT');
        $this->endpoint = 'https://aselna-dev.myshopify.com';
        //$this->apiVersion = env('SHOPIFY_API_VERSION');
        $this->apiVersion = '2025-01';
    }

    public function makeGraphQLRequest($query, $variables = [])
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];

        $bodyArray['query'] = $query;
        if(!empty($variables)){
            $bodyArray['variables'] = $variables;
        }

        $body = array_filter($bodyArray, function ($value) {
            return $value !== null;
        });


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

    public function createCollection($createCollection)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$createCollection, $createCollection);
    }

    public function createProductVariant($productVariantData)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$createProductVariant, $productVariantData);
    }

    public function getOrdersField()
    {
        try {

            $response = $this->makeGraphQLRequest(ShopifyQueries::$orderFields, array('first' => 10));
            /*echo '<pre>';
            print_r($response);
            echo '</pre>';die;*/
            $fields = collect($response['data']['__type']['fields'])->pluck('name')->toArray();
            return $this->processFields($response['data']['__type']['fields']);;
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    private function processFields($fields)
    {
        $result = [];

        foreach ($fields as $field) {
            if (isset($field['type']['kind']) && $field['type']['kind'] === 'OBJECT' && !empty($field['type']['fields'])) {
                // If it's an object, add subfields
                $nestedFields = $this->processFields($field['type']['fields']);
                $result[] = "{$field['name']} { " . implode(" ", $nestedFields) . " }";
            } else {
                // If it's scalar, add normally
                $result[] = $field['name'];
            }
        }

        return $result;
    }

    public function getInventoryItemId($variantId)
    {

        $variables = ['id' => $variantId];

        $response = $this->makeGraphQLRequest(ShopifyQueries::$getProductVariant, $variables);

        Log::info('Inventory Item: ' .json_encode($response));

        return $response['data']['productVariant']['inventoryItem']['id'] ?? null;
    }

    public function getLocationId()
    {

        $response = $this->makeGraphQLRequest(ShopifyQueries::$getLocation);

        Log::info('Location Item: ' .json_encode($response));

        return $response['data']['locations']['edges'][0]['node']['id'] ?? null;
    }

    public function updateInventory($inventoryUpdates)
    {
        if(!empty($inventoryUpdates)) {
            $variables = [
                'input' => [
                    'reason' => 'correction',
                    'name' => 'available',
                    //'locationId' => $inventoryUpdates['locationId'], // your actual Location GID
                    'changes' => [$inventoryUpdates['changes']],
                ],
            ];

            $response = $this->makeGraphQLRequest(ShopifyQueries::$inventoryAdjustQuantities, $variables);

            Log::info('Inventory Response: ' . json_encode($response));

            return $response;
        }else{
            return false;
        }
    }

    public function updatePrice($priceUpdatesData)
    {

        if(!empty($priceUpdatesData)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$productVariantUpdate, $priceUpdatesData);

            Log::info('Price Update Response: ' . json_encode($response));

            return $response;
        }else{
            return false;
        }
    }

    public function getOrders()
    {

        //$fields = $this->getOrdersField();
        /*echo '<pre>';
        print_r($fields);
        echo '</pre>';*/
        //$fieldsString = implode("\n", $fields);
        //echo $fieldsString;
        //die;
        try {
            /*$getOrders = <<<GQL
            {
              orders(first: 5) {
                edges {
                  node {
                    $fieldsString
                  }
                }
              }
            }
            GQL;*/
            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrders, array('first' => 50));
            if (!isset($result['errors'])) {
                return $result['data']['orders']['edges'];
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getPublications()
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$getPublications,null);
    }
}
