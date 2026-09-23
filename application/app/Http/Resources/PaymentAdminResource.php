<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAdminResource extends JsonResource
{
    protected static function newCollection($resource)
    {
        return new PaymentAdminCollection($resource);
    }

    public function toArray(Request $request): array
    {
        return $this->mapPayment();
    }

    private function mapPayment(): array
    {
        return [
            "id" => $this->id,
            "cashbox_id" => $this->cashbox_id,
            "user_id" => $this->cashbox->user_id,
            "cashbox_name" => $this->cashbox->name,
            "user_email" => $this->cashbox->user->email,
            "amount" => $this->amount,
            "status" => $this->status,
            "created_at" => $this->created_at->toISOString(),
        ];
    }
}
