<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;

class PaymentShowController extends Controller
{
    public function __invoke(int $paymentId)
    {
        $payment = Payment::with('cashbox')->findOrFail($paymentId);

        return [
            'data' => new PaymentResource($payment),
        ];
    }
}
