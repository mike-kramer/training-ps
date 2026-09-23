<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Statistics\AdminStatisticsRequest;
use App\Services\Statistics\AdminStatisticsService;

class StatisticsController extends Controller
{
    public function __invoke(
        AdminStatisticsRequest $request,
        AdminStatisticsService $service,
    ) {
        return response()->json([
            'success' => true,
            'data' => $service->get($request->toDTO()),
        ]);
    }
}
