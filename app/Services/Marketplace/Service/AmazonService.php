<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;

class AmazonService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id',$listingId)->first();
        // Generate feed for Amazon
        $feed = [
            'headers' => [
                'DocumentVersion' => '1.01',
                'MerchantIdentifier' => 'M_EXAMPLE_123456',
            ],
            'MessageType' => 'Product',
        ];

        $xmlContent =  XmlUtility::toXml($feed, 'AmazonEnvelope');

        XmlUtility::saveToFile('feeds/'.$listingId . '.xml', $xmlContent);
    }
}
