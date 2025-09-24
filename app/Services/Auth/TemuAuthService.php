<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class TemuAuthService
{
    private $endpoint;
    private $appKey;
    private $appSecret;

    private $accessToken;

    public function __construct()
    {
        $this->endpoint = env('TEMU_API_ENDPOINT', 'https://openapi-b-us.temu.com/openapi/router');
        $this->appKey = env('TEMU_APP_KEY');
        $this->appSecret = env('TEMU_APP_SECRET');
        $this->accessToken = 'uplosijskhkphsp9k784eatdtk4eizqsegnmmekcslgyx1ni11jqzvjyxzu';
    }

    public function getCategories($parentId = 0)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.cats.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'parentCatId' => $parentId,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }


    private function generateSign(array $params, string $appSecret): string
    {
        // Remove 'sign' if already present
        unset($params['sign']);

        // Sort by key
        ksort($params);

        // Concatenate key-value pairs
        // value might be an array, so we need to handle that
        $queryString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $queryString .= $key . $value;
        }

        // Wrap with secret
        $rawString = $appSecret . $queryString . $appSecret;

        // MD5 + uppercase
        return strtoupper(md5($rawString));
    }

    public function submitFeed($data, $store)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $data['timestamp'] = time();
        $data['app_key'] = $this->appKey;
        $data['data_type'] = 'JSON';
        $data['access_token'] = $this->accessToken;
        $data['sign'] = $this->generateSign($data, $this->appSecret);

        Log::info('Submitting feed data: ' . json_encode($data));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($data));

        $res = $client->sendAsync($request)->wait();
        Log::info('Feed submission response: ' . $res->getBody());

        return json_decode($res->getBody(), true);
    }

    public function getTemplate($parentId)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.template.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'parentCatId' => $parentId,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }
}
