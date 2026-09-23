<?php

namespace App\Services\Statistics;

class PeriodSeriesFiller
{
    /**
     * @param list<string> $periodKeys
     * @param array<string, array<string, mixed>> $indexedByPeriod
     * @param array<string, mixed> $emptyDefaults
     * @return list<array<string, mixed>>
     */
    public function fill(array $periodKeys, array $indexedByPeriod, array $emptyDefaults): array
    {
        $series = [];

        foreach ($periodKeys as $key) {
            $series[] = array_merge(
                ['period' => $key],
                $indexedByPeriod[$key] ?? $emptyDefaults
            );
        }

        return $series;
    }
}
