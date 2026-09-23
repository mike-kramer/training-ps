<?php

namespace App\Services\Statistics;

use App\Data\Statistics\CashboxStatisticsData;
use App\Models\Cashbox;
use App\Models\Payment;
use App\Services\Statistics\Periods\PeriodStrategyFactory;

class MerchantStatisticsService
{
    public function __construct(
        private readonly PeriodSeriesFiller $filler,
    ) {
    }

    /**
     * @return list<array{period: string, payments_count: int, average_check: int}>
     */
    public function forCashbox(Cashbox $cashbox, CashboxStatisticsData $data): array
    {
        $strategy = PeriodStrategyFactory::make($data->period);
        [$from, $to] = $strategy->normalizeRange($data->from, $data->to);
        $bucketExpr = $strategy->sqlBucketExpression('payments.created_at');

        $rows = Payment::query()
            ->where('payments.status', Payment::STATUS_PAID)
            ->where('payments.cashbox_id', $cashbox->id)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw("{$bucketExpr} as bucket")
            ->selectRaw('COUNT(*) as payments_count')
            ->selectRaw('AVG(payments.amount) as average_check')
            ->groupByRaw($bucketExpr)
            ->orderByRaw($bucketExpr)
            ->get();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row->bucket] = [
                'payments_count' => (int) $row->payments_count,
                'average_check' => (int) round((float) $row->average_check),
            ];
        }

        return $this->filler->fill(
            $strategy->iterateKeys($data->from, $data->to),
            $indexed,
            [
                'payments_count' => 0,
                'average_check' => 0,
            ]
        );
    }
}
