<?php

namespace App\Services\Statistics;

use App\Data\Statistics\AdminStatisticsData;
use App\Models\Payment;
use App\Services\Statistics\Periods\PeriodStrategyFactory;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdminStatisticsService
{
    public function __construct(
        private readonly PeriodSeriesFiller $filler,
    ) {
    }

    /**
     * @return list<array{entity_id: int, entity_label: string, periods: list<array{period: string, income: int}>}>
     */
    public function get(AdminStatisticsData $data): array
    {
        $strategy = PeriodStrategyFactory::make($data->period);
        [$from, $to] = $strategy->normalizeRange($data->from, $data->to);
        $bucketExpr = $strategy->sqlBucketExpression('payments.created_at');
        $periodKeys = $strategy->iterateKeys($data->from, $data->to);

        $rows = match ($data->groupBy) {
            'cashbox' => $this->aggregateByCashbox($bucketExpr, $from, $to),
            'user' => $this->aggregateByUser($bucketExpr, $from, $to),
            default => throw new InvalidArgumentException("Unknown group_by: {$data->groupBy}"),
        };

        return $this->buildEntitySeries($rows, $periodKeys);
    }

    private function aggregateByCashbox(string $bucketExpr, $from, $to): Collection
    {
        return Payment::query()
            ->join('cashboxes', 'cashboxes.id', '=', 'payments.cashbox_id')
            ->where('payments.status', Payment::STATUS_PAID)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw("{$bucketExpr} as bucket")
            ->selectRaw('cashboxes.id as entity_id')
            ->selectRaw('cashboxes.name as entity_label')
            ->selectRaw('SUM(payments.amount) as total_amount')
            ->groupByRaw("{$bucketExpr}, cashboxes.id, cashboxes.name")
            ->orderByRaw('cashboxes.id, ' . $bucketExpr)
            ->get();
    }

    private function aggregateByUser(string $bucketExpr, $from, $to): Collection
    {
        return Payment::query()
            ->join('cashboxes', 'cashboxes.id', '=', 'payments.cashbox_id')
            ->join('users', 'users.id', '=', 'cashboxes.user_id')
            ->where('payments.status', Payment::STATUS_PAID)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw("{$bucketExpr} as bucket")
            ->selectRaw('users.id as entity_id')
            ->selectRaw('users.email as entity_label')
            ->selectRaw('SUM(payments.amount) as total_amount')
            ->groupByRaw("{$bucketExpr}, users.id, users.email")
            ->orderByRaw('users.id, ' . $bucketExpr)
            ->get();
    }

    /**
     * @param list<string> $periodKeys
     * @return list<array{entity_id: int, entity_label: string, periods: list<array{period: string, income: int}>}>
     */
    private function buildEntitySeries(Collection $rows, array $periodKeys): array
    {
        $byEntity = [];

        foreach ($rows as $row) {
            $entityId = (int) $row->entity_id;
            if (!isset($byEntity[$entityId])) {
                $byEntity[$entityId] = [
                    'entity_id' => $entityId,
                    'entity_label' => (string) $row->entity_label,
                    'indexed' => [],
                ];
            }

            $byEntity[$entityId]['indexed'][(string) $row->bucket] = [
                'income' => intdiv((int) $row->total_amount, 10),
            ];
        }

        $result = [];
        foreach ($byEntity as $entity) {
            $result[] = [
                'entity_id' => $entity['entity_id'],
                'entity_label' => $entity['entity_label'],
                'periods' => $this->filler->fill(
                    $periodKeys,
                    $entity['indexed'],
                    ['income' => 0]
                ),
            ];
        }

        return $result;
    }
}
