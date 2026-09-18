<?php

namespace App\Services\Assistants\UserFilters;

use App\Services\Assistants\UserFilters\UserFilterContract;
use Illuminate\Database\Eloquent\Builder;

class EmailFilter implements UserFilterContract
{

    public function alterBuilder(Builder $builder, array $filters): Builder
    {
       $builder->when(
           $filters["email"] ?? null,
           fn(Builder $query, $email) => $query->whereLike("email", "%{$email}%")
       );
       return $builder;
    }
}
