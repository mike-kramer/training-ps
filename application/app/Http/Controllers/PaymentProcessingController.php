<?php

namespace App\Http\Controllers;

use App\Services\PaymentProcessingService;
use Illuminate\Http\Request;

class PaymentProcessingController extends Controller
{
    public function __invoke(int $paymentId, Request $request, PaymentProcessingService $service)
    {
        return [
            "success" => true,
            "data" => [
                "newStatus" => $service->changePaymentStatus($paymentId, $request->status),
            ]
        ];
    }
}
