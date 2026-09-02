<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Jobs\SendMerchantWebhook;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(
        readonly private AuditLogContract $auditLogService,
    )
    {
    }

    public function changePaymentStatus(int $paymentId, int $status): int
    {
        return DB::transaction(function () use ($paymentId, $status) {
            $payment = Payment::lockForUpdate()->findOrFail($paymentId);
            if ($payment->status !== Payment::STATUS_PENDING) {
                return $payment->status;
            }
            $payment->status = $status;
            $payment->save();
            $this->auditLogService->log(
                "payment-status-changed",
                null,
                null,
                $payment->cashbox->id,
                parameters: [
                    "payment_id" => $payment->id,
                    "status" => Payment::STATUS_PAID,
                ]
            );
            SendMerchantWebhook::dispatch($paymentId);
            return $payment->status;
        });

    }
}
