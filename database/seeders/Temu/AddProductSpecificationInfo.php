<?php

namespace Database\Seeders\Temu;

use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\TemuAuthService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        foreach ($categories as $category) {
            $specifications = $this->temuAuthService->getTemplate($category->code);

            $TemplateProperties = $specifications['result']['templateInfo']['goodsProperties'] ?? [];
            $TemplateSpecProperties = $specifications['result']['templateInfo']['goodsSpecProperties'] ?? [];

            foreach ($TemplateProperties as $property) {
                $productSpecification = ProductSpecificationInfo::updateOrCreate([
                    'store_type_id' => $this->storeType->id,
                    'store_type_category_id' => $category->id,
                    'name' => $property['name'],
                ], [
                    'store_type_id' => $this->storeType->id,
                    'store_type_category_id' => $category->id,
                    'name' => $property['name'],
                    'field_local_name' => $property['name'],
                    'required' => $property['required'] == true ? 1 : 0,
                ]);

                foreach ($property['values'] as $value) {
                    ProductSpecificationValue::updateOrCreate([
                        'product_specification_id' => $productSpecification->id,
                        'field_value' => $value['value'],
                    ], [
                        'product_specification_id' => $productSpecification->id,
                        'field_value' => $value['value']
                    ]);
                }
            }

            foreach ($TemplateSpecProperties as $specProperty) {
                $productSpecification = ProductSpecificationInfo::updateOrCreate([
                    'store_type_id' => $this->storeType->id,
                    'store_type_category_id' => $category->id,
                    'name' => $specProperty['name'],
                ], [
                    'store_type_id' => $this->storeType->id,
                    'store_type_category_id' => $category->id,
                    'name' => $specProperty['name'],
                    'field_local_name' => $specProperty['name'],
                    'required' => $specProperty['required'] == true ? 1 : 0,
                ]);

                foreach ($specProperty['values'] as $value) {
                    ProductSpecificationValue::updateOrCreate([
                        'product_specification_id' => $productSpecification->id,
                        'field_value' => $value['value'],
                    ], [
                        'product_specification_id' => $productSpecification->id,
                        'field_value' => $value['value']
                    ]);
                }
            }
        }

    }
}
