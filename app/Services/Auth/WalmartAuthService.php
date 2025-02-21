<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
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
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ];

        Log::info("Headers: " . json_encode($headers));
        Log::info("Options: " . json_encode($options));

        $request = new Request('POST', 'https://marketplace.walmartapis.com/v3/token', $headers);
        $res = $this->client->sendAsync($request, $options)->wait();
        Log::info('Response: ' . $res->getBody());

        $data = json_decode($res->getBody(), true);

        return $data['access_token'];
    }

    public function submitFeed()
    {
        $accessToken = $this->authenticate();
        Log::info('Access Token Created ' . $accessToken);
        return $accessToken;

//        $response = $this->client->post("{$this->endpoint}/feeds", [
//            'headers' => [
//                'Authorization' => "Bearer {$accessToken}",
//                'Content-Type' => 'application/json',
//                'WM_SVC.NAME' => 'Walmart Marketplace',
//                'WM_QOS.CORRELATION_ID' => uniqid(),
//            ],
//            'body' => $jsonPayload,
//        ]);
//
//        return json_decode($response->getBody(), true);
    }

    public function getFeedStatus($feedId)
    {
        $accessToken = $this->authenticate();

        $headers = [
            'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => uniqid(),
        ];

        $request = new Request('GET', "{$this->endpoint}/feeds/{$feedId}", $headers);
        $response = $this->client->sendAsync($request)->wait();

        return json_decode($response->getBody(), true);
    }

    public function getOrders()
    {
        try {
            $accessToken = $this->authenticate();

            if (!$accessToken) {
                Log::error("Failed to retrieve Walmart API token.");
                return null;
            }

            $url = "https://marketplace.walmartapis.com/v3/orders";

            $response = $this->client->get($url, [
                'headers' => [
                    'WM_SEC.ACCESS_TOKEN' => "{$accessToken}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'WM_SVC.NAME' => 'Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID' => uniqid(),
                ],
                'query' => [
                    'createdStartDate' => now()->subDays(7)->toISOString(),
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Walmart API request failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
