<?php

namespace Database\Seeders;

use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreCategoryImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreCategoryImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $query = Store::where('store_status', '<>', 0);
        $store_ids = $query->pluck('id');
        $productTypes = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->where('code','!=','ceabundle')->orderBy('name', 'asc')->pluck('id');

        if(!empty($store_ids)){
            foreach($store_ids as $store_id){
                if(!empty($productTypes)){
                    foreach($productTypes as $ptype){

                        StoreCategoryImage::updateOrCreate(
                            [
                                'store_id' => $store_id,
                                'category_id' => $ptype,
                            ],
                            [
                                'store_id' => $store_id,
                                'category_id' => $ptype
                            ]
                        );
                    }
                }
            }
        }
    }
}
