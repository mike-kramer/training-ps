<?php

namespace App\Data\Cashboxes;

use App\Models\Cashbox;

readonly class CashboxData
{
    public function __construct(
        public string $name,
        public string $success_url,
        public string $fail_url,
        public string $webhook_url,
    ) {

    }
}
