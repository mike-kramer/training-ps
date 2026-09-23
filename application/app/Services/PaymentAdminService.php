<?php

namespace App\Services;

use App\Http\Resources\PaymentAdminResource;
use App\Models\Payment;

class PaymentAdminService
{
    public function getList()
    {
        return Payment::orderByDesc("created_at")
            ->with("cashbox.user")
            ->get()
            ->toResourceCollection(PaymentAdminResource::class);

    }
}
