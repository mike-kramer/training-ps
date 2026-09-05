<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Contracts\SignatureContract;
use App\Jobs\SendMerchantWebhook;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaymentProcessingService
{
    public function __construct(
        readonly private AuditLogContract $auditLogService,
        readonly private SignatureContract $signatureService,
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

    public function sendStatusWebhook(int $payment_id, int $retryNumber): void
    {
        $payment = Payment::find($payment_id);
        $webhookUrl = $payment->cashbox->webhook_url;
        $data = [
            "order_id" => $payment->order_id,
            "amount" => $payment->amount,
            "status" => $payment->status,
        ];
        $signature = $this->signatureService->sign($data, $payment->cashbox->secret_key);
        try {
            Http::withHeaders([
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->post($webhookUrl, $data)->throw();
            $this->auditLogService->log(
                "webhook-called",
                null,
                null,
                $payment->cashbox_id,
                parameters: [
                    "payment_id" => $payment->id,
                    "status" => $payment->status,
                    "url" => $payment->cashbox->webhook_url,
                ]
            );
        } catch (ConnectionException | RequestException $exception) {
            if (
                $exception instanceof RequestException
                && $exception->response->status() < 500
            ) {
                $this->auditLogService->log(
                    "webhook-failed-with-40x-code",
                    null,
                    null,
                    $payment->cashbox->id,
                    parameters: [
                        "code" => $exception->response->status(),
                        "payment_id" => $payment->id,
                        "status" => $payment->status,
                        "url" => $payment->cashbox->webhook_url,
                    ]
                );
            } elseif ($retryNumber < 5) {
                SendMerchantWebhook::dispatch($payment_id, $retryNumber + 1)
                    ->delay(now()->addMinutes(2 ** ($retryNumber - 1)));
                $this->auditLogService->log("webhook-failed-with-retry",
                    null,
                    null,
                    $payment->cashbox->id,
                    parameters: [
                        "payment_id" => $payment->id,
                        "status" => $payment->status,
                        "url" => $payment->cashbox->webhook_url,
                    ]
                );
            } else {
                $this->auditLogService->log("webhook-failed-5-times",
                    null,
                    null,
                    $payment->cashbox->id,
                    parameters: [
                        "payment_id" => $payment->id,
                        "status" => $payment->status,
                        "url" => $payment->cashbox->webhook_url,
                    ]
                );
            }
        }
    }
}
