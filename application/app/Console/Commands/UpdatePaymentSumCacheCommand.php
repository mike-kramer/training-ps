<?php

namespace App\Console\Commands;

use App\Services\PaymentStatisticsService;
use Illuminate\Console\Command;

class UpdatePaymentSumCacheCommand extends Command
{
    protected $signature = 'update:payment-sum-cache';

    protected $description = 'Command description';

    public function handle(PaymentStatisticsService $service): void
    {
        $service->updatePaymentsSumCache();
    }
}
