<?php

namespace Tests\Services\Auth;

use App\Models\Store;
use App\Services\Auth\EbayAuthService;
use Tests\TestCase;

/**
 * Manual helper, not an automated test: it calls the live eBay OAuth endpoint.
 *
 * Prints a fresh user access token (valid ~2 hours) for the awaccessory eBay store, for calling the
 * eBay APIs from Postman, e.g. Trading API GetMyeBaySelling with header X-EBAY-API-IAF-TOKEN: <token>.
 *
 *   ./vendor/bin/phpunit --filter testGenerateAccessToken tests/Services/Auth/EbayAuthServiceTest.php
 *
 * Another store: EBAY_STORE_NAME=phonil ./vendor/bin/phpunit --filter testGenerateAccessToken tests/Services/Auth/EbayAuthServiceTest.php
 */
class EbayAuthServiceTest extends TestCase
{
    public function testGenerateAccessToken()
    {
        $storeName = getenv('EBAY_STORE_NAME') ?: 'awaccessory';

        $store = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'ebay')
            ->where('store.name', $storeName)
            ->where('store.store_status', 1)
            ->select('store.*')
            ->first();

        $this->assertNotNull($store, "Active eBay store '{$storeName}' not found");

        // Same credentials as the eBay jobs; the store's refresh token is kept in store.user_id
        $ebayAuthService = new EbayAuthService(
            env('EBAY_CLIENT_ID'),
            env('EBAY_CLIENT_SECRET'),
            env('EBAY_REDIRECT_URI'),
            $store->user_id
        );

        // refreshToken() is private; it exchanges the refresh token for a new access token
        $refreshToken = new \ReflectionMethod(EbayAuthService::class, 'refreshToken');
        $refreshToken->setAccessible(true);
        $accessToken = $refreshToken->invoke($ebayAuthService);

        $this->assertIsString($accessToken, 'eBay token refresh failed, see storage/logs/laravel.log');

        $accessToken = trim($accessToken);

        // The token is ~2000 chars, so copying it from a wrapped terminal line picks up line breaks,
        // which Postman rejects ("Invalid character in header content"). Put it on the clipboard instead.
        $copied = false;
        if (PHP_OS_FAMILY === 'Darwin' && ($clipboard = popen('pbcopy', 'w'))) {
            fwrite($clipboard, $accessToken);
            $copied = pclose($clipboard) === 0;
        }

        fwrite(STDOUT, PHP_EOL . "eBay access token for {$store->name} (valid ~2 hours, " . strlen($accessToken) . ' chars)'
            . ($copied ? ' - copied to clipboard, paste it into Postman' : '') . ':' . PHP_EOL . $accessToken . PHP_EOL);
    }
}
