<?php

namespace App\Http\Requests\Statistics;

use App\Data\Statistics\AdminStatisticsData;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', Rule::in(['day', 'month', 'year'])],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group_by' => ['required', 'string', Rule::in(['cashbox', 'user'])],
        ];
    }

    public function toDTO(): AdminStatisticsData
    {
        return new AdminStatisticsData(
            period: $this->string('period')->toString(),
            from: Carbon::createFromFormat('Y-m-d', $this->string('from')->toString())->startOfDay(),
            to: Carbon::createFromFormat('Y-m-d', $this->string('to')->toString())->startOfDay(),
            groupBy: $this->string('group_by')->toString(),
        );
    }
}
