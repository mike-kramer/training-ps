<?php

namespace App\Http\Requests\Payment;

use App\Data\Payments\PaymentData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentCreationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "cashbox_id" => "required|integer|exists:cashboxes,id",
            "amount" => "required|integer|min:1",
            "description" => "required|string",
            "order_id" => [
                "required",
                Rule::unique("payments", "order_id")
                ->where(function ($query) {
                    return $query->where("cashbox_id", $this->cashbox_id);
                })
            ]
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function toDTO(): PaymentData
    {
        return new PaymentData(...$this->all());
    }
}
