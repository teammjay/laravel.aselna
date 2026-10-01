<?php

namespace App\Constants;

/**
 * Base name of a feed's part files: feed_1.csv, feed_product_1.csv, feed_device_1.csv. A run only
 * replaces the parts of its own prefix, so feeds for different filters can sit side by side.
 */
class FeedFilePrefix
{
    const FEED = 'feed';
    const PRODUCT = 'feed_product';
    const DEVICE = 'feed_device';

    const names = [
        self::FEED => 'feed',
        self::PRODUCT => 'feed_product',
        self::DEVICE => 'feed_device',
    ];

    /**
     * The prefix to use for a feed. The product and device prefixes are only allowed with their
     * filter set; anything else falls back to "feed".
     */
    public static function resolve(?string $prefix, $productCodes, $devices): string
    {
        return match ($prefix) {
            self::PRODUCT => !empty($productCodes) ? self::PRODUCT : self::FEED,
            self::DEVICE => !empty($devices) ? self::DEVICE : self::FEED,
            default => self::FEED,
        };
    }

    public static function isValid(?string $prefix): bool
    {
        return array_key_exists((string)$prefix, self::names);
    }
}
