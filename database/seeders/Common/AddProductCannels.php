<?php

namespace Database\Seeders\Common;

use App\Models\Catalog;
use App\Models\ProductChannelDetail;
use App\Models\ProductType;
use App\Models\StoreType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddProductCannels extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $all_product_ids = [];

        $product_sub_types = ProductType::select('id', 'code')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->get()->toArray();
        if (!empty($product_sub_types)) {
            foreach ($product_sub_types as $stype) {
                if ($stype['code'] === 'ceabundle') continue;
                $all_product_ids[] = $stype['id'];
                $product_sub_sub_types = ProductType::select('id')->where('parent_id', $stype['id'])->get()->toArray();
                if (!empty($product_sub_sub_types)) {
                    foreach ($product_sub_sub_types as $ssubtype) {
                        $all_product_ids[] = $ssubtype['id'];
                    }
                }
            }
        }

        $query = Catalog::where('products.product_status', 1);

        if (!empty($all_product_ids)) {
            $query->whereIn('products.product_type', $all_product_ids);
        }

        $storetypes = StoreType::all();

        $query->chunk(100, function ($products) use ($storetypes) {
            foreach ($products as $product) {
                $this->enableProductToStoreType($product,$storetypes);
            }
        });
    }

    private function enableProductToStoreType(mixed $product,$storetypes)
    {
        foreach ($storetypes as $storetype) {
            ProductChannelDetail::updateOrCreate([
                'store_type_id' => $storetype->id,
                'product_id' => $product->product_id,
            ],[
                'store_type_id' => $storetype->id,
                'product_id' => $product->product_id,
                'status' => 1
            ]);
        }
    }
}
