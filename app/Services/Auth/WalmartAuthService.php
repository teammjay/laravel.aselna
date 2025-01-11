<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;

class WalmartAuthService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $endpoint;

    public function __construct()
    {
        $this->client = new Client();
        $this->clientId = env('WALMART_CLIENT_ID');
        $this->clientSecret = env('WALMART_CLIENT_SECRET');
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
}
