<?php

namespace App\Services\Statistics\Periods;

use Carbon\Carbon;
use DateTimeInterface;

class YearPeriodStrategy implements PeriodStrategy
{
    public function name(): string
    {
        return 'year';
    }

    public function sqlBucketExpression(string $column): string
    {
        return "to_char(date_trunc('year', {$column}), 'YYYY-MM-DD')";
    }

    public function periodKey(DateTimeInterface $dt): string
    {
        return Carbon::instance($dt)->startOfYear()->format('Y-m-d');
    }

    public function iterateKeys(Carbon $from, Carbon $to): array
    {
        [$normalizedFrom, $normalizedTo] = $this->normalizeRange($from, $to);
        $keys = [];
        $cursor = $normalizedFrom->copy()->startOfYear();

        while ($cursor->lte($normalizedTo)) {
            $keys[] = $this->periodKey($cursor);
            $cursor->addYear();
        }

        return $keys;
    }

    public function normalizeRange(Carbon $from, Carbon $to): array
    {
        return [
            $from->copy()->startOfYear()->startOfDay(),
            $to->copy()->endOfYear()->endOfDay(),
        ];
    }
}
