<?php

namespace App\Services\Auth;

use App\Constants\ShopifyQueries;
use App\Services\CriticalErrorService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class ShopifyAuthService
{

    protected $client;
    protected $accessToken;
    protected $endpoint;
    protected $apiVersion;

    public function __construct($clientSecret, $endpoint)
    {
        $this->client = new Client(['verify' => false]);
        $this->accessToken = $clientSecret;
        //$this->accessToken = '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';
        //$this->endpoint = env('SHOPIFY_API_ENDPOINT');
        $this->endpoint = $endpoint;
        //$this->apiVersion = env('SHOPIFY_API_VERSION');
        $this->apiVersion = '2025-01';
    }

    public function makeGraphQLRequest($query, $variables = [], $apiVersion = null)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];

        $bodyArray['query'] = $query;
        if (!empty($variables)) {
            $bodyArray['variables'] = $variables;
        }

        $body = array_filter($bodyArray, function ($value) {
            return $value !== null;
        });

        if (!isset($apiVersion)) {
            $apiVersion = $this->apiVersion;
        }


        try {
            $request = new Request('POST', $this->endpoint . '/admin/api/' . $apiVersion . '/graphql.json', $headers, json_encode($body));
            $res = $this->client->sendAsync($request)->wait();

            Log::info('Response: ' . $res->getBody());

            $result = json_decode($res->getBody(), true);

            return $result ?? null;
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());

            CriticalErrorService::report('Shopify Marketplace API failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['request_body' => $bodyArray]);

            return null;
        }
    }

    public function saveProduct($productData)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$saveProduct, $productData);
    }

    public function getProductById($marketplace_product_id)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$getProductById, ['id' => $marketplace_product_id]);
    }

    public function updateProduct($productData)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$updateProduct, $productData);
    }

    public function createCollection($createCollection)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$createCollection, $createCollection);
    }

    public function updateCollection($updateCollection)
    {
        return $this->makeGraphQLRequest(ShopifyQueries::$updateCollection, $updateCollection);
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

        Log::info('Inventory Item: ' . json_encode($response));

        return $response['data']['productVariant']['inventoryItem']['id'] ?? null;
    }

    public function getLocationId()
    {

        $response = $this->makeGraphQLRequest(ShopifyQueries::$getLocation);

        Log::info('Location Item: ' . json_encode($response));

        return $response['data']['locations']['edges'][0]['node']['id'] ?? null;
    }

    public function updateInventory($inventoryUpdates)
    {
        if (!empty($inventoryUpdates)) {
            $variables = [
                'input' => [
                    'reason' => 'correction',
                    'name' => 'available',
                    //'locationId' => $inventoryUpdates['locationId'], // your actual Location GID
                    'changes' => [$inventoryUpdates['changes']],
                ],
            ];

            $response = $this->makeGraphQLRequest(ShopifyQueries::$inventoryAdjustQuantities, $variables);

            //Log::info('Inventory Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function updatePrice($priceUpdatesData)
    {

        if (!empty($priceUpdatesData)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$productVariantUpdate, $priceUpdatesData);

            //Log::info('Price Update Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function addTags($entityId,$tags)
    {
        $variables = [
            'id' => $entityId,
            'tags' => $tags
        ];
        if (!empty($variables)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$AddTags, $variables);

            Log::info('Add Tags Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function removeTags($entityId,$tags)
    {
        $variables = [
            'id' => $entityId,
            'tags' => $tags
        ];
        if (!empty($variables)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$removeTags, $variables);

            Log::info('Remove Tags Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function getOrders($sync_start_date)
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
            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrders, array('first' => 50, 'query' => 'created_at:>' . $sync_start_date));
            if (!isset($result['errors'])) {
                return $result['data']['orders']['edges'];
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getLatestOrders()
    {

        try {
            $result = $this->makeGraphQLRequest(ShopifyQueries::$getLatestOrders, array('first' => 1));
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
        return $this->makeGraphQLRequest(ShopifyQueries::$getPublications, null);
    }

    public function getFullfillmentOrders($orderid)
    {

        if (!empty($orderid)) {
            $variable['id'] = $orderid;
            $response = $this->makeGraphQLRequest(ShopifyQueries::$getFullfillmentOrders, $variable);

            Log::info('Fullfillment Orders Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function updateFullfillment($variables)
    {

        if (!empty($variables)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$createFullfillment, $variables);

            //Log::info('Update Fullfillment Response: ' . json_encode($response));

            return $response;
        } else {
            return false;
        }
    }

    public function cancelOrder($orderId, $reason = 'CUSTOMER')
    {

        if (!empty($orderId)) {
            $variable = [
                'notifyCustomer' => true,
                'orderId' => "gid://shopify/Order/" . $orderId,
                'reason' => $reason, // CUSTOMER, DECLINED, FRAUD, INVENTORY, OTHER, STAFF
                'refund' => true,
                "restock" => true,
                'staffNote' => 'Order Cancelled',
            ];
            Log::info('Cancel Orders Variables: ' . json_encode($variable));
            $response = $this->makeGraphQLRequest(ShopifyQueries::$cancelOrder, $variable);

            //Log::info('Cancel Orders Response: ' . json_encode($response));

            /*echo '<pre>';
            print_r($response);
            echo '</pre>';*/

            return $response;
        } else {
            return false;
        }
    }

    public function getOrderDetail($orderID)
    {

        try {
            $orderID = "gid://shopify/Order/" . $orderID;
            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrderDetail, array('id' => $orderID));
            Log::info('Shopify Orders Detail Response: ' . json_encode($result));
            if (!isset($result['errors'])) {
                return $result['data']['order'];
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getTransactions($orderID)
    {

        try {

            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrderTransaction, array('orderId' => $orderID));
            /*echo '<pre>';
            print_r($result);
            echo '</pre>';die;*/
            Log::info('Shopify Orders Detail Response: ' . json_encode($result));
            if (isset($result['data']['order']['transactions'])) {
                return $result['data']['order']['transactions'] ?? null;
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getOrder($orderID)
    {

        try {

            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrder, array('query' => "name:'#'.$orderID"));
            if (!isset($result['errors'])) {
                Log::info('Shopify Orders Detail Response: ' . json_encode($result));
                return $response['data']['orders']['edges'][0]['node'] ?? null;
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function createRefund($orderId, $variables)
    {
        if (!empty($orderId)) {

            $response = $this->makeGraphQLRequest(ShopifyQueries::$createRefund, $variables);

            //Log::info('Create Refund Response: ' . json_encode($response));
            if (!isset($response['errors']) || !isset($response['userErrors'])) {
                return $response['data']['refundCreate']['refund']['id'] ?? null;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function orderRefund($orderId, $lineItemId, $quantity, $transactionId, $amount)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];

        $payload = [
            'refund' => [
                'refund_line_items' => [
                    [
                        'line_item_id' => $lineItemId,
                        'quantity' => $quantity,
                    ]
                ],
                'transactions' => [
                    [
                        'parent_id' => $transactionId,
                        'amount' => $amount,
                        'kind' => 'refund',
                        'gateway' => 'shopify_payments',
                    ]
                ]
            ]
        ];

        try {
            $request = new Request('POST', $this->endpoint . '/admin/api/' . $this->apiVersion . '/orders/{$orderId}/refunds.json', $headers, json_encode($payload));
            $res = $this->client->sendAsync($request)->wait();

            Log::info('Response: ' . $res->getBody());

            $result = json_decode($res->getBody(), true);

            return $result ?? null;
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return null;
        }
    }

    public function refundOrder($refundId)
    {

        if (!empty($refundId)) {
            $variable = [
                'id' => $refundId
            ];
            $response = $this->makeGraphQLRequest(ShopifyQueries::$refundOrder, $variable);

            //Log::info('Refund Orders Response: ' . json_encode($response));

//            echo '<pre>';
//            print_r($response);
//            echo '</pre>';die;
            return $response;
        } else {
            return false;
        }
    }

    public function getProducts($cursor = NULL)
    {

        $variable = [];
        if ($cursor != NULL) {
            Log::info('Cursor: ' . $cursor);
            $variable = ['cursor' => $cursor];
            $response = $this->makeGraphQLRequest(ShopifyQueries::$getProductsWithCursor, $variable);
        } else {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$getProducts, $variable);
        }
        //Log::info('Refund Orders Response: ' . json_encode($response));

        return $response;

    }

    public function getCollectionByHandle(string $handle)
    {
        $query = ShopifyQueries::$getCollectionByHandle;
        $variables = ['handle' => $handle];

        $response = $this->makeGraphQLRequest($query, $variables);

        if (isset($response['data']['collectionByHandle'])) {
            return $response['data']['collectionByHandle'];
        }

        return null;
    }

    public function syncIndividualPage(mixed $StoreNavigationPage)
    {
        $isPageExists = $this->pageAlreadyExists($StoreNavigationPage->url_path);
        $variables = ['page' => [
            'title' => $StoreNavigationPage->title,
            'body' => $StoreNavigationPage->generated_body,
            'handle' => $StoreNavigationPage->url_path,
            'isPublished' => true,
        ]];

        if ($isPageExists['exists']) {
            $query = ShopifyQueries::$pageUpdate;

            $variables['id'] = $isPageExists['id'];

            $response = $this->makeGraphQLRequest($query, $variables);

            if (isset($response['data']['pageUpdate'])) {
                Log::info('Page Updated: ' . json_encode($response['data']['pageUpdate']));
                $pageData = $response['data']['pageUpdate']['page'];
                $StoreNavigationPage->page_id = str_replace('gid://shopify/Page/', '', $pageData['id']);
                $StoreNavigationPage->url_path = $pageData['handle'];
                $StoreNavigationPage->save();

                return $response['data']['pageUpdate'];
            }
        } else {
            $query = ShopifyQueries::$pageCreate;

            $response = $this->makeGraphQLRequest($query, $variables);

            if (isset($response['data']['pageCreate'])) {
                Log::info('Page Created: ' . json_encode($response['data']['pageCreate']));
                $pageData = $response['data']['pageCreate']['page'];
                $StoreNavigationPage->page_id = str_replace('gid://shopify/Page/', '', $pageData['id']);
                $StoreNavigationPage->url_path = $pageData['handle'];
                $StoreNavigationPage->save();

                return $response['data']['pageCreate'];
            }
        }

        return null;
    }

    private function pageAlreadyExists($url_path)
    {
        if ($url_path == null || $url_path == '') {
            Log::error('URL path is empty or null');
            return ['exists' => false];
        }
        $query = ShopifyQueries::$pageFetch;
        Log::info('Checking if page exists with URL path: ' . $url_path);
        $variables['handle'] = 'handle:'.$url_path;

        $getPageData = $this->makeGraphQLRequest($query, $variables, '2025-07');
        Log::info('Get Page Data: ' . json_encode($getPageData));
        $existingPages = $getPageData['data']['pages']['edges'];
        Log::info('Existing Pages: ' . json_encode($existingPages));
        if (count($existingPages) > 0) {
            return ['exists' => true, 'id' => $existingPages[0]['node']['id']];
        } else {
            return ['exists' => false];
        }
    }
}
