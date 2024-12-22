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
    private static function arrayToXml(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild(is_numeric($key) ? "item$key" : $key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild(is_numeric($key) ? "item$key" : $key, htmlspecialchars($value));
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
        //file_put_contents(feeds/0004477a-c0ad-4492-222e-1c5ec80a442e.xml): Failed to open stream: No such file or directory
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $xmlContent);
    }
}
