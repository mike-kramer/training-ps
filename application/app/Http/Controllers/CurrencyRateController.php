<?php

namespace App\Http\Controllers;

use App\Services\CurrencyRateService;

class CurrencyRateController extends Controller
{
    public function __invoke(CurrencyRateService $rateService)
    {
        return [
            "success" => true,
            "data" => $rateService->getRates()
        ];
    }
}
