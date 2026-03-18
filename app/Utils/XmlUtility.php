<?php

namespace App\Utils;

use Illuminate\Support\Facades\File;

class XmlUtility
{
    /**
     * Convert an array or object to XML.
     *
     * @param array|object $data
     * @param string $rootNodeName
     * @return string
     */
    public static function toXml($data, $rootNodeName = 'root'): string
    {
        $xml = new \SimpleXMLElement("<$rootNodeName/>");
        self::arrayToXml((array)$data, $xml);
        return $xml->asXML();
    }

    /**
     * Recursive method to build XML from array.
     *
     * @param array $data
     * @param \SimpleXMLElement $xml
     */
    private static function arrayToXml(array $data, \SimpleXMLElement &$xml,$name = ''): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (self::is_numeric_array($value)) {
//                    // Add sibling with the parent tag name
                    self::arrayToXml($value, $xml,$key);
                } else {
                    $subnode = $xml->addChild($key);
                    self::arrayToXml($value, $subnode,$key);
                }
            } else {
                if (is_numeric($key)) {
                    // Add sibling tag with the key name
                    $xml->addChild($name, htmlspecialchars($value));
                } else {
                    $xml->addChild($key, htmlspecialchars($value));
                }
            }
        }
    }

    /**
     * Save XML to a file.
     *
     * @param string $filePath
     * @param string $xmlContent
     */
    public static function saveToFile(string $filePath, string $xmlContent): void
    {
        $directory = dirname($filePath);

        // 2. Ensure the directory exists (Laravel handles recursive creation)
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0775, true);
        }
        File::put($filePath, $xmlContent);
    }

    private static function is_numeric_array(array $value)
    {
        // check if tyhe array is [1,2,3] or [1=>'' , 2 =>'']
        return count(array_filter(array_keys($value), 'is_string')) === 0;
    }
}
