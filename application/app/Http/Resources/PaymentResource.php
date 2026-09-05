<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Payment $payment */
        $payment = $this->resource;

        $statusLabels = [
            Payment::STATUS_PENDING => 'pending',
            Payment::STATUS_PAID => 'paid',
            Payment::STATUS_FAILED => 'failed',
        ];

        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'description' => $payment->description,
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'status_label' => $statusLabels[$payment->status] ?? 'unknown',
            'success_url' => $payment->cashbox?->success_url,
            'fail_url' => $payment->cashbox?->fail_url,
        ];
    }
}
