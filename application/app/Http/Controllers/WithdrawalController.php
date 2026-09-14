<?php

namespace App\Http\Controllers;

use App\Data\Withdrawal\WithdrawalData;
use App\Exceptions\WithdrawalAmountTooBig;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function createRequest(Request $request, WithdrawalService $withdrawalService)
    {
        try {
            return [
                "success" => true,
                "data" => [
                    "request_id" => $withdrawalService->createWithdrawalRequest(
                        auth()->user(),
                        new WithdrawalData(...$request->all())
                    )->id,
                ]
            ];
        } catch (WithdrawalAmountTooBig $e) {
            throw ValidationException::withMessages(["amount" => $e->getMessage()]);
        }
    }
}
