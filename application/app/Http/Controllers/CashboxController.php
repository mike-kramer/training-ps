<?php

namespace App\Http\Controllers;

use App\Data\Cashboxes\CashboxData;
use App\Http\Requests\Cashbox\CashboxCreationRequest;
use App\Models\Cashbox;
use App\Services\CashboxService;
use Illuminate\Http\Request;

class CashboxController extends Controller
{
    public function index(CashboxService $service)
    {
        return [
            "data" => $service->userCashboxes(auth()->user())
        ];
    }

    public function create(CashboxCreationRequest $request, CashboxService $cashboxService)
    {
        $cashboxService->createAuditLog(auth()->user(), $request->toDTO());
        return response()->json(["success" => true], 201);
    }

    public function updateCashbox(CashboxCreationRequest $request, Cashbox $cashbox, CashboxService $cashboxService)
    {
        $cashboxService->updateCashbox(auth()->user(), $cashbox, $request->toDTO());
        return ["success" => true];
    }

    public function deleteCashbox(Cashbox $cashbox, CashboxService $cashboxService)
    {
        $cashboxService->deleteCashbox(auth()->user(), $cashbox);
        return ["success" => true];
    }
}
