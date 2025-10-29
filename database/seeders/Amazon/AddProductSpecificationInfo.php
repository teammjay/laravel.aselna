<?php

namespace Database\Seeders\Amazon;

use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\Store;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\AmazonAuthService;
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
        $qualifiedcategoryCodes = ['ELECTRONIC_CABLE', 'SCREEN_PROTECTOR', 'HEADPHONES', 'PORTABLE_ELECTRONIC_DEVICE_MOUNT', 'CELL_PHONE_HOLSTER', 'CHARGING_ADAPTER', 'POWER_BANK', 'CAMERA_TRIPOD', 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND', 'PORTABLE_ELECTRONIC_DEVICE_STAND', 'CELLULAR_PHONE_CASE'];
        $this->amazonAuthService = new AmazonAuthService(env('AMAZON_CLIENT_ID'), env('AMAZON_CLIENT_SECRET'), env('AMAZON_REFRESH_TOKEN'));
        $marketPlaceID = $this->amazonAuthService->getMarketplaceIDs();
        $categories = StoreTypeCategory::where('store_type_id', $this->storeType->id)
            ->whereNotNull('marketplace_code')
            ->whereIn('marketplace_code', $qualifiedcategoryCodes)
            ->select('marketplace_code')
            ->distinct()
            ->get();

        foreach ($categories as $category) {
//            $this->amazonAuthService = new AmazonAuthService($this->store->seller_id, $this->store->secret_key, $this->store->api_key);
            $specifications = $this->amazonAuthService->getProductTypeSchema($category->marketplace_code, $marketPlaceID);

            $properties = $specifications['properties'] ?? [];

            foreach ($properties as $nameKey => $material) {
                Log::info('Processing attribute: ' . $nameKey);

                $itemProperties = $material['items']['properties'];
                $itemValue = $itemProperties['value'] ?? null;

                if (isset($itemValue) && isset($itemValue['anyOf'])) {
                    ProductSpecificationInfo::updateOrCreate([
                        'store_type_id' => $this->storeType->id,
                        'field_name' => $nameKey,
                    ], [
                        'store_type_id' => $this->storeType->id,
                        'field_name' => $nameKey,
                        'field_local_name' => $nameKey,
                    ]);

                    $productSpecification = ProductSpecifcationUniqueInfo::updateOrCreate([
                        'field_name' => $nameKey,
                    ], [
                        'field_name' => $nameKey,
                    ]);

                    foreach ($itemValue['anyOf'] as $anyOf) {
                        if (isset($anyOf['enum'])) {
                            foreach ($anyOf['enum'] as $enumValue) {
                                Log::info('Storing enum value: ' . $enumValue);

                                ProductSpecificationValue::updateOrCreate([
                                    'product_specification_id' => $productSpecification->id,
                                    'field_value' => $enumValue,
                                    'category' => $category->marketplace_code,
                                    'store_type_id' => $this->storeType->id,
                                ], [
                                    'product_specification_id' => $productSpecification->id,
                                    'field_value' => $enumValue,
                                    'store_type_id' => $this->storeType->id,
                                    'category' => $category->marketplace_code
                                ]);
                            }
                        }
                    }
                }


                //skip if attribute is not searchable and not required
//                if (($attribute['searchable'] ?? false) == false && ($attribute['required'] ?? false) == false) {
//                    continue;
//                }
//
//                //skip if attribute is already exists
//                $existingAttribute = ProductSpecificationInfo::where('store_type_id', $this->storeType->id)
//                    ->where('field_name', $attribute['name'])
//                    ->first();
//
//                if ($existingAttribute) {
//                    continue;
//                }
//
//                ProductSpecificationInfo::create([
//                    'store_type_id' => $this->storeType->id,
//                    'field_name' => $attribute['name'],
//                    'field_local_name' => $attribute['name'],
//                    'required' => ($attribute['required'] ?? false) == true ? 1 : 0,
//                ]);
            }

            sleep(15);
        }

    }
}
