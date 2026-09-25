<?php

namespace App\Console\Commands;

use App\Services\CurrencyRateService;
use Illuminate\Console\Command;

class UpdateCacheCommand extends Command
{
    protected $signature = 'update:cache';

    protected $description = 'Command description';

    public function handle(CurrencyRateService $rateService): void
    {
        $rateService->updateCache();
    }
}
