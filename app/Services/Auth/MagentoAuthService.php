<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;


class MagentoAuthService
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
//        $this->endpoint = env('MAGENTO_API_ENDPOINT');
        $this->endpoint = 'https://magento.aselna.com';
    }

    public function authenticate()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $body = [
            'username' => $this->clientId,
            'password' => $this->clientSecret,
        ];

        Log::info('Request: ' . json_encode($body));

        $request = new Request('POST', $this->endpoint . '/rest/V1/integration/admin/token', $headers);
        $res = $this->client->sendAsync($request, ['verify' => false, 'json' => $body])->wait();

        Log::info('Response: ' . $res->getBody());

        $data = json_decode($res->getBody(), true);

        return $data ?? null;
    }

    public function saveProducts($productData)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $body = $productData;
        Log::info('Product Data: ' . json_encode($body));

        $request = new Request('POST', $this->endpoint . '/rest/async/bulk/V1/products', $headers);
        $response = $this->client->sendAsync($request, ['json' => $body])->wait();

        return json_decode($response->getBody(), true);
    }

    public function saveProduct($productData)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $body = ['product' => $productData];
        Log::info('Product Data: ' . json_encode($body));

        $request = new Request('POST', $this->endpoint . '/rest/default/V1/products', $headers);
        $response = $this->client->sendAsync($request, ['json' => $body])->wait();

        return json_decode($response->getBody(), true);
    }

    public function getBulkStatus($feed_id)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $request = new Request('GET', $this->endpoint . '/rest/V1/bulk/' . $feed_id . '/status', $headers);
        $response = $this->client->sendAsync($request)->wait();

        return json_decode($response->getBody(), true);
    }

    public function getDefaultAttributeSetId()
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $endpoint = '/rest/V1/products/attribute-sets/sets/list';
        $params = [
            'searchCriteria' => [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'entity_type_id',
                                'value' => '4', // Default entity type ID for products
                                'condition_type' => 'eq'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $request = new Request('GET', $this->endpoint . $endpoint, $headers);
        return $response['items'][0]['attribute_set_id'] ?? 4; // Return default (4) if not found
    }

    public function isAttributeExists($attribute)
    {
        try {
            $accessToken = $this->authenticate();
            Log::info('Access Token Created ' . $accessToken);

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ];

            $endpoint = '/rest/V1/products/attributes/' . $attribute;
            $request = new Request('GET', $this->endpoint . $endpoint, $headers);
            $response = $this->client->sendAsync($request)->wait();
            return $response->getStatusCode() == 200;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function createCategory($name, $parent_id = 2)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $body = [
            'category' => [
                'parent_id' => $parent_id,
                'name' => $name,
                'is_active' => true,
                'include_in_menu' => $parent_id == 2,
            ]
        ];

        Log::info('Request: ' . json_encode($body));

        $request = new Request('POST', $this->endpoint . '/rest/V1/categories', $headers);
        $response = $this->client->sendAsync($request, ['json' => $body])->wait();

        return json_decode($response->getBody(), true);
    }

    public function createAttribute($attributeData)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $body = ['attribute' => $attributeData];

        $request = new Request('POST', $this->endpoint . '/rest/V1/products/attributes', $headers);
        $response = $this->client->sendAsync($request, ['json' => $body])->wait();

        return json_decode($response->getBody(), true);
    }

    public function getAttribute($attributeCode)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $endpoint = '/rest/V1/products/attributes/' . $attributeCode;
        $request = new Request('GET', $this->endpoint . $endpoint, $headers);

        $response = $this->client->sendAsync($request)->wait();

        return json_decode($response->getBody(), true);
    }

    public function updateAttributeOptions($attributeCode, $optionData)
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $body = ['attribute' => $optionData];

        $request = new Request('PUT', $this->endpoint . '/rest/V1/products/attributes/' . $attributeCode, $headers);
        $response = $this->client->sendAsync($request, ['json' => $body])->wait();

        return json_decode($response->getBody(), true);
    }

    public function getOrders($pageSize = 10, $currentPage = 1)
    {
        try {

            $accessToken = $this->authenticate();
            Log::info('Access Token Created ' . $accessToken);

            $response = $this->client->get($this->endpoint."/rest/V1/orders", [
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'query' => [
                    'searchCriteria[pageSize]' => $pageSize,
                    'searchCriteria[currentPage]' => $currentPage
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
}
