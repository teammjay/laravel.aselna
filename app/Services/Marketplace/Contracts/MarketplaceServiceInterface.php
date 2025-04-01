<?php

namespace App\Services\Marketplace\Contracts;

interface MarketplaceServiceInterface
{
    public function generateFeed($listingId,$feedId = null);

    public function submitFeed($feedId, $listingId);

    public function getResultFeed(mixed $listingId,$feedId = null);

    public function syncAttributes($store);
}
