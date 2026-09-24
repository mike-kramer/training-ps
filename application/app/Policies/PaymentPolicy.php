<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewPlatformStatistics(User $user): bool
    {
        return $user->hasPermission('statistics.view');
    }
}
