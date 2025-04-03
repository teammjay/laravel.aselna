<?php

namespace App\Services\Auth;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Utils\FileUtility;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AmazonAuthService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://sellingpartnerapi-na.amazon.com',
        ]);
    }

    public function getAccessToken()
    {
        $client = new Client([
            'base_uri' => 'https://api.amazon.com',
        ]);

        $response = $client->post('/auth/o2/token', [
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

        $response = $this->client->post('/feeds/2021-06-30/documents', [
            'headers' => [
                'x-amz-access-token' => "{$accessToken}",
            ],
            'json' => [
                'contentType' => 'text/xml; charset=UTF-8',
            ]
        ]);
        Log::info('Feed Upload Response: ' . $response->getBody());

        $uploadDetails = json_decode($response->getBody(), true);
        $uploadUrl = $uploadDetails['url'];
        Log::info('Upload URL: ' . json_encode($uploadUrl));

        $this->client->put($uploadUrl, [
            'headers' => ['Content-Type' => 'text/xml; charset=UTF-8'],
            'body' => $feedContent,
        ]);
        Log::info('Feed Content Uploaded');

        return $uploadDetails['feedDocumentId'];
    }

    public function submitFeed(mixed $generatedFeedId, $listingId)
    {
        $accessToken = $this->getAccessToken();
        Log::info('Access Token Created ' . $accessToken);

        $marketplaceIDs = $this->getMarketplaceIDs();
        Log::info('Marketplace IDs: ' . json_encode($marketplaceIDs));

        $feedType = $this->getFeedType($listingId);
        Log::info('Feed Type: ' . $feedType);

        $data = [
            'marketplaceIds' => $marketplaceIDs,
            'feedType' => $feedType,
            'inputFeedDocumentId' => $generatedFeedId,
        ];
        Log::info('Feed Submission Data: ' . json_encode($data));

        $response = $this->client->post('/feeds/2021-06-30/feeds', [
            'headers' => [
                'x-amz-access-token' => "{$accessToken}",
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        Log::info('Feed Submission Response: ' . $response->getBody());

        return json_decode($response->getBody(), true);
    }

    public function getResultFeed($feedId, $listingId)
    {
        $accessToken = $this->getAccessToken();
        $savePath = public_path('result-feeds/amazon/'.$listingId.'.zip');

        $response = $this->client->get('/feeds/2021-06-30/feeds/' . $feedId, [
            'headers' => [
                'x-amz-access-token' => "{$accessToken}",
            ]
        ]);

        $feedResult = json_decode($response->getBody(), true);
        Log::info('Feed Result: ' . json_encode($feedResult));
        $feedDocumentId = $feedResult['resultFeedDocumentId'];

        $response = $this->client->get('/feeds/2021-06-30/documents/' . $feedDocumentId, [
            'headers' => [
                'x-amz-access-token' => "{$accessToken}",
            ]
        ]);

        $documentResult = json_decode($response->getBody(), true);
        $doocumentUrl = $documentResult['url'];

        Log::info('Document URL: '. $doocumentUrl);

        if(isset($documentResult['compressionAlgorithm']) && $documentResult['compressionAlgorithm'] == 'GZIP') {

            $this->client->get($doocumentUrl, [
                'sink' => $savePath,
            ]);

            $compressedContent = file_get_contents($savePath);
            $decompressedContent = gzdecode($compressedContent);

            if ($decompressedContent === false) {
                Log::error('Failed to decompress GZIP content for feed document ' . $feedDocumentId);
                return null;
            }

            FileUtility::saveToFile(public_path('result-feeds/amazon/' . $listingId . '.json'), $decompressedContent);
            return 'result-feeds/amazon/'. $listingId. '.json';
        } else {
            $response = $this->client->get($doocumentUrl, []);
            Log::info('Feed Document: '. $response->getBody());
            FileUtility::saveToFile(public_path('result-feeds/amazon/' . $listingId . '.xml'), $response->getBody());
            return 'result-feeds/amazon/'. $listingId. '.xml';
        }
    }

    private function getMarketplaceIDs()
    {
        $accessToken = $this->getAccessToken();

        $response = $this->client->get('/sellers/v1/marketplaceParticipations', [
            'headers' => [
                'Accept' => 'application/json',
                'x-amz-access-token' => "{$accessToken}",
            ]
        ]);

        $marketplaces = json_decode($response->getBody()->getContents(), true);
        Log::info('Marketplaces: ' . json_encode($marketplaces));

        $marketplaceIDs = [];
        foreach ($marketplaces['payload'] as $marketplace) {
            if($marketplace['marketplace']['domainName'] == 'www.amazon.com') {
                $marketplaceIDs[] = $marketplace['marketplace']['id'];
            }
        }
        return $marketplaceIDs;
    }

    private function getFeedType($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();

        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                return 'JSON_LISTINGS_FEED';
            case FeedTypes::PRICE:
                return 'POST_PRODUCT_PRICING_DATA';
            case FeedTypes::INVENTORY:
                return 'POST_INVENTORY_AVAILABILITY_DATA';
            case FeedTypes::IMAGE_UPDATE:
                return 'POST_PRODUCT_IMAGE_DATA';
            default:
                return 'POST_PRODUCT_DATA';
        }
    }

    public function getOrders()
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return "Error: Could not retrieve access token.";
            }

            $marketplaceIDs = $this->getMarketplaceIDs();
            Log::info('Marketplace IDs: ' . json_encode($marketplaceIDs));

            $response = $this->client->get('/orders/v0/orders', [
                'headers' => [
                    'x-amz-access-token' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'x-amz-date' => gmdate('Ymd\THis\Z'),
                ],
                'query' => [
                    'MarketplaceIds' => implode(',',$marketplaceIDs),
                    'CreatedAfter' => now()->subDays(7)->toISOString(),
                ],
            ]);

            Log::info('Order Response'.$response->getBody());

            $data = json_decode($response->getBody(), true);

            return $data['payload']['Orders'] ?? [];

        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Amazon API request failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrderItems($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return response()->json(['error' => 'Unable to retrieve access token'], 401);
            }

            $response = $this->client->get("/orders/v0/orders/{$orderId}/orderItems", [
                'headers' => [
                    'x-amz-access-token' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'x-amz-date' => gmdate('Ymd\THis\Z'),
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['payload']['OrderItems'] ?? [];

        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Amazon API request failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // getProducttypes
    public function getProductTypes($productType)
    {
        $marketPlaceID = $this->getMarketplaceIDs();
        $accessToken = $this->getAccessToken();
        Log::info('Access Token Created ' . $accessToken);

        ///definitions/2020-09-01/productTypes?marketplaceIds=ATVPDKIKX0DER&keywords=Wireless&locale=en_US&searchLocale=en_US
        $response = $this->client->get('/definitions/2020-09-01/productTypes', [
            'headers' => [
                'x-amz-access-token' => "{$accessToken}",
                'Content-Type' => 'application/json',

            ],
            'query' => [
                'marketplaceIds' => implode(',', $marketPlaceID),
                'keywords' => $productType,
                'locale' => 'en_US',
                'searchLocale' => 'en_US',
            ]
        ]);

        Log::info('Product Type Response: ' . $response->getBody());

        return json_decode($response->getBody(), true);
    }

}
