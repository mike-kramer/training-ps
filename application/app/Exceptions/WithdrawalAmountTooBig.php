<?php

namespace App\Exceptions;

class WithdrawalAmountTooBig extends \Exception
{
    public function __construct()
    {
        parent::__construct("Withdrawal amount too big");
    }
}
