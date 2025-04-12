<?php

namespace App\Utils;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Log;

class XmlValidator
{
    /**
     * Validate an XML file against an XSD schema.
     *
     * @param string $xmlPath Path to the XML file
     * @param string $xsdPath Path to the XSD schema
     * @return bool|string True if valid, error message if invalid
     */
    public function validate(string $xmlPath, string $xsdPath)
    {
        try {
            $dom = new DOMDocument();
            $dom->load($xmlPath);

            libxml_use_internal_errors(true);

            // Validate against the XSD
            if ($dom->schemaValidate($xsdPath)) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    Log::info('XML validation error: ' . $error->message);
                }
                return true;
            } else {
                return 'Validation failed. The XML does not conform to the XSD.';
            }
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
    }
}
