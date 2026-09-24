<?php

namespace App\Data\Statistics;

use Carbon\Carbon;

readonly class CashboxStatisticsData
{
    public function __construct(
        public string $period,
        public Carbon $from,
        public Carbon $to,
    ) {
    }
}
