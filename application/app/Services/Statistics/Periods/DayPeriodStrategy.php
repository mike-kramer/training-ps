<?php

namespace App\Services\Statistics\Periods;

use Carbon\Carbon;
use DateTimeInterface;

class DayPeriodStrategy implements PeriodStrategy
{
    public function name(): string
    {
        return 'day';
    }

    public function sqlBucketExpression(string $column): string
    {
        return "to_char(date_trunc('day', {$column}), 'YYYY-MM-DD')";
    }

    public function periodKey(DateTimeInterface $dt): string
    {
        return Carbon::instance($dt)->format('Y-m-d');
    }

    public function iterateKeys(Carbon $from, Carbon $to): array
    {
        [$normalizedFrom, $normalizedTo] = $this->normalizeRange($from, $to);
        $keys = [];
        $cursor = $normalizedFrom->copy()->startOfDay();

        while ($cursor->lte($normalizedTo)) {
            $keys[] = $this->periodKey($cursor);
            $cursor->addDay();
        }

        return $keys;
    }

    public function normalizeRange(Carbon $from, Carbon $to): array
    {
        return [
            $from->copy()->startOfDay(),
            $to->copy()->endOfDay(),
        ];
    }
}
