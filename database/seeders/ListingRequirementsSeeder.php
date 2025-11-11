<?php

namespace Database\Seeders;

use App\Models\ListingRequirement;
use App\Models\ProductSpecificationInfo;
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
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connectivity_technology', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connector_gender', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Cable Length', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Type', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_CABLE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_length',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_dimensions_width',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'SCREEN_PROTECTOR', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'HEADPHONES', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connectivity_technology', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'headphones_ear_placement', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'headphones_form_factor', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'HEADPHONES', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'mounting_type', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'column', 'table' => 'products', 'column' => 'package_weight_unit',],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'column', 'table' => 'products', 'column' => 'product_package_weight',],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Fastening', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CELL_PHONE_HOLSTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'output_current_value', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'output_voltage_value', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'input_voltage_value', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Ports', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connectivity_technology', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CHARGING_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length',],
            ['category' => 'POWER_BANK', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_width',],
            // TODO Needed more feilds
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'output_voltage_value', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connectivity_technology', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Ports', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'power_source_type', 'value_column' => 'value'],
            ['category' => 'POWER_BANK', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'recommended_uses_for_product', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'column', 'table' => 'products', 'column' => 'dimensions_length',],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CAMERA_TRIPOD', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'form_factor', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'mounting_type', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Items Included', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'column', 'table' => 'products', 'column' => 'product_color',],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CELLULAR_PHONE_CASE', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'ELECTRONIC_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connector_type', 'value_column' => 'value'],
            ['category' => 'CABLE_OR_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'CABLE_OR_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'CABLE_OR_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Material', 'value_column' => 'value'],
            ['category' => 'CABLE_OR_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connector_gender', 'value_column' => 'value'],
            ['category' => 'CABLE_OR_ADAPTER', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Cable Length', 'value_column' => 'value'],
            ['category' => 'INPUT_PEN', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'INPUT_PEN', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Cable Length', 'value_column' => 'value'],
            ['category' => 'INPUT_PEN', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connector_type', 'value_column' => 'value'],
            ['category' => 'INPUT_PEN', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'connector_gender', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Number of Items', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'Warranty', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'total_usb_ports', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'special_feature', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'data_transfer_rate', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'hardware_interface', 'value_column' => 'value'],
            ['category' => 'MULTIPORT_HUB', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => 'number_of_ports', 'value_column' => 'value'],
        ];

        foreach ($requirements as $requirement) {
            ListingRequirement::where('store_type_id', '9ea83e8c-5b36-d377-d583-f7560b67f6b0')->where('category', $requirement['category'])->delete();
            ListingRequirement::whereNull('store_type_id')->where('category', $requirement['category'])->delete();
        }

        foreach ($requirements as $requirement) {
            ProductSpecificationInfo::updateOrCreate(
                [
                    'store_type_id' => '9ea83e8c-5b36-d377-d583-f7560b67f6b0',
                    'field_name' => $requirement['key_value'] ?? $requirement['column'],
                ],
                [
                    'store_type_id' => '9ea83e8c-5b36-d377-d583-f7560b67f6b0',
                    'field_name' => $requirement['key_value'] ?? $requirement['column'],
                    'field_local_name' => $requirement['key_value'] ?? $requirement['column'],
                ]
            );

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
                    'store_type_id' => '9ea83e8c-5b36-d377-d583-f7560b67f6b0',
                ]
            );
        }

        ListingRequirement::where(['table' => 'products', 'column' => 'product_material_id'])->delete();
    }
}
