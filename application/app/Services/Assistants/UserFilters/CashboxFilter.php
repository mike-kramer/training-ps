<?php

namespace App\Services\Assistants\UserFilters;

use App\Services\Assistants\UserFilters\UserFilterContract;
use Illuminate\Database\Eloquent\Builder;

class CashboxFilter implements UserFilterContract
{

    public function alterBuilder(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when(
                $filters["cashbox_id"] ?? null,
                fn(Builder $query, $cashbox_id) => $query->hasCashbox($cashbox_id)
            )
            ->when(
                $filters["cashbox_name"] ?? null,
                fn(Builder $query, $cashbox_name) => $query->hasCashboxWithName($cashbox_name)
            );
    }
}
