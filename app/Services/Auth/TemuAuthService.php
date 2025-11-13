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

    public function __construct($endpoint = null, $appKey = null, $appSecret = null)
    {
        $this->endpoint = $endpoint ?? env('TEMU_API_ENDPOINT', 'https://openapi-b-us.temu.com/openapi/router');
        $this->appKey = $appKey ?? env('TEMU_APP_KEY');
        $this->appSecret = $appSecret ?? env('TEMU_APP_SECRET');
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

        $data['type'] = 'bg.local.goods.add';
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
            'catId' => $parentId,
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

    public function compiliancePropertyCheck()
    {

    }

    public function geShippingTemplate()
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.freight.template.list.query';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
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

    public function getOrders(string $filterStartDateFormatted, $cursor)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.order.list.v2.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        if (isset($cursor)) {

        } else {
            $params['createAfter'] = $filterStartDateFormatted;
        }

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));
        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));
        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function getOrderInfo($parentOrderSn)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.order.detail.v2.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'parentOrderSn' => $parentOrderSn,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function uploadImage(string $imagesUrl)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.image.upload';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'fileUrl' => $imagesUrl,
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

    public function getFeedResult($outSkunList)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.sku.out.sn.check';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'outSkuSnList' => $outSkunList,
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

    public function getGoodsInfo($GoodsId)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.detail.query';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'goodsId' => $GoodsId,
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

    public function createSpecOption($category, mixed $parentSpecId, $value)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.local.goods.spec.id.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'catId' => $category,
            'parentSpecId' => $parentSpecId,
            'childSpecName' => $value,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function getShippingInfo(mixed $parentOrderSn)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.order.shippinginfo.v2.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'parentOrderSn' => $parentOrderSn,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function getLogisticsInfo($regionId)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.logistics.companies.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
            'regionId' => $regionId,
            'data_type' => 'JSON',
            'access_token' => $this->accessToken,
        ];

        $params['sign'] = $this->generateSign($params, $this->appSecret);

        Log::info('Request Params: ' . json_encode($params));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function callOrderShipment($data)
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $data['type'] = 'bg.logistics.shipment.v2.confirm';
        $data['timestamp'] = time();
        $data['app_key'] = $this->appKey;
        $data['data_type'] = 'JSON';
        $data['access_token'] = $this->accessToken;
        $data['sign'] = $this->generateSign($data, $this->appSecret);

        Log::info('Request Params: ' . json_encode($data));

        $request = new Request('POST', $this->endpoint, $headers, json_encode($data));

        $res = $client->sendAsync($request)->wait();

        return json_decode($res->getBody(), true);
    }

    public function getCarrierInfo($regionId = 'US')
    {
        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $apiType = 'bg.logistics.companies.get';
            $timestamp = time();

            $params = [
                'type' => $apiType,
                'app_key' => $this->appKey,
                'access_token' => $this->accessToken,
                'timestamp' => $timestamp,
                'data_type' => 'JSON',
                'regionId' => $regionId
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Carrier Request Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        }catch (\Exception $e){
            Log::info('Temu carier info error : ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getWarehouseInfo()
    {
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
        ];

        $categoryType = 'bg.logistics.warehouse.list.get';
        $timestamp = time();

        $params = [
            'type' => $categoryType,
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
