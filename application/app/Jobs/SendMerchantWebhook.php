<?php

namespace App\Jobs;

use App\Services\PaymentProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMerchantWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $payment_id,
        public int $retry_number = 1
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentProcessingService $service): void
    {
        $service->sendStatusWebhook($this->payment_id, $this->retry_number);
    }
}
