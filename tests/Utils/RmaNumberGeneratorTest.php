<?php

namespace Tests\Utils;

use App\Utils\RmaNumberGenerator;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RmaNumberGeneratorTest extends TestCase
{

    public function testGenerate()
    {
        $rmaGenerator = new RmaNumberGenerator();
        $rma = $rmaGenerator->generateRma("123456");
        Log::info('Generated RMA: ' . $rma);
    }
}
