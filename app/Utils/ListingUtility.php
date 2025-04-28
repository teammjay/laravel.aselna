<?php

namespace App\Utils;

use App\Models\ListingRequirement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingUtility
{
    static function generateTitle($storeProduct, $force, $level, $maxRandomTitleLength = null, $bundleSeparator = " ")
    {
        $title = $storeProduct["title"] ?? null;
        $product = $storeProduct->product;
        Log::info("Product: " . json_encode($product));

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
                    Log::info("Processed Templates: " . json_encode($processedTemplates));
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
        Log::info("Product: " . json_encode($product));
        if (isset($product->storeProduct->store->storeType->name)) {
            return match (strtolower($product->storeProduct->store->storeType->name)) {
                "ebay" => "https://www.ebay.com/itm/" . $product->store_sku . "/" . $product->store_sku,
                "amazon" => "https://www.amazon.com/dp/" . $product->store_sku,
                "shopify" => "https://" . $product->store_sku . "/products/" . $product->store_sku,
                "magento" => "https://" . $product->store_sku . "/product/" . $product->store_sku,
                "walmart" => "https://www.walmart.com/ip/" . $product->store_sku,
                "newegg" => "https://www.newegg.com/p/" . $product->store_sku,
                default => "",
            };
        } else {
            return "";
        }
    }


    public static function validate( $category, $productId): array
    {
        $requirements = ListingRequirement::where('category', $category)->get();
        $issues = [];

        foreach ($requirements as $req) {
            Log::info("Requirement: " . json_encode($req));
            if ($req->type === 'column') {
                $value = DB::table($req->table)
                    ->where('product_id', $productId)
                    ->value($req->column);

                if (is_null($value) || $value === '') {
                    $issues[] = "Missing column '{$req->column}' in '{$req->table}'";
                }
            }

            if ($req->type === 'key_value') {
                $value = DB::table($req->table)
                    ->where('product_id', $productId)
                    ->where($req->key_column, $req->key_value)
                    ->value($req->value_column);

                if (is_null($value) || $value === '') {
                    $issues[] = "Missing value for key '{$req->key_value}' in '{$req->table}'";
                }
            }
        }

        return $issues;
    }


}
