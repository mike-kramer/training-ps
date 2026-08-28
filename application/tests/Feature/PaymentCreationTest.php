<?php

namespace Tests\Feature;

use App\Contracts\SignatureContract;
use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class PaymentCreationTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;

    private User $user;
    private Cashbox $cashbox;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cashbox = Cashbox::factory()->state(["user_id" => $this->user->id])->create();
    }

    public function testCreatePayment(): void
    {
        $this->mock(
            SignatureContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('checkSignature')->andReturn(true);
            }
        );
        $resp = $this->post("/api/create-payment", [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100000,
            "description" => "Test payment",
            "order_id" => "1"
        ], [
            "X-Signature" => "good signature"
        ]);
        $resp->assertStatus(201);
        $resp->assertJsonStructure(["data" => ["url"]]);
        $this->assertDatabaseHas("payments", [
            "status" => 0,
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100000,
            "order_id" => "1"
        ]);
        $this->assertLog(
            "payment-created",
            null,
            null,
            $this->cashbox->id,
            parameters: [
                "order_id" => "1"
            ]
        );
    }

    public function testInvalidSignature(): void
    {
        $this->mock(
            SignatureContract::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('checkSignature')->andReturn(false);
            }
        );
        $resp = $this->post("/api/create-payment", [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100000,
            "description" => "Test payment",
            "order_id" => "1"
        ], [
            "X-Signature" => "bad signature"
        ]);
        $resp->assertStatus(400);
        $resp->assertJsonPath("message", "Invalid signature");
    }

    public function testMissignData()
    {
        $resp = $this->post("/api/create-payment", [

        ], [
            "X-Signature" => "good signature"
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(["cashbox_id", "amount", "order_id", "description"]);
    }

    public function testInvalidAmount()
    {
        $resp = $this->post("/api/create-payment", [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 0,
            "description" => "Test payment",
            "order_id" => "1"
        ], [
            "X-Signature" => "bad signature"
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors("amount");

        $resp = $this->post("/api/create-payment", [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 15.7,
            "description" => "Test payment",
            "order_id" => "1"
        ], [
            "X-Signature" => "bad signature"
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors("amount");
    }

    public function testNotUniqueOrderId()
    {
        $payment = Payment::factory()->state(["cashbox_id" => $this->cashbox->id])->create();
        $resp = $this->post("/api/create-payment", [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100000,
            "description" => "Test payment",
            "order_id" => $payment->order_id,
        ], [
            "X-Signature" => "good signature"
        ]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors("order_id");
    }
}
