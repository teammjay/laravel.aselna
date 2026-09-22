<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a listing item detail needs a UPC and the store has no unused one left.
 * Feed generation stops on it rather than writing rows without a UPC.
 */
class UpcNotAvailableException extends RuntimeException
{
    public function __construct(
        public readonly ?string $storeId,
        public readonly ?string $storeName,
        public readonly ?string $listingItemDetailId = null
    ) {
        parent::__construct(
            'No UPC available for store ' . ($storeName ?: $storeId)
            . '. Feed generation was stopped. Please generate UPC codes for this store and generate the feed again.'
        );
    }
}
