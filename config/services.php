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

];
