<?php

namespace App\Data\Statistics;

use Carbon\Carbon;

readonly class AdminStatisticsData
{
    public function __construct(
        public string $period,
        public Carbon $from,
        public Carbon $to,
        public string $groupBy,
    ) {
    }
}
