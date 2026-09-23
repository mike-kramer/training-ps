<?php

namespace App\Services\Statistics\Periods;

use Carbon\Carbon;
use DateTimeInterface;

interface PeriodStrategy
{
    public function name(): string;

    public function sqlBucketExpression(string $column): string;

    public function periodKey(DateTimeInterface $dt): string;

    /**
     * @return list<string>
     */
    public function iterateKeys(Carbon $from, Carbon $to): array;

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function normalizeRange(Carbon $from, Carbon $to): array;
}
