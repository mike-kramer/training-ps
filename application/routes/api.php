<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentShowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/health", HealthController::class);

Route::prefix("/auth")
    ->as("auth.")
    ->group(function () {
        Route::post("/register", [AuthController::class, "register"])
            ->name("register")
            ->middleware(["throttle:reg"]);
        Route::post("/login", [AuthController::class, "login"])
            ->name("login")
            ->middleware(["throttle:login"]);
        Route::post("verify", [AuthController::class, "verifyEmail"])
            ->name("verify");
        Route::post("/logout", [AuthController::class, "logout"])
            ->name("logout")
            ->middleware("auth:sanctum");
    });

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("create-payment", \App\Http\Controllers\PaymentCreationController::class)->name("create-payment");

Route::get("payments/{paymentId}", PaymentShowController::class)
    ->name("payments.show");

Route::post("payments/{paymentId}/change-status", \App\Http\Controllers\PaymentProcessingController::class)
    ->name("payments.change-status");

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix("/cashboxes")
        ->as("cashbox.")
        ->group(function () {
            Route::get("", [CashboxController::class, "index"])->name("index");
            Route::post("", [CashboxController::class, "create"])->name("create");
            Route::put("{cashbox}", [CashboxController::class, "updateCashbox"])
                ->middleware("can:update,cashbox")
                ->name("update");
            Route::delete("{cashbox}", [CashboxController::class, "deleteCashbox"])
                ->middleware("can:delete,cashbox")
                ->name("delete");
            Route::post("{cashbox}/reveal-secret", [CashboxController::class, "revealSecret"])
                ->middleware("can:update,cashbox")
                ->name("reveal-secret");
        });
});
