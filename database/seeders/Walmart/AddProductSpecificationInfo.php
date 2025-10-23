<?php

namespace Database\Seeders\Walmart;

use App\Models\ListingRequirement;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\WalmartAuthService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AddProductSpecificationInfo extends Seeder
{
    private WalmartAuthService $walmartAuthService;
    private StoreType $storeType;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->walmartAuthService = new WalmartAuthService('1a658940-03da-47f1-8d51-efffe07ad63d', 'ehGhTShle6lGH9m2XFpWOUIuM1jgq2EhIkDd-OxG3_gnXpD0AqxWsZ4MXmJNIlLHAwjyo3w_gvphe9O-kvI6eA');
        $this->storeType = StoreType::where('code', 'walmart')->first();

        StoreTypeCategory::where('store_type_id', $this->storeType->id)->chunk(20, function ($categories) {
            $this->fetchandStoreSpecifications($categories);
        });
    }

    private function fetchandStoreSpecifications($categories): void
    {
        $exculdeKeys = ["productName", "brand", "shortDescription", "keyFeatures", "mainImageUrl", "netContent", "compatibleBrands", "compatibleModels",];
//        $uniqueRequiredAttributes = [];
        $categoryArray = [];
        foreach ($categories as $category) {
            $categoryArray[] = $category->code;
        }
        try {
            Log::info("Fetching specifications for category: " . json_encode($categoryArray));
            $specifications = $this->walmartAuthService->getProductSpecifications('MP_ITEM', '5.0.20250828-17_32_59-api', $categoryArray);
            if (isset($specifications)) {
                $specMap = $specifications['schema']['properties']['MPItem']['items']['properties']['Visible']['properties'] ?? [];
                foreach ($specMap as $key => $specification) {
                    $requiredAttr = $specification['required'] ?? [];
                    Log::info("Category Requirement: " . $key . " :: " . json_encode($requiredAttr));
                    foreach ($requiredAttr as $required) {
                        if (in_array($required, $exculdeKeys)) {
                            continue;
                        }
                        $property = $specification['properties'][$required] ?? null;
                        if ($property) {
                            $propertyType = $property['type'] ?? 'string';
                            ProductSpecificationInfo::updateOrCreate([
                                'store_type_id' => $this->storeType->id,
                                'field_name' => $required,
                            ], [
                                'store_type_id' => $this->storeType->id,
                                'field_name' => $required,
                                'field_local_name' => $required,
                                'required' => 1,
                            ]);

                            $this->addListingRequirement($this->storeType->id, $key, $required);

                            if (isset($property['enum'])) {
                                $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
                                    'field_name' => $required,
                                ], [
                                    'field_name' => $required,
                                ]);

                                foreach ($property['enum'] as $value) {
                                    ProductSpecificationValue::updateOrCreate([
                                        'product_specification_id' => $productSpecification->id,
                                        'field_value' => $value,
                                    ], [
                                        'product_specification_id' => $productSpecification->id,
                                        'field_value' => $value,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching specifications for category: " . $category->code . " :: " . $e->getMessage());
        }
        sleep(10);
    }

    private function addListingRequirement(mixed $id, $code, mixed $required)
    {
        $requirement = ['type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => '', 'value_column' => 'value'];
        switch ($required) {
            case 'color':
                $requirement['table'] = 'products';
                $requirement['column'] = 'product_color';
                break;
            case 'assembledProductLength':
                $requirement['table'] = 'products';
                $requirement['column'] = 'package_dimensions_length';
                break;
            case 'assembledProductHeight':
                $requirement['table'] = 'products';
                $requirement['column'] = 'package_dimensions_height';
                break;
            case 'assembledProductWeight':
                $requirement['table'] = 'products';
                $requirement['column'] = 'product_package_weight';
                break;
            case 'assembledProductWidth':
                $requirement['table'] = 'products';
                $requirement['column'] = 'package_dimensions_width';
                break;
            default:
                $requirement['key_value'] = $required;
                break;
        }

        if (isset($requirement['column'])) {
            $requirement['type'] = 'column';
            $requirement['key_column'] = null;
            $requirement['key_value'] = null;
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
                'store_type_id' => $id
            ]
        );
    }
}
