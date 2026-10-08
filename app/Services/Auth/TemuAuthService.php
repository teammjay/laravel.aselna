<?php

namespace App\Services\Auth;

use App\Services\CriticalErrorService;
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
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Category API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['endpoint'=>$this->endpoint]);

            Log::error('Temu Category API Error: ' . $e->getMessage());

        }
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
        try {
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
        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Submit Feed Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['data'=> $data]);

            Log::error('Temu Submit Feed Error: ' . $e->getMessage());

        }
    }

    public function getTemplate($parentId)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Template Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parent_id'=> $parentId]);

            Log::error('Temu Get Template Error: ' . $e->getMessage());

        }
    }

    public function compiliancePropertyCheck()
    {

    }

    public function geShippingTemplate()
    {
        try{
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
        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Shipping Template Error', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Temu Get Shipping Template Error: ' . $e->getMessage());

        }
    }

    public function getOrders(string $filterStartDateFormatted, $cursor)
    {
        try {
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
            $filterEndDateFormatted = (int)time();

            if (isset($cursor)) {

            } else {
                $params['createAfter'] = $filterStartDateFormatted;
                $params['createBefore'] = $filterEndDateFormatted;
            }

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Request Params: ' . json_encode($params));
            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));
            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Order Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['start_date'=> $filterStartDateFormatted,'cursor'=>$cursor]);

            Log::error('Temu Get Order Error: ' . $e->getMessage());

        }
    }

    public function getOrderInfo($parentOrderSn)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Order Info Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrdersn'=> $parentOrderSn]);

            Log::error('Temu Get Order Info Error: ' . $e->getMessage());

        }
    }

    public function uploadImage(string $imagesUrl)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Upload Image Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['image_url'=> $imagesUrl]);

            Log::error('Temu Upload Image Error: ' . $e->getMessage());

        }
    }

    public function getFeedResult($outSkunList)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Get Result Feed Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['outSkunList'=> $outSkunList]);

            Log::error('Temu Get Result Feed Error: ' . $e->getMessage());

        }
    }

    public function getGoodsInfo($GoodsId)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Goods Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['GoodsId'=> $GoodsId]);

            Log::error('Temu Goods Info API Error: ' . $e->getMessage());

        }
    }

    public function createSpecOption($category, mixed $parentSpecId, $value)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Specs Option API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['category'=> $category,'parentSpecId'=>$parentSpecId, 'value'=>$value]);

            Log::error('Temu Specs Option API Error: ' . $e->getMessage());

        }
    }

    public function getShippingInfo(mixed $parentOrderSn)
    {
        try {
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
        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Shipping Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrderSn'=> $parentOrderSn]);

            Log::error('Temu Shipping Info API Error: ' . $e->getMessage());

        }
    }

    public function getLogisticsInfo($regionId)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Logistic Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['regionId'=> $regionId]);

            Log::error('Temu Logistic Info API Error: ' . $e->getMessage());

        }
    }

    public function callOrderShipment($data)
    {
        try {
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

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Order Shipment Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['data'=> $data]);

            Log::error('Temu Order Shipment Error: ' . $e->getMessage());

        }
    }

    /**
     * Change the carrier and tracking number of packages already shipped (bg.logistics.shipment.shippingtype.update).
     * Each entry is ['packageSn' => ..., 'trackingNumber' => ..., 'shipCompanyId' => ...].
     */
    public function updateShipmentTracking(array $editPackageRequestList)
    {
        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $params = [
                'type' => 'bg.logistics.shipment.shippingtype.update',
                'app_key' => $this->appKey,
                'timestamp' => time(),
                'editPackageRequestList' => $editPackageRequestList,
                'data_type' => 'JSON',
                'access_token' => $this->accessToken,
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Update Shipment Tracking Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        } catch (\Throwable $e) {

            CriticalErrorService::report('Temu Update Shipment Tracking Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['editPackageRequestList' => $editPackageRequestList]);

            Log::error('Temu Update Shipment Tracking Error: ' . $e->getMessage());

            return ['success' => false, 'errorMsg' => $e->getMessage()];
        }
    }

    public function getShipmentInfo($parentOrderSn,$orderSn)
    {
        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $categoryType = 'bg.logistics.shipment.v2.get';
            $timestamp = time();

            $params = [
                'type' => $categoryType,
                'app_key' => $this->appKey,
                'timestamp' => $timestamp,
                'parentOrderSn' => $parentOrderSn,
                'orderSn' => $orderSn,
                'data_type' => 'JSON',
                'access_token' => $this->accessToken,
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Request Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        }catch (\Throwable $e) {

            CriticalErrorService::report('Temu Shipment Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrderSn'=> $parentOrderSn,'orderSn'=>$orderSn]);

            Log::error('Temu Shipment Info Error: ' . $e->getMessage());

        }
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

        }catch (\Throwable $e){

            CriticalErrorService::report('Temu Carrier Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['regionId'=> $regionId]);

            Log::info('Temu carier info error : ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getWarehouseInfo()
    {
        try {
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

        }catch (\Throwable $e){

            CriticalErrorService::report('Temu Warehouse Info API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::info('Temu carier info error : ' . $e->getMessage());
        }
    }

    public function issueRefund($parentAfterSalesSn, $parentOrderSn){

        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $apiType = 'temu.aftersales.refund.issue';
            $timestamp = time();

            $params = [
                'type' => $apiType,
                'app_key' => $this->appKey,
                'access_token' => $this->accessToken,
                'timestamp' => $timestamp,
                'data_type' => 'JSON',
                'parentAfterSalesSn' => $parentAfterSalesSn,
                'parentOrderSn' => $parentOrderSn,
                'openApiRefundType' => 1,
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Issue Refund Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        }catch (\Throwable $e){

            CriticalErrorService::report('Temu Refund API Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentAfterSalesSn'=> $parentAfterSalesSn,'parentOrderSn'=>$parentOrderSn]);

            Log::info('Temu issue refund error : ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getAfterSalesList($parentOrderSn = []){

        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $apiType = 'bg.aftersales.parentaftersales.list.get';
            $timestamp = time();

            $params = [
                'type' => $apiType,
                'app_key' => $this->appKey,
                'access_token' => $this->accessToken,
                'timestamp' => $timestamp,
                'data_type' => 'JSON',
                'parentOrderSnList' => $parentOrderSn,
                'pageSize' => 200,
                'pageNo' => 1,
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('After Sales Parent After Sales List Request Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        }catch (\Exception $e){

            CriticalErrorService::report('Temu Get After Sales List Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrderSn'=> $parentOrderSn]);

            Log::info('Temu Sales Parent After Sales List Error : ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function cancelOrder($parentOrderSn, $reason = 'CUSTOMER REQUEST'){
        try {
            $client = new Client();
            $headers = [
                'content-type' => 'application/json',
            ];

            $apiType = 'temu.order.cancel.appeal.apply';
            $timestamp = time();

            $params = [
                'type' => $apiType,
                'app_key' => $this->appKey,
                'access_token' => $this->accessToken,
                'timestamp' => $timestamp,
                'data_type' => 'JSON',
                'cancelType' => 2,
                'applyOrder' => [
                    'parentOrderSn' => $parentOrderSn,
                ],
                'reason' => [
                    'description' => $reason,
                    'proofUrlList' => '',
                ]
            ];

            $params['sign'] = $this->generateSign($params, $this->appSecret);

            Log::info('Cancel Order Apply Request Params: ' . json_encode($params));

            $request = new Request('POST', $this->endpoint, $headers, json_encode($params));

            $res = $client->sendAsync($request)->wait();

            return json_decode($res->getBody(), true);

        }catch (\Exception $e){

            CriticalErrorService::report('Temu Cancel Order Failed', [
                'marketplace' => 'Temu',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['parentOrderSn'=> $parentOrderSn,'reason'=>$reason]);

            Log::info('Temu Cancel Order Apply Error : ' . $e->getMessage());
            return $e->getMessage();
        }
    }
}
