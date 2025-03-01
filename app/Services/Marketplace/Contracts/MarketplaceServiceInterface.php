<?php

namespace App\Services\Marketplace\Contracts;

interface MarketplaceServiceInterface
{
    public function generateFeed($listingId);

    public function submitFeed($feedId, $listingId);

    public function getResultFeed(mixed $listingId);

    public function syncAttributes($store);
}
