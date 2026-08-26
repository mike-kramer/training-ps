<?php

namespace App\Http\Controllers;

use App\Data\Cashboxes\CashboxData;
use App\Http\Requests\Cashbox\CashboxCreationRequest;
use App\Services\CashboxService;
use Illuminate\Http\Request;

class CashboxController extends Controller
{
    public function create(CashboxCreationRequest $request, CashboxService $cashboxService)
    {
        $cashboxService->createAuditLog(auth()->user(), $request->toDTO());
        return response()->json(["success" => true], 201);
    }
}
