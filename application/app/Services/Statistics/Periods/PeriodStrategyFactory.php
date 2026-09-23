<?php

namespace App\Services\Statistics\Periods;

use InvalidArgumentException;

class PeriodStrategyFactory
{
    public static function make(string $period): PeriodStrategy
    {
        return match ($period) {
            'day' => new DayPeriodStrategy(),
            'month' => new MonthPeriodStrategy(),
            'year' => new YearPeriodStrategy(),
            default => throw new InvalidArgumentException("Unknown period: {$period}"),
        };
    }
}
