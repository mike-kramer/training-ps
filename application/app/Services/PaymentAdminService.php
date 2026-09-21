<?php

namespace App\Services;

use App\Http\Resources\PaymentAdminCollection;
use App\Models\Payment;

class PaymentAdminService
{
    public function getPayments()
    {
        return Payment::orderByDesc("created_at")
            ->with("cashbox.user")
            ->get()
            ->map(fn($payment) => $this->mapPayment($payment));
    }

    private function mapPayment($payment): array
    {
        return [
            "id" => $payment->id,
            "cashbox_id" => $payment->cashbox_id,
            "user_id" => $payment->cashbox->user_id,
            "cashbox_name" => $payment->cashbox->name,
            "user_email" => $payment->cashbox->user->email,
            "amount" => $payment->amount,
            "status" => $payment->status,
            "created_at" => $payment->created_at->toISOString(),
        ];
    }
}
