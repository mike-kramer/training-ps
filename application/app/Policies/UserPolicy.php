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
}
