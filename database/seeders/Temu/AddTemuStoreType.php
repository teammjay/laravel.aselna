<?php

namespace Database\Seeders\Temu;

use Illuminate\Database\Seeder;

class AddTemuStoreType extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\StoreType::updateOrCreate([
            'code' => 'temu',
            'name' => 'Temu',
        ],[
            'code' => 'temu',
            'name' => 'Temu',
            'max_title_length' => 200,
            'max_variation_name_length' => 200,
            'max_listing_variants' => 200,
            'max_random_title_length' => 200,
            'acknowledge_order' => 0,
        ]);
    }
}
