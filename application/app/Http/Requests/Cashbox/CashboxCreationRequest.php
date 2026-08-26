<?php

namespace App\Http\Requests\Cashbox;

use App\Data\Cashboxes\CashboxData;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashboxCreationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique('cashboxes')
                    ->where(fn (Builder $query) => $query->where('user_id', $this->user()->id))
            ],
            "success_url" => ["required", "string", "max:255", "url"],
            "fail_url" => ["required", "string", "max:255", "url"],
            "webhook_url" => ["required", "string", "max:255", "url"],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function toDTO(): CashboxData
    {
        return new CashboxData(...$this->all());
    }
}
