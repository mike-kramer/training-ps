<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\CashboxStatisticsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentShowController;
use App\Http\Controllers\WithdrawalController;
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
    Route::middleware(\App\Http\Middleware\BlockBannedUsersMiddleware::class)->group(function () {
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
                Route::get("{cashbox}/statistics", CashboxStatisticsController::class)
                    ->middleware("can:viewStatistics,cashbox")
                    ->name("statistics");
            });

        Route::prefix("/withdrawals")
            ->as("withdrawal.")
            ->group(function () {
                Route::post("", [WithdrawalController::class, "createRequest"])
                    ->middleware(\App\Http\Middleware\IdempotenceMiddleware::class)
                    ->name("create");
            });
        Route::prefix("/admin")
            ->as("admin.")
            ->group(function () {
                Route::prefix("/users")->as("users.")->group(function () {
                    Route::get(
                        "",
                        [\App\Http\Controllers\Admin\UsersController::class, "usersList"]
                    )
                        ->middleware('can:viewList,App\Models\User')
                        ->name("usersList");
                    Route::post("/{userToBan}/ban", [\App\Http\Controllers\Admin\UsersController::class, "ban"])
                        ->middleware('can:banUser,userToBan')
                        ->name("ban");
                });
                Route::prefix("/payments")->as("payments.")->group(function () {
                    Route::get("", [\App\Http\Controllers\Admin\PaymentsController::class, "getList"])
                        ->name("get-list");
                });
                Route::get("/statistics", \App\Http\Controllers\Admin\StatisticsController::class)
                    ->middleware('can:viewPlatformStatistics,App\Models\Payment')
                    ->name("statistics");
            });
    });
});
