<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;

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
        $response = $this->client->post("{$this->endpoint}/token", [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['access_token'];
    }

    public function submitFeed($jsonPayload)
    {
        $accessToken = $this->authenticate();

        $response = $this->client->post("{$this->endpoint}/feeds", [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
                'WM_SVC.NAME' => 'Walmart Marketplace',
                'WM_QOS.CORRELATION_ID' => uniqid(),
            ],
            'body' => $jsonPayload,
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getFeedStatus($feedId)
    {
        $accessToken = $this->authenticate();

        $response = $this->client->get("{$this->endpoint}/feeds/{$feedId}", [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
                'WM_SVC.NAME' => 'Walmart Marketplace',
                'WM_QOS.CORRELATION_ID' => uniqid(),
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
