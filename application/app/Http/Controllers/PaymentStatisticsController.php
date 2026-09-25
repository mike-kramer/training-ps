<?php

namespace App\Http\Controllers;

use App\Services\PaymentStatisticsService;

class PaymentStatisticsController
{
    public function __invoke(PaymentStatisticsService $service)
    {
        return [
            "success" => true,
            "data" => [
                "sum" => $service->getPaymentsSum()
            ]
        ];
    }
}
