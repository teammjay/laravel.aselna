<?php

namespace App\Services\Marketplace;

use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Services\Marketplace\Exceptions\MarketplaceNotSupportedException;
use App\Services\Marketplace\Service\AmazonService;
use App\Services\Marketplace\Service\MagentoService;
use App\Services\Marketplace\Service\ShopifyService;
use App\Services\Marketplace\Service\TemuService;
use App\Services\Marketplace\Service\WalmartService;
use App\Services\Marketplace\Service\EbayService;
use App\Services\Marketplace\Service\GoogleShoppingService;

class MarketplaceServiceFactory
{
    public static function create(string $marketplace): MarketplaceServiceInterface {
        $marketplaceClass = match (strtolower($marketplace)) {
            'amazon' => AmazonService::class,
            'walmart' => WalmartService::class,
            'ebay' => EbayService::class,
            'google shopping ads' => GoogleShoppingService::class,
            'magento' => MagentoService::class,
            'shopify' => ShopifyService::class,
            'temu' => TemuService::class,
            default => null,
        };

        if (!$marketplaceClass || !class_exists($marketplaceClass)) {
            throw new MarketplaceNotSupportedException("Marketplace '{$marketplace}' is not supported.");
        }

        return new $marketplaceClass();
    }
}

/*
 * $amazonFactory = MarketplaceServiceFactory.create('amazon');
 * $feed = $amazonFactory->generateFeed();
 *
 * $shopifyFactory = MarketplaceServiceFactory.create('shopify');
 * $feed = $shopifyFactory->generateFeed();
 */
