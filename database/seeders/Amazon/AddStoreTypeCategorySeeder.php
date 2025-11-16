<?php

namespace Database\Seeders\Amazon;

use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddStoreTypeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storeTypeCategories = [
            ['name' => 'cable-or-adapters', 'marketplace_code' => 'CABLE_OR_ADAPTER'],
            ['name' => 'stylus', 'marketplace_code' => 'INPUT_PEN'],
            ['name' => 'usb-hubs', 'marketplace_code' => 'MULTIPORT_HUB'],
            ['name' => 'keyboards', 'marketplace_code' => 'KEYBOARDS'],
            ['name' => 'speakers', 'marketplace_code' => 'SPEAKERS']
        ];

        $storeType = StoreType::where('code', 'amazon')->first();
        $parent = StoreTypeCategory::where('store_type_id', $storeType->id)
            ->where('code','WirelessAccessories')
            ->first();

        foreach ($storeTypeCategories as $category) {
            StoreTypeCategory::updateOrCreate(
                [
                    'name' => $category['name'],
                    'marketplace_code' => $category['marketplace_code'],
                    'store_type_id' => $storeType->id
                ],
                [
                    'name' => $category['name'],
                    'code' => $category['name'],
                    'store_type_code' => $category['name'],
                    'marketplace_code' => $category['marketplace_code'],
                    'parent_id' => $parent->id,
                    'store_type_id' => $storeType->id,
                ]
            );
        }
    }
}
