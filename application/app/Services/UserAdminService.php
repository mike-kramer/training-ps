<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserAdminService
{
    public function getUserList($perPage = 10, array $filters = [])
    {
        return User::orderBy("users.created_at", "desc")
            ->when(
                $filters["email"] ?? null,
                fn(Builder $query, $email) => $query->whereLike("email", "%{$email}%")
            )
            ->when(
                $filters["cashbox_id"] ?? null,
                fn(Builder $query, $cashbox_id) => $query->hasCashbox($cashbox_id)
            )
            ->when(
                $filters["cashbox_name"] ?? null,
                fn(Builder $query, $cashbox_name) => $query->hasCashboxWithName($cashbox_name)
            )
            ->when(
                $filters["created_from"] ?? null,
                fn(Builder $query, $created_from) => $query->where("users.created_at", ">=", $created_from)
            )
            ->paginate($perPage);
    }
}
