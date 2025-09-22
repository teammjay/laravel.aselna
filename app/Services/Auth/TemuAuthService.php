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

    public function __construct()
    {
        $this->endpoint = env('TEMU_API_ENDPOINT', 'https://openapi-b-us.temu.com/openapi/router');
        $this->appKey = env('TEMU_APP_KEY');
        $this->appSecret = env('TEMU_APP_SECRET');
    }

    public function getCategories($parentId = 0)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $accessToken = 'uplosijskhkphsp9k784eatdtk4eizqsegnmmekcslgyx1ni11jqzvjyxzu';

        $categoryType = 'bg.local.goods.cats.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'parentCatId' => $parentId,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'data_type' => 'JSON',
            'access_token' => $accessToken,
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
        $queryString = '';
        foreach ($params as $key => $value) {
            $queryString .= $key . $value;
        }

        // Wrap with secret
        $rawString = $appSecret . $queryString . $appSecret;

        // MD5 + uppercase
        return strtoupper(md5($rawString));
    }
}
