<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(["user_id", "name", "webhook_url", "success_url", "fail_url"])]
class Cashbox extends Model
{
    use HasFactory, SoftDeletes;
}
