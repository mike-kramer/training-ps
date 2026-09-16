<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Data\Withdrawal\WithdrawalData;
use App\Exceptions\WithdrawalAmountTooBig;
use App\Models\Payment;
use App\Models\User;
use App\Models\WithdrawalRequest;

class WithdrawalService
{
    public function __construct(readonly private AuditLogContract $auditLogService)
    {
    }

    public function createWithdrawalRequest(User $user, WithdrawalData $withdrawalData): WithdrawalRequest
    {
        return \DB::transaction(function () use ($user, $withdrawalData) {
            if (\DB::transactionLevel() === 1) {
                \DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            }
            $userPaymentsAmount = Payment::where("cashbox_id", $withdrawalData->cashbox_id)
                ->where("status", Payment::STATUS_PAID)
                ->sum("amount");
            $userWithdrawnAmount = WithdrawalRequest::where("cashbox_id", $withdrawalData->cashbox_id)
                ->whereIn("status", [
                    WithdrawalRequest::STATUS_SUCCESS,
                    WithdrawalRequest::STATUS_PENDING
                ])
                ->sum("amount");
            $userMoneyAmount = $userPaymentsAmount - $userWithdrawnAmount;

            if ($withdrawalData->amount > $userMoneyAmount) {
                throw new WithdrawalAmountTooBig();
            }

            $withdrawalRequest = new WithdrawalRequest((array)$withdrawalData);
            $withdrawalRequest->status = WithdrawalRequest::STATUS_PENDING;
            $withdrawalRequest->user_id = $user->id;
            $withdrawalRequest->save();

            $this->auditLogService->log(
                "withdrawal-request-created",
                $user->id,
                null,
                $withdrawalData->cashbox_id,
                parameters: [
                    "amount" => 100_000_000,
                    "request_id" => $withdrawalRequest->id,
                ]
            );
            return $withdrawalRequest;
        }, 3);
    }
}
