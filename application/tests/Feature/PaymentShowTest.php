<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentShowTest extends TestCase
{
    use RefreshDatabase;

    public function testShowPayment(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->state([
            'user_id' => $user->id,
            'success_url' => 'https://example.com/success',
            'fail_url' => 'https://example.com/fail',
        ])->create();
        $payment = Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'amount' => 1500,
            'description' => 'Test order',
            'order_id' => 'ord-1',
            'status' => Payment::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/payments/{$payment->id}");
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $payment->id,
                'amount' => 1500,
                'description' => 'Test order',
                'order_id' => 'ord-1',
                'status' => Payment::STATUS_PENDING,
                'status_label' => 'pending',
                'success_url' => 'https://example.com/success',
                'fail_url' => 'https://example.com/fail',
            ],
        ]);
        $response->assertJsonMissingPath('data.secret_key');
    }

    public function testShowMissingPayment(): void
    {
        $this->getJson('/api/payments/999999')->assertNotFound();
    }
}
