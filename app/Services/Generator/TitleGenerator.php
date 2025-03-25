<?php

namespace App\Services\Generator;

use Illuminate\Support\Facades\Log;

class TitleGenerator
{
    public const COMPATIBILITY_TEMPLATE_VAR = '#devices';
    public const COMPATIBILITY_NO_BRAND_TEMPLATE_VAR = '#devices_without_brand';

    public const PRODUCT_TEMPLATE_VAR = '#product';
    public const UNIQUE_CODE_VAR = '#unique_code';
    public const CARRIER_VAR = '#carrier';
    public const BRAND_VAR = '#brand';
    public const DEVICESERIES_VAR = '#device_series';
    public const DEVICESERIES_NO_BRAND_VAR = '#model_series';

    public const SHORT_KEYWORD = '#short_keyword';
    public const LONG_KEYWORD = '#long_keyword';

    protected string $template;
    protected int $maxNumberOfDetails;
    protected int $maxTitleLen;
    protected array $paramMap;

    public function __construct(int $maxNumberOfDetails, int $maxTitleLen, array $paramMap)
    {
        $this->maxNumberOfDetails = $maxNumberOfDetails;
        $this->maxTitleLen = $maxTitleLen;
        $this->paramMap = $paramMap;
    }

    public function getProductTitle(string $template, $productName, $short_keyword, $long_keyword): string
    {
//        $template = $this->replaceVar($template, self::PRODUCT_TEMPLATE_VAR, $productName);

        $containsLong = strpos($template, self::LONG_KEYWORD) !== false;
        $containsShort = strpos($template, self::SHORT_KEYWORD) !== false;
        $containsProductName = strpos($template, self::PRODUCT_TEMPLATE_VAR) !== false;

        if ($containsProductName) {
            $productNameMaxLen = $this->maxTitleLen - strlen($template) + strlen(self::PRODUCT_TEMPLATE_VAR) +
                ($containsShort ? strlen(self::SHORT_KEYWORD) : 0) +
                ($containsLong ? strlen(self::LONG_KEYWORD) : 0) - 1;
            Log::info('containsLong: ' . $productNameMaxLen);
            Log::info('productName: ' . $productName);

            $varValue = $this->trimWords($productName, $productNameMaxLen);
            $template = str_replace(self::PRODUCT_TEMPLATE_VAR, $varValue, $template);
        }

        if ($containsShort) {
            $productNameMaxLen = $this->maxTitleLen - strlen($template) + strlen(self::SHORT_KEYWORD) - 1 +
                ($containsLong ? strlen(self::LONG_KEYWORD) : 0);
            $varValue = $this->trimWords($short_keyword, $productNameMaxLen);
            $template = str_replace(self::SHORT_KEYWORD, $varValue, $template);
        }

        if ($containsLong) {
            $productNameMaxLen = $this->maxTitleLen - strlen($template) + strlen(self::LONG_KEYWORD) - 1;
            $varValue = $this->trimWords($long_keyword, $productNameMaxLen);
            $template = str_replace(self::LONG_KEYWORD, $varValue, $template);
        }

        return $template;
    }

    public static function trimWords(string $original, int $maxLen): string
    {
        if (strlen($original) <= $maxLen || $maxLen < 3) {
            return $original;
        }
        try {
            $original = substr($original, 0, strrpos(substr($original, 0, $maxLen + 1), ' '));
            if (substr($original, -1) === ',') {
                $original = substr($original, 0, -1);
            }
            return trim($original);
        } catch (\Exception $e) {
            return $original;
        }
    }

    protected function replaceVar(string $template, string $varName, string $varValue): string
    {
        if (strpos($template, $varName) !== false) {
            $productNameMaxLen = $this->maxTitleLen - strlen($template) + strlen($varName) - 1;
            $varValue = $this->trimWords($varValue, $productNameMaxLen);
            return str_replace($varName, $varValue, $template);
        }

        return $template;
    }

}
