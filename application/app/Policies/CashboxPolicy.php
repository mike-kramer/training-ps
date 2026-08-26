<?php

namespace App\Policies;

use App\Models\Cashbox;
use App\Models\User;

class CashboxPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Cashbox $cashbox)
    {
        return $user->id === $cashbox->user_id;
    }

    public function delete(User $user, Cashbox $cashbox)
    {
        return $user->id === $cashbox->user_id;
    }
}
