<?php

namespace Database\Seeders;

use App\Models\ProductSpecificationInfo;
use App\Models\StoreType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecificationKeyseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayOfKeys = ['Speed Class', 'Storage Capacity', 'Format', 'Wireless Technology', 'Fastening', 'Items Included', 'Material', 'Microphone Type', 'Features', 'Connectivity', 'Number of Earpieces', 'Type', 'Mount Location', 'Compatible Brand', 'Form Factor', 'Mounting Type', 'Design/Finish', 'Cable Length', 'Number of Ports'];
        $storeTypes = StoreType::all();
        foreach ($arrayOfKeys as $key) {
            foreach ($storeTypes as $storeType) {
                ProductSpecificationInfo::updateOrCreate(
                    [
                        'field_name' => $key,
                        'store_type_id' => $storeType->id,
                    ],
                    [
                        'field_name' => $key,
                        'field_local_name' => $key,
                        'store_type_id' => $storeType->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
