<?php

namespace App\Utils\Amazon;

use Illuminate\Support\Facades\Log;

class AmazonRequirementCalculator
{
    private $schema;

    private $requiredFields = [];

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function getRequiredFieldsV2()
    {
        if (empty($this->requiredFields)) {
            Log::info('Came Inside Schema');
            $this->parseSchema($this->schema);
        }
        return $this->requiredFields;
    }

    public function parseSchema($schema)
    {
        // Get basic required fields at root level
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!in_array($field, $this->requiredFields)) {
                    $this->requiredFields[] = $field;
                }
            }
        }
//        Log::info("Current Required " . json_encode($this->requiredFields));

        // Parse allOf - ALL conditions must be met
        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            foreach ($schema['allOf'] as $subSchema) {
                $this->parseSchema($subSchema);
            }
        }



        //Log::info('Schema :: ' . json_encode($schema));
        if (isset($schema['if'])) {
//            Log::info('Came Inside If');
            $condition = $this->extractCondition($schema['if']);

            //Log::info('Condition :: ' . json_encode($condition));

            if (isset($schema['then']) && $condition) {
                if (isset($schema['then']['required'])) {
                    foreach ($schema['then']['required'] as $field) {
                        if (!in_array($field, $this->requiredFields)) {
                            $this->requiredFields[] = $field;
                        }
                    }
                }
            }
            if (!$condition) {
                //Log::info('Current Schema ' . json_encode($schema));
            }
        }
    }

    private function extractCondition($ifSchema) : bool
    {
        $conditions = [];
        //Log::info('Condition :: ' . json_encode($ifSchema));

        if (isset($ifSchema['anyOf'])) {
            $result = false;
            foreach ($ifSchema['anyOf'] as $anyOf) {
                $newResult = $this->extractCondition($anyOf);
                //Log::info('Any OF :: ' . json_encode($newResult));
                $result = $result || $newResult;
            }
            return $result;
        }

        if (isset($ifSchema['allOf'])) {
            $result = true;
            foreach ($ifSchema['allOf'] as $allOf) {
                $newResult = $this->extractCondition($allOf);
                //Log::info('All OF :: ' . json_encode($newResult));
                $result = $result && $newResult;
            }
            return $result;
        }

        if (isset($ifSchema['not'])) {
            return !$this->extractCondition($ifSchema['not']);
        }

        if (isset($ifSchema['required'])) {
            foreach ($ifSchema['required'] as $field) {
                if (!in_array($field, $this->requiredFields)) {
                    return false;
                }
            }
        }

//        if (isset($ifSchema['properties'])) {
//            foreach ($ifSchema['properties'] as $prop => $value) {
//                $conditions[$prop] = $value;
//            }
//        }

        return false;
    }
}
