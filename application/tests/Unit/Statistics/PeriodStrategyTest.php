<?php

namespace Tests\Unit\Statistics;

use App\Services\Statistics\Periods\DayPeriodStrategy;
use App\Services\Statistics\Periods\MonthPeriodStrategy;
use App\Services\Statistics\Periods\PeriodStrategyFactory;
use App\Services\Statistics\Periods\YearPeriodStrategy;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PeriodStrategyTest extends TestCase
{
    public function testFactoryCreatesStrategies(): void
    {
        $this->assertInstanceOf(DayPeriodStrategy::class, PeriodStrategyFactory::make('day'));
        $this->assertInstanceOf(MonthPeriodStrategy::class, PeriodStrategyFactory::make('month'));
        $this->assertInstanceOf(YearPeriodStrategy::class, PeriodStrategyFactory::make('year'));
    }

    public function testFactoryRejectsUnknownPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PeriodStrategyFactory::make('week');
    }

    public function testDayPeriodKeysAndIteration(): void
    {
        $strategy = new DayPeriodStrategy();
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-03');

        $this->assertSame('2026-09-02', $strategy->periodKey(Carbon::parse('2026-09-02 15:30:00')));
        $this->assertSame(
            ['2026-09-01', '2026-09-02', '2026-09-03'],
            $strategy->iterateKeys($from, $to)
        );

        [$normalizedFrom, $normalizedTo] = $strategy->normalizeRange($from, $to);
        $this->assertTrue($normalizedFrom->equalTo(Carbon::parse('2026-09-01')->startOfDay()));
        $this->assertTrue($normalizedTo->equalTo(Carbon::parse('2026-09-03')->endOfDay()));
        $this->assertStringContainsString("date_trunc('day'", $strategy->sqlBucketExpression('created_at'));
    }

    public function testMonthPeriodKeysAndIteration(): void
    {
        $strategy = new MonthPeriodStrategy();
        $from = Carbon::parse('2026-01-15');
        $to = Carbon::parse('2026-03-10');

        $this->assertSame('2026-02-01', $strategy->periodKey(Carbon::parse('2026-02-20')));
        $this->assertSame(
            ['2026-01-01', '2026-02-01', '2026-03-01'],
            $strategy->iterateKeys($from, $to)
        );
    }

    public function testYearPeriodKeysAndIteration(): void
    {
        $strategy = new YearPeriodStrategy();
        $from = Carbon::parse('2024-06-01');
        $to = Carbon::parse('2026-03-01');

        $this->assertSame('2025-01-01', $strategy->periodKey(Carbon::parse('2025-08-01')));
        $this->assertSame(
            ['2024-01-01', '2025-01-01', '2026-01-01'],
            $strategy->iterateKeys($from, $to)
        );
    }
}
