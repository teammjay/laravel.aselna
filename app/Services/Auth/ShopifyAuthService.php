<?php

namespace App\Services\Auth;

use App\Constants\ShopifyQueries;
use App\Services\CriticalErrorService;
use App\Utils\Navigationutility;
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
        } catch (\Throwable $e) {
            Log::error('Error: ' . $e->getMessage());

            CriticalErrorService::report('Shopify Marketplace API failed - EndPoint: '.$this->endpoint, [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ], ['request_body' => $bodyArray]);

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

    public function addTags($entityId, $tags)
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

    public function removeTags($entityId, $tags)
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

    public function productPublish($entityId, $productPublications)
    {
        $variables['input'] = [
            'id' => $entityId,
            'productPublications' => $productPublications
        ];

        if (!empty($variables['input'])) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$productPublish, $variables);

            Log::info('Product Publish Response: ' . json_encode($response));

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
            $result = $this->makeGraphQLRequest(ShopifyQueries::$getOrders, array('first' => 250, 'query' => 'created_at:>\'' . $sync_start_date . '\''));
            if (!isset($result['errors'])) {
                return $result['data']['orders']['edges'];
            }

        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());

        }
        return null;
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
        return null;
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

    public function getProductImages($productId = null)
    {

        $variable = [];
        if ($productId != null) {
            Log::info('Product ID: ' . $productId);
            $variable = ['id' => $productId];
        }
        $response = $this->makeGraphQLRequest(ShopifyQueries::$getProductImages, $variable);

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
        // A row without a handle still has to find its existing Shopify page; creating it instead
        // makes Shopify save a copy under "<handle>-1".
        $handle = !empty($StoreNavigationPage->url_path)
            ? $StoreNavigationPage->url_path
            : Navigationutility::pageHandle($StoreNavigationPage->title);

        $existingPageId = $this->findExistingPageId($StoreNavigationPage, $handle);
        $variables = ['page' => [
            'title' => $StoreNavigationPage->title,
            'body' => $StoreNavigationPage->generated_body,
            'handle' => $handle !== '' ? $handle : null,
            'isPublished' => true,
        ]];

        if ($existingPageId) {
            $query = ShopifyQueries::$pageUpdate;

            $variables['id'] = $existingPageId;

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

            if (isset($response['data']['pageCreate']['page'])) {
                Log::info('Page Created: ' . json_encode($response['data']['pageCreate']));
                $pageData = $response['data']['pageCreate']['page'];

                if ($variables['page']['handle'] !== null && $pageData['handle'] !== $variables['page']['handle']) {
                    CriticalErrorService::report('Shopify page created under a different handle, possible duplicate - EndPoint: ' . $this->endpoint, [
                        'marketplace' => 'Shopify',
                        'reason' => 'Requested handle ' . $variables['page']['handle'] . ', Shopify returned ' . $pageData['handle'],
                        'method' => __METHOD__
                    ], ['store_navigation_page_id' => $StoreNavigationPage->id]);
                }
                $StoreNavigationPage->page_id = str_replace('gid://shopify/Page/', '', $pageData['id']);
                $StoreNavigationPage->url_path = $pageData['handle'];
                $StoreNavigationPage->save();

                return $response['data']['pageCreate'];
            }

            Log::error('Page Create failed: ' . json_encode($response));
        }

        return null;
    }

    /**
     * The Shopify page this row already has, looked up by the stored page_id, then the stored
     * url_path, then the handle derived from the title. Null only when none of them exists.
     */
    private function findExistingPageId(mixed $StoreNavigationPage, string $handle): ?string
    {
        if (!empty($StoreNavigationPage->page_id)) {
            $response = $this->makeGraphQLRequest(ShopifyQueries::$pageById, [
                'id' => 'gid://shopify/Page/' . $StoreNavigationPage->page_id
            ], '2025-07');

            if (!isset($response['data'])) {
                throw new \RuntimeException('Shopify page lookup failed for page id ' . $StoreNavigationPage->page_id);
            }

            if (!empty($response['data']['page']['id'])) {
                return $response['data']['page']['id'];
            }
        }

        foreach (array_unique(array_filter([$StoreNavigationPage->url_path, $handle])) as $candidate) {
            $isPageExists = $this->pageAlreadyExists($candidate);
            if ($isPageExists['exists']) {
                return $isPageExists['id'];
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
        $variables['handle'] = 'handle:' . $url_path;

        $getPageData = $this->makeGraphQLRequest($query, $variables, '2025-07');
        Log::info('Get Page Data: ' . json_encode($getPageData));

        // A failed lookup must not be read as "no page", or the caller creates a duplicate.
        if (!isset($getPageData['data']['pages']['edges'])) {
            throw new \RuntimeException('Shopify page lookup failed for handle ' . $url_path);
        }

        $existingPages = $getPageData['data']['pages']['edges'];
        Log::info('Existing Pages: ' . json_encode($existingPages));

        // The handle filter is a search, so only accept an exact handle match (not "<handle>-1").
        foreach ($existingPages as $existingPage) {
            if (($existingPage['node']['handle'] ?? null) === $url_path) {
                return ['exists' => true, 'id' => $existingPage['node']['id']];
            }
        }

        return ['exists' => false];
    }

    public function deleteProductImage(mixed $mediaIds,$productId)
    {
        $variables = [
            'mediaIds' => $mediaIds,
            'productId' => $productId
        ];

        $response = $this->makeGraphQLRequest(ShopifyQueries::$deleteProductImage, $variables);

        Log::info('Delete Product Image Response: ' . json_encode($response));

        return $response;
    }

    public function setFeaturedImage($marketplace_product_id, $reference_id)
    {
        $variables = [
            "id" => $marketplace_product_id,
            "moves" => [
                [
                    "id" => "gid://shopify/MediaImage/".$reference_id,
                    "newPosition" => "0"
                ]
            ]
        ];
        Log::info('Set Featured Image Variables: ' . json_encode($variables));

        $response = $this->makeGraphQLRequest(ShopifyQueries::$productReorderMedia, $variables);
        Log::info('Set Featured Image Response: ' . json_encode($response));
        return $response;
    }

    public function deleteMediaImages($marketplace_product_id, $mediaIdsToDelete)
    {
        $variables = [
            "productId" => $marketplace_product_id,
            "mediaIds" => $mediaIdsToDelete
        ];
        Log::info('Delete Image Variables: ' . json_encode($variables));
        $response = $this->makeGraphQLRequest(ShopifyQueries::$deleteMediaImages, $variables);
        Log::info('Delete Image Response: ' . json_encode($response));
        return $response;
    }

    public function updateMediaImage($marketplace_product_id, $reference_id, array $mediaData)
    {
        $variables = [
            "productId" => $marketplace_product_id,
            "media" => [
                [
                    "id" => "gid://shopify/MediaImage/".$reference_id,
                    "alt" => $mediaData['alt'] ?? null,
                    "previewImageSource" => $mediaData['originalSource'] ?? null
                ]
            ]
        ];
        Log::info('Update Image Variables: ' . json_encode($variables));
        $response = $this->makeGraphQLRequest(ShopifyQueries::$updateMediaImage, $variables);
        Log::info('Update Image Response: ' . json_encode($response));
        return $response;
    }
}
