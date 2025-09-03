<?php

namespace App\Helpers;

use App\Models\Catalog;

class Common
{
    public static function calculateProductWeight($orderOperation){

        $product_total_weight = 0;
        if (count($orderOperation->operationItem ?? []) > 0) {
            $product_weight = 0;
            foreach ($orderOperation->operationItem as $oitem) {
                if (isset($oitem->orderItem->product)) {
                    $product_id = $oitem->orderItem->product->product_id;
                    if ($product_id) {
                        $product_detail = Catalog::findOrFail($product_id);
                        $product_weight = $product_detail->product_weight * $oitem->quantity;
                        $product_total_weight = $product_total_weight + $product_weight;
                    }
                }
            }
        }
        return $product_total_weight;
    }
}
