<?php

namespace Database\Seeders;

use App\Models\ProductSpecification;
use App\Models\StoreType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductSpecificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $storeTypes = StoreType::whereIn('code',['ebay','walmart','shopify','magento','amazon','googleShopping'])->get();
        foreach ($storeTypes as $storeType) {
            ProductSpecification::whereNull('store_type_id')->whereNotNull('product_id')->chunk(100, function ($productSpecifications) use ($storeType) {
                foreach ($productSpecifications as $productSpecification) {
                    ProductSpecification::updateOrCreate([
                        'store_type_id' => $storeType->id,
                        'product_id' => $productSpecification->product_id,
                        'name' => $productSpecification->name,
                    ],[
                        'name' => $productSpecification->name,
                        'value' => $productSpecification->value,
                        'product_id' => $productSpecification->product_id,
                        'created_at' => $productSpecification->created_at,
                        'updated_at' => $productSpecification->updated_at,
                        'store_type_id' => $storeType->id,
                    ]);
                }
            });
        }
    }
}
