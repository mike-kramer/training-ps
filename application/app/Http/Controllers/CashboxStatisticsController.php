<?php

namespace App\Http\Controllers;

use App\Http\Requests\Statistics\CashboxStatisticsRequest;
use App\Models\Cashbox;
use App\Services\Statistics\MerchantStatisticsService;

class CashboxStatisticsController extends Controller
{
    public function __invoke(
        CashboxStatisticsRequest $request,
        Cashbox $cashbox,
        MerchantStatisticsService $service,
    ) {
        return response()->json([
            'success' => true,
            'data' => $service->forCashbox($cashbox, $request->toDTO()),
        ]);
    }
}
