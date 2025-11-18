<?php

namespace App\Utils;

use App\Models\ListingRequirement;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\ListingRequiredFields;
use App\Models\ProductSpecificationValueDependency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListingUtility
{
    static function generateTitle($storeProduct, $force, $level, $maxRandomTitleLength = null, $bundleSeparator = " ")
    {
        $title = $storeProduct["title"] ?? null;
        $product = $storeProduct->product;
        //Log::info("Product: " . json_encode($product));

        if ($force) {
            $title = null;
        }

        if (empty(trim($title))) {
            $template = $product->title_template ?? null;
            Log::info("Template: " . $template);

            if ($template !== null) {
                Log::info("Product Bundle: " . $product->bundle);
                if (isset($product->bundle)) {
                    $template = self::trimTemplate($template, $level);
                } else {
                    $templates = explode(" + ", $template);
                    Log::info("Templates: " . count($templates));
                    $processedTemplates = array_map(fn($t) => self::trimTemplate($t, $level), $templates);
                    //Log::info("Processed Templates: " . json_encode($processedTemplates));
                    $template = implode($bundleSeparator, $processedTemplates);
                }
                Log::info("Processed Template: " . $template);

                $spintax = new Spintax();
                $randomText = $spintax->process($template);
                $title = $randomText;
                Log::info("Random Text: " . $randomText);
                Log::info("Title: " . $title);
            } else {
                $title = $product["title"];
            }
        }

        if ($maxRandomTitleLength !== null && $maxRandomTitleLength > 0 && (empty($product["bundle"]))) {
            $title = self::trimWords($title, $maxRandomTitleLength);
        }

        return $title;
    }

    static function trimTemplate($template, $level)
    {
        Log::info(
            "Trimming template: " . $template . " with level: " . $level
        );
        $level1 = self::findClosingParen($template, 0);

        if ($level == 1 || strlen($template) == $level1) {
            return substr($template, 0, $level1 + 1);
        }
        if ($level == 2) {
            $start = strpos($template, "{", $level1);
            Log::info("Start: " . $start);
            if ($start > 0) {
                $level2 = self::findClosingParen($template, $start);
                Log::info("Level 2: " . $level2);
                return substr($template, 0, $level2 + 1);
            }
            return $template;
        }
        if ($level == 3) {
            $start = strpos($template, "{", $level2 ?? 0);
            if ($start > 0) {
                $level2 = self::findClosingParen($template, $start);
                return substr($template, 0, $level2 + 1);
            }
            return $template;
        }

        return $template;
    }

    static function findClosingParen($text, $openPos)
    {
        $closePos = $openPos;
        $counter = 1;

        while ($counter > 0 && ++$closePos < strlen($text)) {
            $char = $text[$closePos];
            if ($char === '{') {
                $counter++;
            } elseif ($char === '}') {
                $counter--;
            }
        }

        return $closePos;
    }

    static function trimWords($text, $maxWords)
    {
        $words = explode(" ", trim($text));
        if (count($words) > $maxWords) {
            return implode(" ", array_slice($words, 0, $maxWords));
        }
        return $text;
    }

    public static function generateTitleUrl(mixed $product): string
    {
        //Log::info("Product: " . json_encode($product));
        if (isset($product->storeProduct->store->storeType->name)) {
            return match (strtolower($product->storeProduct->store->storeType->name)) {
                "ebay" => "https://www.ebay.com/itm/" . $product->store_sku . "/" . $product->store_sku,
                "amazon" => "https://www.amazon.com/dp/" . $product->store_sku,
                "shopify" => "https://" . $product->store_sku . "/products/" . $product->store_sku,
                "magento" => "https://" . $product->store_sku . "/product/" . $product->store_sku,
                "walmart" => "https://www.walmart.com/ip/" . $product->store_sku,
                "newegg" => "https://www.newegg.com/p/" . $product->store_sku,
                "temu" => "https://www.temu.com/goods.html?goods_id=" . $product->store_sku,
                default => "",
            };
        } else {
            return "";
        }
    }


    public static function generateItemUrl($store_type, $store_sku = '', $site_url = '', $store_url = '')
    {
        //Log::info("Product: " . json_encode($product));
        if ($store_type != '' && $store_sku != '') {
            return match (strtolower($store_type)) {
                "ebay" => "https://www.ebay.com/itm/" . $store_sku . "/" . $store_sku,
                "amazon" => "https://www.amazon.com/dp/" . $store_sku,
                "shopify" => $site_url . "/products/" . $store_url,
                "magento" => $site_url . "/product/" . $store_sku,
                "walmart" => "https://www.walmart.com/ip/" . $store_sku,
                "newegg" => "https://www.newegg.com/p/" . $store_sku,
                "temu" => "https://www.temu.com/goods.html?goods_id=" . $store_sku,
                default => "",
            };
        } else {
            return "";
        }
    }


    public static function validate($category, $productId, $storeTypeId): array
    {
        $requirements = ListingRequirement::where('category', $category)->where('store_type_id', $storeTypeId)->get();
        $issues = [];
        $specifications = [];
        $fieldName = '';
        $productData = [];

        $productData = self::collectProductData($productId, $storeTypeId);

        foreach ($requirements as $key => $req) {
            // Log::info("Requirement: " . json_encode($req));
            if ($req->type === 'column') {
                $value = DB::table($req->table)
                    ->where('product_id', $productId)
                    ->value($req->column);

                $fieldName = $req->column;
                if (is_null($value) || $value === '') {
                    $issues[$key]['title'] = "Missing column '{$req->column}' in '{$req->table}'";
                }
            }

            if ($req->type === 'key_value') {
                $query = DB::table($req->table)
                    ->where('product_id', $productId)
                    ->where($req->key_column, $req->key_value);

                if (Schema::hasColumn($req->table, 'store_type_id')) {
                    // Assuming you have the store_type_id value (e.g., from the request)
                    $query->where('store_type_id', $storeTypeId);
                }
                $fieldName = $req->key_value;
                $value = $query->value($req->value_column);

                if (is_null($value) || $value === '') {
                    $issues[$key]['title'] = "Missing value for key '{$req->key_value}' in '{$req->table}'";
                }

            }

            if ($req->type === 'any_fields') {
                $fieldsData = json_decode($req->fields_json, true);
                if (empty($fieldsData) || !is_array($fieldsData)) {
                    continue;
                }

                $query = DB::table($req->table)->where('product_id', $productId);

                if (Schema::hasColumn($req->table, 'store_type_id')) {
                    $query->where('store_type_id', $storeTypeId);
                }

                $row = $query->first();
                if (!$row) {
                    $issues[$key]['title'] = "Missing record in '{$req->table}'";
                    continue;
                }

                $hasValue = false;
                foreach ($fieldsData as $field) {
                    if (property_exists($row, $field) && !empty($row->{$field})) {
                        $hasValue = true;
                        break;
                    }
                }

                if (!$hasValue) {
                    $fieldsList = implode("', '", $fieldsData);
                    $issues[$key]['title'] = "At least one of these fields must have a value: '{$fieldsList}'";
                }
            }

            if (isset($issues[$key]['title'])) {
                $qquery = ProductSpecificationInfo::where('field_local_name', $fieldName);
                if ($storeTypeId) {
                    $qquery->where('store_type_id', $storeTypeId);
                }

                $specificationInfo = $qquery->orderBy('field_local_name')->first();

                if ($specificationInfo) {

                    $field_option = ProductSpecifcationUniqueInfo::where('field_name', $req->key_value)->first();
                    $field = $valoption = '';
                    if (!empty($field_option)) {
                        $field = 'select';
                        $valoption = self::getSpecValues($field_option, $category, $storeTypeId);
                    }

                    $issues[$key]['spec'] = [
                        'field_id' => $specificationInfo->id,
                        'field' => $specificationInfo->field_local_name,
                        'spec_id' => $field_option->id ?? null,
                        'field_type' => $field,
                        'values' => $valoption,
                    ];

                }
            }
        }

        // Validate field dependencies
        $dependencyIssues = self::validateFieldDependencies($productData, $category, $storeTypeId);

        // Merge dependency issues with existing issues
        $issues = array_merge($issues, $dependencyIssues);

        return $issues;
    }

    /**
     * Collect all product data from various tables for dependency validation
     */
    private static function collectProductData($productId, $storeTypeId): array
    {
        $productData = [];

        try {
            $specifications = ProductSpecification::where('product_id', $productId)
                ->get();

            foreach ($specifications as $spec) {
                $productData[$spec->name] = $spec->calculated_value();
            }

        } catch (\Exception $e) {
            Log::error('Error collecting product data for dependency validation: ' . $e->getMessage());
        }

        return $productData;
    }

    /**
     * Validate field dependencies based on the field_dependencies table
     */
    private static function validateFieldDependencies($productData, $category, $storeTypeId): array
    {
        $dependencyIssues = [];

        try {
            // Get all active field dependencies for this category and store type
            $dependencies = ListingRequiredFields::active()
                ->forCategoryAndStore($category, $storeTypeId)
                ->get();

            Log::info("Product Data for Dependency Validation: " . json_encode($productData));

            foreach ($dependencies as $dependency) {
                $parentField = $dependency->parent_field;
                $parentValue = $dependency->parent_value;
                $dependentField = $dependency->dependent_field;

                if (isset($productData[$parentField]) && $productData[$parentField] == $parentValue) {
                    Log::info("Parent condition met for field '{$parentField}' with value '{$parentValue}'");

                    if (!isset($productData[$dependentField]) ||
                        empty($productData[$dependentField]) ||
                        $productData[$dependentField] === '') {

                        $issueKey = 'dependency_' . $dependency->id;
                        $dependencyIssues[$issueKey]['title'] = "Field '{$dependentField}' is required when '{$parentField}' is '{$parentValue}'";

                        // Try to get specification info for the dependent field
                        $specInfo = self::getFieldSpecificationInfo($dependentField, $storeTypeId, $category, $parentField, $parentValue);
                        if ($specInfo) {
                            $dependencyIssues[$issueKey]['spec'] = $specInfo;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error validating field dependencies: ' . $e->getMessage());
        }

        return $dependencyIssues;
    }

    /**
     * Get specification info for a field
     */
    private static function getFieldSpecificationInfo($fieldName, $storeTypeId, $category, $parentField, $parentValue): ?array
    {
        try {
            $qquery = ProductSpecificationInfo::where('field_local_name', $fieldName);
            if ($storeTypeId) {
                $qquery->where('store_type_id', $storeTypeId);
            }

            $specificationInfo = $qquery->orderBy('field_local_name')->first();

            if (!$specificationInfo) {
                return null;
            }

            $field_option = ProductSpecifcationUniqueInfo::where('field_name', $fieldName)->first();
            $field = $valoption = '';

            if (!empty($field_option)) {
                $field = 'select';
                $childValueIds = ProductSpecificationValueDependency::getChildValues(
                    $parentField, $parentValue, $fieldName, $category, $storeTypeId
                );
                $valoption = self::getSpecValues($field_option, $category, $storeTypeId, $childValueIds);
            }

            return [
                'field_id' => $specificationInfo->id,
                'field' => $specificationInfo->field_local_name,
                'spec_id' => $field_option->id ?? null,
                'field_type' => $field,
                'values' => $valoption,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting field specification info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param $field_option
     * @return mixed
     */
    public static function getSpecValues($field_option, $category, $storeTypeId, $childValueIds = null)
    {
        $valoption = ProductSpecificationValue::where('product_specification_id', $field_option->id)
            ->where('store_type_id', $storeTypeId)
            ->where('category', $category)
            ->when(!empty($childValueIds), function ($query) use ($childValueIds) {
                $query->whereIn('marketplace_value_id', $childValueIds);
            })
            ->get();

        if ($valoption->isEmpty()) {
            $valoption = ProductSpecificationValue::where('product_specification_id', $field_option->id)
                ->where('store_type_id', $storeTypeId)
                ->when(!empty($childValueIds), function ($query) use ($childValueIds) {
                    $query->whereIn('marketplace_value_id', $childValueIds);
                })
                ->get();
        }

        if ($valoption->isEmpty()) {
            $valoption = ProductSpecificationValue::where('product_specification_id', $field_option->id)
                ->whereNull('store_type_id')
                ->when(!empty($childValueIds), function ($query) use ($childValueIds) {
                    $query->whereIn('marketplace_value_id', $childValueIds);
                })
                ->get();
        }

        return $valoption;
    }


}
