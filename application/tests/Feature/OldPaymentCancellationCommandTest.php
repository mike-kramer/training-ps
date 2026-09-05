<?php

namespace Tests\Feature;

use App\Console\Commands\CancelOldPayments;
use App\Jobs\SendMerchantWebhook;
use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class OldPaymentCancellationCommandTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;
    /**
     * A basic feature test example.
     */
    public function testCancelCommands(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->state([
            'user_id' => $user->id,
        ])->create();
        $payment = Payment::factory()->create([
            "cashbox_id" => $cashbox->id,
            "created_at" => now()->subMinutes(30),
        ]);

        $this->artisan("app:cancel-old-payments");
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_FAILED, $payment->status);
        Queue::assertPushed(SendMerchantWebhook::class, function (SendMerchantWebhook $job) use ($payment) {
            return $job->payment_id === $payment->id;
        });
        $this->assertLog(
            "timeout-payment-cancellation",
            null,
            null,
            $cashbox->id,
            parameters: [
                "payment_id" => $payment->id,
            ]
        );
    }
}
