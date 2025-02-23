<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
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
        $this->endpoint = env('MAGENTO_API_ENDPOINT');
    }

    public function authenticate()
    {
        Log::info('Authenticating Magento');
        Log::info('Client ID: ' . $this->clientId);
        Log::info('Client Secret: ' . $this->clientSecret);
        Log::info('Endpoint: ' . $this->endpoint);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $body = [
            'username' => $this->clientId,
            'password' => $this->clientSecret,
        ];

        Log::info("Headers: " . json_encode($headers));
        Log::info("Body: " . json_encode($body));

        $request = new Request('POST', $this->endpoint . '/rest/V1/integration/admin/token', $headers);
        $res = $this->client->sendAsync($request, ['json' => $body])->wait();

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

    public function getBulkStatus($feed_id)
    {
        //call the response api /rest/V1/bulk/:bulkUuid/status
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
}
