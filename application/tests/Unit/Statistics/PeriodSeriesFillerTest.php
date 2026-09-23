<?php

namespace Tests\Unit\Statistics;

use App\Services\Statistics\PeriodSeriesFiller;
use PHPUnit\Framework\TestCase;

class PeriodSeriesFillerTest extends TestCase
{
    public function testFillsGapsInTheMiddleAndEdges(): void
    {
        $filler = new PeriodSeriesFiller();
        $keys = ['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04'];
        $indexed = [
            '2026-09-02' => ['payments_count' => 2, 'average_check' => 100],
            '2026-09-03' => ['payments_count' => 1, 'average_check' => 50],
        ];

        $result = $filler->fill($keys, $indexed, [
            'payments_count' => 0,
            'average_check' => 0,
        ]);

        $this->assertSame([
            ['period' => '2026-09-01', 'payments_count' => 0, 'average_check' => 0],
            ['period' => '2026-09-02', 'payments_count' => 2, 'average_check' => 100],
            ['period' => '2026-09-03', 'payments_count' => 1, 'average_check' => 50],
            ['period' => '2026-09-04', 'payments_count' => 0, 'average_check' => 0],
        ], $result);
    }
}
