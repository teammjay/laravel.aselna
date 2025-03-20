<?php

namespace App\Services;

use Hashids\Hashids;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductManufacturerCode;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class SkuGenerator
{
    private $uniqueNumbers;
    private $longHashIds;
    private $oldShortHashIds;
    private $shortHashIds;
    private $mpnHashIds;
    private $storeUUId;
    private $skuType;

    public function __construct(Store $store, $uniqueNumbers)
    {
        $this->uniqueNumbers = $uniqueNumbers;
        $this->storeUUId = str_replace('-', '', $store->id);
        $this->skuType = $store->sku_generation_type;

        if ($this->skuType == 'HashId') {
            $this->longHashIds = new Hashids($this->storeUUId, 8, 'ABCDEFGHI1234567890');
        }

        $this->oldShortHashIds = new Hashids($this->storeUUId, 4, 'ABCDEFGHI1234567890');
        $this->shortHashIds = new Hashids($this->storeUUId, 4, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $this->mpnHashIds = new Hashids($this->storeUUId, 5, 'ABCDEFGHI1234567890');
    }

    public function getNextValue(StoreProduct $sp, $useExistingManufacturingCodes = true)
    {
        $store = $sp->store;
        $product = $sp->product;
        $productCode = $product->code;

        $manCodes = $useExistingManufacturingCodes ? $product->codes : null;

        $nextSeq = $this->uniqueNumbers->getNextNumber($this->storeUUId);
        $shortHash = $this->shortHashIds->encode($nextSeq);
        $shortHashForTitle = $this->getShortHashIdV2($shortHash, $nextSeq);

        $mpns = [];

        $noManufacturingCode = empty($manCodes);
        if ($noManufacturingCode) {
            $mpns[] = $this->mpnHashIds->encode($nextSeq);
        } else {
            foreach ($manCodes as $mc) {
                $mpns[] = $mc->code;
            }
        }

        foreach ($mpns as &$mpn) {
            if ($store->short_code) {
                $mpn .= '-' . $store->short_code;
            }
            if (!$noManufacturingCode) {
                $mpn .= '-' . $shortHash;
            }
        }

        $sku = null;
        if ($this->skuType == 'ProductCodeAndHashId') {
            $sku = $productCode . '-' . $shortHash;
        } elseif ($this->skuType == 'PRODUCT_CODE') {
            $sku = $productCode;
        } elseif ($this->skuType == 'HashId') {
            $sku = $this->longHashIds->encode($nextSeq);
        } else {
            $sku = (string)$nextSeq;
        }

        return new NextValue($nextSeq, $shortHash, $shortHashForTitle, $sku, $mpns);
    }

    private function getShortHashIdV2($shortHash, $index)
    {
        $res = 'XXX';
        $res[0] = $shortHash[0];
        $digit = $index % 10;
        if ($digit == 0) {
            $digit = 1;
        }
        $res[1] = (string)$digit;
        $res[2] = $shortHash[2];
        return $res;
    }
}

class NextValue
{
    private $index;
    private $shortHash;
    private $shortHashForTitle;
    private $sku;
    private $mpns;

    public function __construct($index, $shortHash, $shortHashForTitle, $sku, $mpns)
    {
        $this->index = $index;
        $this->shortHash = $shortHash;
        $this->shortHashForTitle = $shortHashForTitle;
        $this->sku = $sku;
        $this->mpns = $mpns;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function getShortHash()
    {
        return $this->shortHash;
    }

    public function getMpns()
    {
        return $this->mpns;
    }

    public function getMpn()
    {
        return implode('¬', $this->mpns);
    }

    public function getShortHashForTitle()
    {
        return $this->shortHashForTitle;
    }
}