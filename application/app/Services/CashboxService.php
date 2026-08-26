<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Data\Cashboxes\CashboxData;
use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Support\Str;

class CashboxService
{
    public function __construct(
        private readonly AuditLogContract $auditLogService,
    )
    {

    }

    public function createAuditLog(User $creator, CashboxData $cashboxData)
    {
        $cashbox = new Cashbox([
            "name" => $cashboxData->name,
            "success_url" => $cashboxData->success_url,
            "fail_url" => $cashboxData->fail_url,
            "user_id" => $creator->id,
            "webhook_url" => $cashboxData->webhook_url,
        ]);
        $cashbox->secret_key = Str::random(20);
        $cashbox->save();
        $this->auditLogService->log("cashbox-created", $creator->id, cashbox_id: $cashbox->id);
    }

    public function userCashboxes(User $user)
    {
        return Cashbox::where("user_id", $user->id)
            ->orderByDesc("created_at")
            ->get()
            ->toResourceCollection();
    }

    public function updateCashbox(User $user, Cashbox $cashbox, CashboxData $cashboxData)
    {
        $cashbox->fill((array)$cashboxData);
        $cashbox->save();
        $this->auditLogService->log(
            "cashbox-updated",
            $user->id,
            cashbox_id: $cashbox->id,
            parameters: (array) $cashboxData
        );
    }

    public function deleteCashbox(User $user, Cashbox $cashbox): void
    {
        $cashbox->delete();
        $this->auditLogService->log(
            "cashbox-deleted",
            $user->id,
            cashbox_id: $cashbox->id,
        );
    }
}
