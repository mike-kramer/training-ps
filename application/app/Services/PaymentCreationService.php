<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Contracts\SignatureContract;
use App\Data\Payments\PaymentData;
use App\Exceptions\InvalidSignatureException;
use App\Models\Cashbox;
use App\Models\Payment;

class PaymentCreationService
{
    public function __construct(
        readonly private SignatureContract $signService,
        readonly private AuditLogContract  $auditLogService
    )
    {

    }

    public function createPayment(PaymentData $data, string $signature): string
    {
        $cashbox = Cashbox::findOrFail($data->cashbox_id);
        if (!$this->signService->checkSignature((array)$data, $cashbox->secret_key, $signature)) {
            throw new InvalidSignatureException();
        }
        $payment = Payment::create([
            ...(array)$data,
            "status" => Payment::STATUS_PENDING,
        ]);
        $this->auditLogService->log(
            "payment-created",
            null,
            null,
            $cashbox->id,
            parameters: [
                "order_id" => $payment->order_id,
            ]
        );
        return config("app.url") . "/process-payment/{$payment->id}";
    }
}
