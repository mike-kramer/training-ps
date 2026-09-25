<?php

namespace App\Services;

use App\Models\Payment;

class PaymentStatisticsService
{
    public function getPaymentsSum(): int
    {
        $cachingDate = \Cache::get("paymentSumCacheDate");
        $cachedSum = \Cache::get("paymentSumCachedSum", 0);

        $query = Payment::query()
            ->when(
                $cachingDate,
                fn($query) => $query->where('created_at', '>', $cachingDate)
            )
            ->where("status", Payment::STATUS_PAID);
        return $query->sum("amount") + $cachedSum;
    }

    public function updatePaymentsSumCache(): void
    {
        $now = now();
        $sum = Payment::query()->where("status", Payment::STATUS_PAID)
            ->where("created_at", "<=", $now)
            ->sum("amount");
        \Cache::put("paymentSumCacheDate", $now);
        \Cache::put("paymentSumCachedSum", $sum);
    }
}
