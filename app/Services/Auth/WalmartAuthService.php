<?php

namespace App\Services\Auth;

use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Request;

class WalmartAuthService
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
        $this->endpoint = env('WALMART_API_ENDPOINT');
    }

    public function authenticate()
    {
        Log::info('Authenticating Walmart');
        Log::info('Client ID: ' . $this->clientId);
        Log::info('Client Secret: ' . $this->clientSecret);
        Log::info('Endpoint: ' . $this->endpoint);
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => uniqid(),
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ];
        $options = [
            //'verify' => false,
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ];

        //Log::info("Headers: " . json_encode($headers));
        //Log::info("Options: " . json_encode($options));

        $request = new Request('POST', $this->endpoint . '/token', $headers);
        $res = $this->client->sendAsync($request, $options)->wait();
        Log::info('Response: ' . $res->getBody());

        $data = json_decode($res->getBody(), true);

        return $data['access_token'];
    }

    public function submitFeed($fileName, $feedType)
    {
        $client = new Client();
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);

        $headers = [
            'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
            'WM_QOS.CORRELATION_ID' => uniqid(),
            'WM_SVC.NAME' => 'Walmart Service Name',
            'Accept' => 'application/json'
        ];

        $options = [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => Utils::tryFopen($fileName, 'r'),
                    'filename' => $fileName,
                ]
            ]];
        $request = new Request('POST', $this->endpoint . '/feeds?feedType=' . $feedType, $headers);
        $res = $client->sendAsync($request, $options)->wait();

        return json_decode($res->getBody(), true);
    }

    public function getFeedStatus($feedId, $number_of_items = 150)
    {
        $accessToken = $this->authenticate();

        if (!isset($number_of_items)) {
            $number_of_items = 150;
        }

        $headers = [
            'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => uniqid(),
        ];

        $request = new Request('GET', "{$this->endpoint}/feeds/{$feedId}?includeDetails=true&limit=" . $number_of_items, $headers);
        $response = $this->client->sendAsync($request)->wait();

        return json_decode($response->getBody(), true);
    }

    public function getOrders($last_order_time, $cursor, $limit = 100)
    {
        try {
            $accessToken = $this->authenticate();

            if (!$accessToken) {
                Log::error("Failed to retrieve Walmart API token.");
                return null;
            }

            $url = $this->endpoint . "/orders";
            $queryData = [];

            if (isset($cursor)) {
                parse_str(parse_url($cursor, PHP_URL_QUERY), $queryData);
            } else {
                $queryData = [
                    'createdStartDate' => $last_order_time,
                    'limit' => $limit
                ];
            }

            Log::info('Query Data: ' . json_encode($queryData));

            $response = $this->client->get($url, [
                'verify' => false,
                'headers' => [
                    'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'WM_SVC.NAME' => 'Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID' => uniqid(),
                ],
                'query' => $queryData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {

            /*CriticalErrorService::report('Walmart Marketplace Order Sync failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['start_date' => $last_order_time]);*/

            return response()->json([
                'error' => 'Walmart API request failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrderDetail($orderID)
    {
        try {
            $accessToken = $this->authenticate();

            if (!$accessToken) {
                Log::error("Failed to retrieve Walmart API token.");
                return null;
            }

            $url = $this->endpoint . "/orders/" . $orderID;

            $queryData = [
                'productInfo' => false,
                'replacementInfo' => false
            ];

            //Log::info('Query Data: ' . json_encode($queryData));

            $response = $this->client->get($url, [
                'verify' => false,
                'headers' => [
                    'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'WM_SVC.NAME' => 'Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID' => uniqid(),
                ],
                'query' => $queryData,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {

            /*CriticalErrorService::report('Walmart Marketplace Order Detail failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['order_id' => $orderID]);*/

            return response()->json([
                'error' => 'Walmart API request failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function shipOrder($purchaseOrderId, $data)
    {
        try {
            $client = new Client();
            $accessToken = $this->authenticate();

            $headers = [
                'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                'WM_QOS.CORRELATION_ID' => uniqid(),
                'WM_SVC.NAME' => 'Walmart Service Name',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            //Log::info('Order Data' . json_encode($data));

            $request = new Request('POST', "{$this->endpoint}/orders/{$purchaseOrderId}/shipping", $headers, json_encode($data));

            $res = $client->sendAsync($request)->wait();

            $response = json_decode($res->getBody(), true);
            //Log::info('Response from Walmart: ' . json_encode($response));
            return $response;

        } catch (RequestException $e) {
            // Log full error response (if available)
            if ($e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $body = (string)$e->getResponse()->getBody();

                /*CriticalErrorService::report('Walmart API Error', [
                    'marketplace' => 'Walmart',
                    'reason' => $body ?? 'Unknown',
                ],['status' => $status]);*/

                Log::error("Walmart API error ({$status}): " . $body);
            } else {
                Log::error('Request error (no response): ' . $e->getMessage());
            }

        } catch (\Exception $e) {

            /*CriticalErrorService::report('Walmart Marketplace Ship Order failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['purchase_order_id' => $purchaseOrderId]);*/

            Log::error('Error shipping order: ' . $e->getMessage());
        }
        return false;
    }

    public function cancelOrder($purchaseOrderId, $data)
    {
        try {
            $client = new Client();
            $accessToken = $this->authenticate();

            $headers = [
                'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'WM_SVC.NAME' => 'Walmart Marketplace',
                'WM_QOS.CORRELATION_ID' => uniqid(),
            ];

            //Log::info('Order Data' . json_encode($data));

            $request = new Request('POST', "{$this->endpoint}/orders/{$purchaseOrderId}/cancel", $headers, json_encode($data));
            $res = $client->sendAsync($request)->wait();
            $response = json_decode($res->getBody(), true);
            //Log::info('Response from Walmart: ' . json_encode($response));
            return $response;
        } catch (RequestException $e) {
            // Log full error response (if available)
            if ($e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $body = (string)$e->getResponse()->getBody();
                Log::error("Walmart API error ({$status}): " . $body);

                /*CriticalErrorService::report('Walmart API Error', [
                    'marketplace' => 'Walmart',
                    'reason' => $body ?? 'Unknown',
                ],['status' => $status]);*/
            } else {
                Log::error('Request error (no response): ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error cancel order: ' . $e->getMessage());

            /*CriticalErrorService::report('Walmart Marketplace Cancel Order failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['purchase_order_id' => $purchaseOrderId]);*/
        }
        return null;

    }

    public function refundOrder($purchaseOrderId, $data)
    {
        try {
            $client = new Client();
            $accessToken = $this->authenticate();

            $headers = [
                'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'WM_SVC.NAME' => 'Walmart Marketplace',
                'WM_QOS.CORRELATION_ID' => uniqid(),
            ];

            //Log::info('Order Data' . json_encode($data));

            $request = new Request('POST', "{$this->endpoint}/orders/{$purchaseOrderId}/refund", $headers, json_encode($data));
            $res = $client->sendAsync($request)->wait();
            $response = json_decode($res->getBody(), true);
            //Log::info('Response from Walmart: ' . json_encode($response));
            return $response;
        } catch (RequestException $e) {
            // Log full error response (if available)
            if ($e->hasResponse()) {
                $status = $e->getResponse()->getStatusCode();
                $body = (string)$e->getResponse()->getBody();

                /*CriticalErrorService::report('Walmart API Error', [
                    'marketplace' => 'Walmart',
                    'reason' => $body ?? 'Unknown',
                ],['status' => $status]);*/

                Log::error("Walmart API error ({$status}): " . $body);
            } else {
                Log::error('Request error (no response): ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error refund order: ' . $e->getMessage());

            /*CriticalErrorService::report('Walmart Marketplace Refund Order failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() ?? 'Unknown',
            ],['purchase_order_id' => $purchaseOrderId]);*/
        }
        return null;

    }

    public function getItemSearch($upc)
    {
        try {
            $accessToken = $this->authenticate();

            if (!$accessToken) {
                Log::error("Failed to retrieve Walmart API token.");
                return null;
            }

            $url = $this->endpoint . "/items/walmart/search";
            $queryData = [
                'upc' => $upc
            ];

            Log::info('Query Data: ' . json_encode($queryData));

            $response = $this->client->get($url, [
                'verify' => false,
                'headers' => [
                    'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'WM_SVC.NAME' => 'Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID' => uniqid(),
                ],
                'query' => $queryData,
            ]);

            Log::info("getItemSearch Response: " . $response->getBody());

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return $error = [
                'error' => 'Walmart API request failed',
                'message' => $e->getMessage()
            ];
        }
    }

    public function fetchStoreCategories($feedType, $version)
    {
        Log::info('Fetching Walmart categories');
        $accessToken = $this->authenticate();

        $headers = [
            'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => uniqid(),
        ];

        $queryParams = [
            'feedType' => $feedType,
            'version' => $version
        ];

        // convert the query params to a query string
        $request = new Request('GET', "{$this->endpoint}/items/taxonomy?" . http_build_query($queryParams), $headers);
        $response = $this->client->sendAsync($request)->wait();

        $categories = json_decode($response->getBody(), true);
        Log::info('Walmart Categories fetched: ' . json_encode($categories));

        return $categories;
    }

    public function getProductSpecifications($feedType, $version, $categoryName)
    {
        Log::info('Fetching Walmart Specificatrion');
        $accessToken = $this->authenticate();

        $headers = [
            'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => uniqid(),
        ];

        $bodyParams = [
            'feedType' => $feedType,
            'version' => $version,
            'productTypes' =>[
                $categoryName
            ]
        ];

        // convert the body params to JSON
        $request = new Request('POST', "{$this->endpoint}/items/spec", $headers, json_encode($bodyParams));
        $response = $this->client->sendAsync($request)->wait();

        $categories = json_decode($response->getBody(), true);

        return $categories;
    }
}
