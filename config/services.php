<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shopify' => [
        // Shopify stores whose finished feed dispatches SyncShopifyDeviceSkuMetafieldsJob.
        // Comma separated store ids; NevaWireless (shopify) only for now. Manual runs are not limited by this.
        'device_sku_feed_store_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'SHOPIFY_DEVICE_SKU_FEED_STORE_IDS',
            'c2c8131f-c8a2-e082-9f89-3e8bf96b1049'
        ))))),
    ],

    'ebay' => [
        // When a create/resubmit result feed reports an item as failed, ask eBay (GetItem by SKU)
        // whether the listing was created anyway, and count it as a success if it was.
        'verify_failed_items' => (bool) env('EBAY_VERIFY_FAILED_ITEMS', true),
        // Log what the GetItem check would change without updating any data.
        'verify_dry_run' => (bool) env('EBAY_VERIFY_DRY_RUN', false),
        // Most GetItem lookups per feed per result check; the rest wait for the next check.
        'verify_max_per_run' => (int) env('EBAY_VERIFY_MAX_PER_RUN', 50),
    ],

];
