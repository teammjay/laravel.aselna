<?php

namespace Tests\Unit;

use App\Jobs\ProcessFeedGenerationJob;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OyderaFeedFileNumberTest extends TestCase
{
    /**
     * Expected values come from oydera's calculateFileNumber run in Java:
     * new BigInteger(md5(code)).mod(buckets). Both signs of the digest are covered.
     */
    public static function codes(): array
    {
        return [
            // code, mod 6, mod 7
            'E12' => ['E12', 0, 3],
            'M55 (negative digest)' => ['M55', 0, 2],
            'R03' => ['R03', 3, 4],
            'K50 (negative digest)' => ['K50', 0, 3],
            'XW63 (negative digest)' => ['XW63', 5, 0],
            'J55+USB' => ['J55+USB', 5, 1],
            'Z54+3Z35 (negative digest)' => ['Z54+3Z35', 4, 6],
            'A10+D03' => ['A10+D03', 3, 0],
        ];
    }

    #[DataProvider('codes')]
    public function testMatchesOyderaFileNumber(string $code, int $mod6, int $mod7): void
    {
        $this->assertSame($mod6, ProcessFeedGenerationJob::oyderaFileNumber($code, 6));
        $this->assertSame($mod7, ProcessFeedGenerationJob::oyderaFileNumber($code, 7));
    }

    /**
     * UZID's oydera feed (7 files) had E12 in feed_4.csv, so file number 3.
     */
    public function testUzidE12GoesToFourthFile(): void
    {
        $this->assertSame(4, ProcessFeedGenerationJob::oyderaFileNumber('E12', 7) + 1);
    }
}
