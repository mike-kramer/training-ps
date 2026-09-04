<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Jobs\SendMerchantWebhook;
use App\Models\Payment;

class OldPaymentService
{
    public function __construct(
        readonly private AuditLogContract $auditLogService,
    )
    {

    }

    public function cancelOldPayments()
    {
        $oldPayments = Payment::where("created_at", "<", now()->subMinutes(15))
            ->where("status", Payment::STATUS_PENDING)
            ->get();
        foreach ($oldPayments as $oldPayment) {
            $oldPayment->status = Payment::STATUS_FAILED;
            $oldPayment->save();

            SendMerchantWebhook::dispatch($oldPayment->id);

            $this->auditLogService->log(
                "timeout-payment-cancellation",
                null,
                null,
                $oldPayment->cashbox->id,
                parameters: [
                    "payment_id" => $oldPayment->id,
                ]
            );
        }
    }
}
