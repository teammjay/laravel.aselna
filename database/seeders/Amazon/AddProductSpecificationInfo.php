<?php

namespace Database\Seeders\Amazon;

use App\Models\ListingRequirement;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\Store;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\AmazonAuthService;
use App\Utils\Amazon\AmazonRequirementCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AddProductSpecificationInfo extends Seeder
{
    private AmazonAuthService $amazonAuthService;
    private StoreType $storeType;
    private Store $store;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->storeType = StoreType::where('code', 'amazon')->first();
        $this->store = Store::where('store_type_id', $this->storeType->id)->first();

        $this->fetchAndStoreSpecifications();
    }

    private function fetchAndStoreSpecifications()
    {
        $qualifiedcategoryCodes = ['ELECTRONIC_CABLE', 'SCREEN_PROTECTOR', 'HEADPHONES', 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'CELL_PHONE_HOLSTER', 'CHARGING_ADAPTER',
            'POWER_BANK', 'CAMERA_TRIPOD', 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'CELLULAR_PHONE_CASE',
            'CABLE_OR_ADAPTER', 'INPUT_PEN', 'MULTIPORT_HUB', 'ELECTRONIC_ADAPTER', 'MULTIPORT_HUB'];
        $requirement = ['category' => '', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => '', 'value_column' => 'value'];

        $this->amazonAuthService = new AmazonAuthService(env('AMAZON_CLIENT_ID'), env('AMAZON_CLIENT_SECRET'), env('AMAZON_REFRESH_TOKEN'));
        $marketPlaceID = $this->amazonAuthService->getMarketplaceIDs();
        $categories = StoreTypeCategory::where('store_type_id', $this->storeType->id)
            ->whereNotNull('marketplace_code')
//            ->whereIn('marketplace_code', $qualifiedcategoryCodes)
            ->select('marketplace_code')
            ->distinct()
            ->get();

        foreach ($categories as $category) {
            $specifications = $this->amazonAuthService->getProductTypeSchema($category->marketplace_code, $marketPlaceID ?? ['ATVPDKIKX0DER']);

            $properties = $specifications['properties'] ?? [];

//            foreach ($properties as $nameKey => $material) {
//                Log::info('Processing attribute: ' . $nameKey);
//
//                $itemProperties = $material['items']['properties'];
//                $itemValue = $itemProperties['value'] ?? null;
//
//                if (isset($itemValue) && isset($itemValue['anyOf'])) {
//                    ProductSpecificationInfo::updateOrCreate([
//                        'store_type_id' => $this->storeType->id,
//                        'field_name' => $nameKey,
//                    ], [
//                        'store_type_id' => $this->storeType->id,
//                        'field_name' => $nameKey,
//                        'field_local_name' => $nameKey,
//                    ]);
//
//                    $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
//                        'field_name' => $nameKey,
//                    ], [
//                        'field_name' => $nameKey,
//                    ]);
//
//                    foreach ($itemValue['anyOf'] as $anyOf) {
//                        if (isset($anyOf['enum'])) {
//                            foreach ($anyOf['enum'] as $enumValue) {
//                                Log::info('Storing enum value: ' . $enumValue);
//
//                                ProductSpecificationValue::updateOrCreate([
//                                    'product_specification_id' => $productSpecification->id,
//                                    'field_value' => $enumValue,
//                                    'category' => $category->marketplace_code,
//                                    'store_type_id' => $this->storeType->id,
//                                ], [
//                                    'product_specification_id' => $productSpecification->id,
//                                    'field_value' => $enumValue,
//                                    'store_type_id' => $this->storeType->id,
//                                    'category' => $category->marketplace_code
//                                ]);
//                            }
//                        }
//                    }
//                }
//
//                if (isset($itemValue) && isset($itemValue['enum'])) {
//                    ProductSpecificationInfo::updateOrCreate([
//                        'store_type_id' => $this->storeType->id,
//                        'field_name' => $nameKey,
//                    ], [
//                        'store_type_id' => $this->storeType->id,
//                        'field_name' => $nameKey,
//                        'field_local_name' => $nameKey,
//                    ]);
//
//                    $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
//                        'field_name' => $nameKey,
//                    ], [
//                        'field_name' => $nameKey,
//                    ]);
//
//                    foreach ($itemValue['enum'] as $enumValue) {
//                        Log::info('Storing enum value: ' . $enumValue);
//
//                        ProductSpecificationValue::updateOrCreate([
//                            'product_specification_id' => $productSpecification->id,
//                            'field_value' => $enumValue,
//                            'category' => $category->marketplace_code,
//                            'store_type_id' => $this->storeType->id,
//                        ], [
//                            'product_specification_id' => $productSpecification->id,
//                            'field_value' => $enumValue,
//                            'store_type_id' => $this->storeType->id,
//                            'category' => $category->marketplace_code
//                        ]);
//                    }
//                }
//
//
//                //skip if attribute is not searchable and not required
////                if (($attribute['searchable'] ?? false) == false && ($attribute['required'] ?? false) == false) {
////                    continue;
////                }
////
////                //skip if attribute is already exists
////                $existingAttribute = ProductSpecificationInfo::where('store_type_id', $this->storeType->id)
////                    ->where('field_name', $attribute['name'])
////                    ->first();
////
////                if ($existingAttribute) {
////                    continue;
////                }
////
////                ProductSpecificationInfo::create([
////                    'store_type_id' => $this->storeType->id,
////                    'field_name' => $attribute['name'],
////                    'field_local_name' => $attribute['name'],
////                    'required' => ($attribute['required'] ?? false) == true ? 1 : 0,
////                ]);
//            }

//            $schemaObject = json_decode($fullSchemaJson);
            $calculator = new AmazonRequirementCalculator($specifications);
            $productData = [];
            $skipCommonFields = ["brand", "bullet_point", "country_of_origin", "item_name", "item_type_keyword", "product_description", "compatible_devices", "fulfillment_availability", "condition_type", "list_price",
                "item_package_dimensions", "merchant_suggested_asin", "externally_assigned_product_identifier", "manufacturer", "part_number", "product_description", "compatible_phone_models"
                , "model_name", "model_number", "batteries_required"];

            $requiredList = $calculator->getRequiredFieldsV2();

            foreach ($requiredList as $requiredField) {
                $commonField = false;
                if (in_array($requiredField, $skipCommonFields)) {
                    $commonField = true;
                }
                Log::info('Storing required field: ' . $requiredField);

                ProductSpecificationInfo::updateOrCreate([
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $requiredField,
                ], [
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $requiredField,
                    'field_local_name' => $requiredField,
                    'required' => 1,
                ]);

                $this->addListingRequirement($this->storeType->id, $category->marketplace_code, $requiredField,$commonField);
            }

            sleep(15);
        }

    }

    private function addListingRequirement(mixed $id, $code, mixed $required,$commonField)
    {
        $requirement = ['type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => null, 'value_column' => 'value'];
        switch ($required) {
            case 'color':
                $requirement['table'] = 'products';
                $requirement['column'] = 'product_color';
                $requirement['key_value'] = $required;
                break;
            case 'item_length_width':
            case 'item_length_width_thickness':
                $requirement['table'] = 'products';
                $requirement['column'] = 'package_dimensions_length';
                $requirement['key_value'] = $required;
                break;
//            case 'assembledProductHeight':
//                $requirement['table'] = 'products';
//                $requirement['column'] = 'package_dimensions_height';
//                break;
            case 'item_package_weight':
                $requirement['table'] = 'products';
                $requirement['column'] = 'product_package_weight';
                $requirement['key_value'] = $required;
                break;
            case 'cable':
                $requirement['key_value'] = 'Cable Length';
                break;
            default:
                $requirement['key_value'] = $required;
                break;
        }

        if (isset($requirement['column'])) {
            $requirement['type'] = 'column';
            $requirement['key_column'] = null;
//            $requirement['key_value'] = null;
            $requirement['value_column'] = null;
        }

        $requirement['category'] = $code;

        Log::info('Requirement to add: ' . json_encode($requirement));

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
                'store_type_id' => $id,
                'is_common' => $commonField
            ]
        );
    }
}
