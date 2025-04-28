<?php

namespace Database\Seeders;

use App\Models\ListingRequirement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ListingRequirementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requirements = [
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Item Included', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Connectivity', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Connector Gender', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Cable Length', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Type', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'material',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'color',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_length',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_width',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
        ];

        foreach ($requirements as $requirement) {
            ListingRequirement::updateOrCreate(
                [
                    'category' => $requirement['category'],
                    'type' => $requirement['type'],
                    'column' => $requirement['column'] ?? null,
                    'key_column' => $requirement['key_column'] ?? null,
                    'key_value' => $requirement['key_value'] ?? null,
                ],
                [
                    'category' => $requirement['category'],
                    'type' => $requirement['type'],
                    'table' => $requirement['table'],
                    'column' => $requirement['column'] ?? null,
                    'key_column' => $requirement['key_column'] ?? null,
                    'key_value' => $requirement['key_value'] ?? null,
                    'value_column' => $requirement['value_column'] ?? null,
                ]
            );
        }
    }
}
