<?php

namespace App\Services;

use App\Contracts\AuditLogContract;
use App\Models\User;
use App\Services\Assistants\UserFilters\UserFiltersApplier;
use Illuminate\Database\Eloquent\Builder;

class UserAdminService
{
    public function __construct(readonly private AuditLogContract $auditLog)
    {

    }
    public function getUserList($perPage = 10, array $filters = [])
    {
        $query = User::query()->orderBy("users.created_at", "desc");
        $query = UserFiltersApplier::applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function banUser(User $admin, User $userToBan): void
    {
        $userToBan->status = User::STATUS_BANNED;
        $userToBan->save();
        $this->auditLog->log(
            "user-ban",
            $userToBan->id,
            $admin->id,
            null,
        );
    }
}
