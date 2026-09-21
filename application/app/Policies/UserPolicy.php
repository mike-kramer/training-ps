<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;


    public function viewList(User $user): bool
    {
        return $user->hasPermission("users.view");
    }

    public function banUser(User $userWhoBans, User $userToBan): bool
    {
        return $userWhoBans->hasPermission("users.ban")
            && $userToBan->id !== $userWhoBans->id;
    }
}
