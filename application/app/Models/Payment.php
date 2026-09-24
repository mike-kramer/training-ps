<?php

namespace App\Models;

use App\Policies\PaymentPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable("cashbox_id", "order_id", "amount", "description", "status")]
#[UsePolicy(PaymentPolicy::class)]
class Payment extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_PAID = 1;
    const STATUS_FAILED = 2;

    use HasFactory;

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }
}
