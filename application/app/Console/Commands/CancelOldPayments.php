<?php

namespace App\Console\Commands;

use App\Services\OldPaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cancel-old-payments')]
#[Description('Cancels Old Payments')]
class CancelOldPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OldPaymentService $oldPaymentService)
    {
        $oldPaymentService->cancelOldPayments();
    }
}
