<?php

namespace Tests\Services\Auth;

use App\Services\Auth\TemuAuthService;
use Tests\TestCase;

class TemuAuthServiceTest extends TestCase
{

    public function testGetGoodsInfo()
    {
        $temuAuthService = new TemuAuthService('https://openapi-b-us.temu.com/openapi/router', 'd3941506bdcfc2aa3c969f340fc3dfeu', '341a86fc634897324e533f1553bde3195a70b51c');
        $response = $temuAuthService->getGoodsInfo('602516079113451');

    }
}
