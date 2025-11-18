<?php

namespace Tests\Utils\Amazon;

use App\Utils\Amazon\AmazonRequirementCalculator;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AmazonRequirementCalculatorTest extends TestCase
{

    public function testParseSchema()
    {
        $jsonEncode = '{"if":{"anyOf":[{"allOf":[{"not":{"required":["parentage_level"],"properties":{"parentage_level":{"items":{"required":["value"]}}}}},{"not":{"required":["skip_offer"],"properties":{"skip_offer":{"contains":{"required":["value"],"properties":{"value":{"enum":[true]}}}}}}}]},{"allOf":[{"not":{"required":["parentage_level"],"properties":{"parentage_level":{"contains":{"required":["value"],"properties":{"value":{"enum":["parent"]}}}}}}},{"not":{"required":["skip_offer"],"properties":{"skip_offer":{"contains":{"required":["value"],"properties":{"value":{"enum":[true]}}}}}}}]}]},"then":{"required":["condition_type"]}}';

        $schema = json_decode($jsonEncode, true);

        $amazonRequirementCalculator = new AmazonRequirementCalculator($schema);
        $reqFields = $amazonRequirementCalculator->getRequiredFieldsV2();
        Log::info('Req Fields :: ' . json_encode($reqFields));
    }
}
