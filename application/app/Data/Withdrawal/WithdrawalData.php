<?php

namespace App\Data\Withdrawal;

readonly class WithdrawalData
{
    public function __construct(
        public int $cashbox_id,
        public int $amount,
        public string $bank_code,
        public string $account_number,
    )
    {

    }
}
