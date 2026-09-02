<?php

namespace Tests\Feature;

use App\Jobs\SendMerchantWebhook;
use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;
    private User $user;
    private Cashbox $cashbox;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cashbox = Cashbox::factory()->state([
            'user_id' => $this->user->id,
        ])->create();
    }

    public function testPaymentSuccess(): void
    {
        \Queue::fake();
        $payment = Payment::factory()->create([
            "cashbox_id" => $this->cashbox->id,
            "status" => Payment::STATUS_PENDING
        ]);
        $response = $this->post("/api/payments/{$payment->id}/change-status", [
            "status" => Payment::STATUS_PAID,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas("payments", [
            "id" => $payment->id,
            "status" => Payment::STATUS_PAID,
        ]);
        \Queue::assertPushed(SendMerchantWebhook::class, function (SendMerchantWebhook $job) use ($payment) {
            return $payment->id === $job->payment_id;
        });
        $this->assertLog(
            "payment-status-changed",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "payment_id" => $payment->id,
                "status" => Payment::STATUS_PAID,
            ]
        );
    }

    public function testPaymentProcessingIdempotence(): void
    {
        \Queue::fake();
        $payment = Payment::factory()->state([
            "cashbox_id" => $this->cashbox->id,
            "status" => Payment::STATUS_PAID,
        ])->create();
        $response = $this->post("/api/payments/{$payment->id}/change-status", [
            "status" => Payment::STATUS_PAID,
        ]);
        $response->assertStatus(200);
        \Queue::assertNotPushed(SendMerchantWebhook::class);
    }
}
