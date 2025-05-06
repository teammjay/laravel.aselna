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
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_material_id',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_length',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_width',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Connectivity', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Form Factor', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'column', 'table' => 'products', 'column' => 'product_material_id',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Mounting Type', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Fastening', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'output_current_value',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'output_current_unit',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'input_voltage_value',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'input_voltage_unit',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'output_voltage_value',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'column', 'table' => 'products', 'column' => 'output_voltage_unit',],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Ports', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Connectivity', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length_unit',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_width',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_width_unit',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'output_voltage_value',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'output_voltage_unit',],
            // TODO Needed more feilds
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Connectivity', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Ports', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Power Source type', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Recommended Uses', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length',],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length_unit',],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'column', 'table' => 'products', 'column' => 'product_material_id',],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Form Factor', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'column', 'table' => 'products', 'column' => 'product_material_id',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Mounting Type', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'column', 'table' => 'products', 'column' => 'product_material_id',],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
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
