<?php

namespace App\Services\Statistics\Periods;

use Carbon\Carbon;
use DateTimeInterface;

class MonthPeriodStrategy implements PeriodStrategy
{
    public function name(): string
    {
        return 'month';
    }

    public function sqlBucketExpression(string $column): string
    {
        return "to_char(date_trunc('month', {$column}), 'YYYY-MM-DD')";
    }

    public function periodKey(DateTimeInterface $dt): string
    {
        return Carbon::instance($dt)->startOfMonth()->format('Y-m-d');
    }

    public function iterateKeys(Carbon $from, Carbon $to): array
    {
        [$normalizedFrom, $normalizedTo] = $this->normalizeRange($from, $to);
        $keys = [];
        $cursor = $normalizedFrom->copy()->startOfMonth();

        while ($cursor->lte($normalizedTo)) {
            $keys[] = $this->periodKey($cursor);
            $cursor->addMonth();
        }

        return $keys;
    }

    public function normalizeRange(Carbon $from, Carbon $to): array
    {
        return [
            $from->copy()->startOfMonth()->startOfDay(),
            $to->copy()->endOfMonth()->endOfDay(),
        ];
    }
}
