<?php

namespace App\Services;

use App\Models\User;
use App\Services\Assistants\UserFilters\UserFiltersApplier;
use Illuminate\Database\Eloquent\Builder;

class UserAdminService
{
    public function getUserList($perPage = 10, array $filters = [])
    {
        $query = User::query()->orderBy("users.created_at", "desc");
        $query = UserFiltersApplier::applyFilters($query, $filters);

        return $query->paginate($perPage);
    }
}
