<?php

namespace Database\Seeders\Temu;

use App\Models\ProductSpecificationValueDependency;
use App\Models\ListingRequiredFields;
use App\Models\ListingRequirement;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\TemuAuthService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AddProductSpecificationInfo extends Seeder
{
    private TemuAuthService $temuAuthService;
    private StoreType $storeType;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->temuAuthService = new TemuAuthService();
        $this->storeType = StoreType::where('code', 'temu')->first();

        $this->fetchAndStoreSpecifications();
    }

    private function fetchAndStoreSpecifications()
    {
        $categories = StoreTypeCategory::where('store_type_id', $this->storeType->id)->get();
        $requirement = ['category' => '', 'type' => 'key_value', 'table' => 'product_specification', 'key_column' => 'name', 'key_value' => '', 'value_column' => 'value'];

        foreach ($categories as $category) {
            $specifications = $this->temuAuthService->getTemplate($category->code);

            $TemplateProperties = $specifications['result']['templateInfo']['goodsProperties'] ?? [];
            $TemplateSpecProperties = $specifications['result']['templateInfo']['goodsSpecProperties'] ?? [];

            $requirementCount = 0;

            foreach ($TemplateProperties as $property) {
                Log::info("Category Name :: " . $property['name']);
                ProductSpecificationInfo::updateOrCreate([
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $property['name'],
                ], [
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $property['name'],
                    'field_local_name' => $property['name'],
                    'required' => $property['required'] == true ? 1 : 0,
                ]);

                if ($property['required'] == true && $property['showType'] == 0) {
                    $requirement['category'] = $category->code;
                    $requirement['key_value'] = $property['name'];
                    $requirementCount++;

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
                            'store_type_id' => $this->storeType->id
                        ]
                    );
                }

                if ($property['controlType'] == 1) {
                    $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
                        'field_name' => $property['name'],
                    ], [
                        'field_name' => $property['name'],
                    ]);

                    foreach ($property['values'] as $value) {
                        ProductSpecificationValue::updateOrCreate([
                            'product_specification_id' => $productSpecification->id,
                            'field_value' => $value['value'],
                            'store_type_id' => $this->storeType->id,
                            'category' => $category->code
                        ], [
                            'product_specification_id' => $productSpecification->id,
                            'field_value' => $value['value'],
                            'store_type_id' => $this->storeType->id,
                            'category' => $category->code,
                            'marketplace_value_id' => $value['vid']
                        ]);
                    }
                }

                if (isset($property['templatePropertyValueParentList'])) {
                    // Process parent-child value dependencies for UI
                    foreach ($property['templatePropertyValueParentList'] as $parentChildMapping) {
                        if (isset($parentChildMapping['parentVids']) && isset($parentChildMapping['vids'])) {
                            foreach ($parentChildMapping['parentVids'] as $parentVid) {
                                // Find the parent field and value
                                $parentValueRecord = ProductSpecificationValue::where('store_type_id', $this->storeType->id)
                                    ->where('category', $category->code)
                                    ->where('marketplace_value_id', $parentVid)->first();

                                if ($parentValueRecord) {
                                    // Create field requirement dependency
                                    ListingRequiredFields::updateOrCreate([
                                        'store_type_id' => $this->storeType->id,
                                        'category' => $category->code,
                                        'parent_field' => $parentValueRecord->specificationInfo->field_name,
                                        'parent_value' => $parentValueRecord->field_value,
                                        'dependent_field' => $property['name'],
                                    ], [
                                        'store_type_id' => $this->storeType->id,
                                        'category' => $category->code,
                                        'parent_field' => $parentValueRecord->specificationInfo->field_name,
                                        'parent_value' => $parentValueRecord->field_value,
                                        'dependent_field' => $property['name'],
                                    ]);

                                    // Create field value dependencies for each child value
                                    foreach ($parentChildMapping['vids'] as $childVid) {
                                        ProductSpecificationValueDependency::updateOrCreate([
                                            'parent_field_name' => $parentValueRecord->specificationInfo->field_name,
                                            'parent_field_value_id' => $parentValueRecord->field_value,
                                            'child_field_name' => $property['name'],
                                            'child_field_value_id' => (string)$childVid,
                                            'category' => $category->code,
                                            'store_type_id' => $this->storeType->id,
                                        ], [
                                            'parent_field_name' => $parentValueRecord->specificationInfo->field_name,
                                            'parent_field_value_id' => $parentValueRecord->field_value,
                                            'child_field_name' => $property['name'],
                                            'child_field_value_id' => (string)$childVid,
                                            'category' => $category->code,
                                            'store_type_id' => $this->storeType->id,
                                            'is_active' => true,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }


            }

            foreach ($TemplateSpecProperties as $specProperty) {
                ProductSpecificationInfo::updateOrCreate([
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $specProperty['name'],
                ], [
                    'store_type_id' => $this->storeType->id,
                    'field_name' => $specProperty['name'],
                    'field_local_name' => $specProperty['name'],
                    'required' => $specProperty['required'] == true ? 1 : 0,
                ]);

                if ($property['required'] == true) {
                    $requirement['category'] = $category->code;
                    $requirement['key_value'] = $property['name'];
                    $requirementCount++;

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
                            'store_type_id' => $this->storeType->id
                        ]
                    );
                }

                if ($property['controlType'] == 1) {
                    $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
                        'field_name' => $property['name'],
                    ], [
                        'field_name' => $property['name'],
                    ]);

                    foreach ($property['values'] as $value) {
                        ProductSpecificationValue::updateOrCreate([
                            'product_specification_id' => $productSpecification->id,
                            'field_value' => $value['value'],
                            'store_type_id' => $this->storeType->id,
                            'category' => $category->code
                        ], [
                            'product_specification_id' => $productSpecification->id,
                            'field_value' => $value['value'],
                            'store_type_id' => $this->storeType->id,
                            'category' => $category->code
                        ]);
                    }
                }
            }

//            if ($requirementCount <= 0) {
//                $userInputParentSpecList = $specifications['result']['userInputParentSpecList'] ?? [];
//
//            }
//            ListingRequirement::create([
//                'category' => 'electronics',
//                'type' => 'any_fields',
//                'table' => 'products',
//                'fields_json' => json_encode(['field1', 'field2', 'field3'])
//            ]);
        }

    }
}
