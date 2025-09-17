<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Constants\FeedTypes;

class NewEggAuthService
{
    protected $client;
    protected $baseUrl;
    protected $sellerId;
    protected $apiKey;
    protected $secretKey;

    public function __construct($clientId, $clientSecret,$redirectUri,$refresh_token='')
    {
        $this->baseUrl  = env('NEWEGG_BASE_URL'); // e.g. https://api.newegg.com/marketplace/
        $this->sellerId = config('NEWEGG_SELLER_ID');
        $this->apiKey   = config('NEWEGG_API_KEY');
        $this->secretKey = config('NEWEGG_SECRET_KEY');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);

    }

    /**
     * Send request to Newegg API
     */
    protected function request($method, $endpoint, $query = [], $body = [])
    {
        try {
            $response = $this->client->request($method, $endpoint, [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'SecretKey'     => $this->secretKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'sellerid' => $this->sellerId
                ],
                'json' => $body,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error("Newegg API Error: " . $e->getMessage());
            return null;
        }
    }

    public function submitFeed(array $body)
    {
        $endpoint = "datafeedmgmt/feeds/submitfeed";
        $query = [
            'sellerid'    => $this->sellerId,
            'requesttype' => 'ITEM_DATA',
        ];

        try {

            $response = $this->request('POST',$endpoint,$query,$body);

            $respBody = $response->getBody()->getContents();
            return json_decode($respBody, true);
        } catch (\Exception $e) {
            Log::error("Newegg submitItemCreationUpdateFeed error: " . $e->getMessage());
            return null;
        }
    }
}
