<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cashbox\CashboxCreationRequest;
use App\Http\Requests\Cashbox\RevealSecretRequest;
use App\Models\Cashbox;
use App\Services\CashboxService;
use Illuminate\Validation\ValidationException;

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

    public function revealSecret(RevealSecretRequest $request, Cashbox $cashbox, CashboxService $cashboxService)
    {
        try {
            $secretKey = $cashboxService->revealSecret(
                auth()->user(),
                $cashbox,
                $request->validated('password'),
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'password' => 'invalid password',
            ]);
        }

        return [
            'secret_key' => $secretKey,
        ];
    }
}
