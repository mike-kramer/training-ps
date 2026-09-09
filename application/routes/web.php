<?php

use Illuminate\Support\Facades\Route;

// SPA fallback — do not swallow API / Sanctum / health / public storage URLs.
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api(?:/|$)|sanctum(?:/|$)|up(?:/|$)|storage(?:/|$)|horizon(?:/|$)).*');
