<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class WithdrawalRequestTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;

    private User $user;
    private Cashbox $cashbox;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cashbox = Cashbox::factory()->state(['user_id' => $this->user->id])->create();
        Payment::factory()->state([
            "cashbox_id" => $this->cashbox->id,
            "status" => Payment::STATUS_PAID,
            "amount" => 100_000_000
        ])->createMany(3);
    }


    public function testSuccessWithdrawalRequest(): void
    {
        $this->actingAs($this->user);
        $response = $this->post('/api/withdrawals', [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
        ], [
            "Idempotence-Key" => \Str::uuid(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(["data" => ["request_id"]]);
        $requestId = $response->json()["data"]["request_id"];
        $this->assertDatabaseHas("withdrawal_requests", [
            "user_id" => $this->user->id,
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
            "status" => WithdrawalRequest::STATUS_PENDING,
        ]);

        $this->assertLog(
            "withdrawal-request-created",
            $this->user->id,
            null,
            $this->cashbox->id,
            parameters: [
                "amount" => 100_000_000,
                "request_id" => $requestId,
            ]
        );
    }

    public function testIdempotenceOfWithdrawalRequest(): void
    {
        $key = \Str::uuid();
        $this->actingAs($this->user);

        $response = $this->post('/api/withdrawals', [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
        ], [
            "Idempotence-Key" => $key,
        ]);

        $response->assertStatus(200);

        $response = $this->post('/api/withdrawals', [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
        ], [
            "Idempotence-Key" => $key,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(["data" => ["request_id"]]);
        $response->assertJsonPath("duplicated", true);

        $countRequest = WithdrawalRequest::where([
            "user_id" => $this->user->id,
            "cashbox_id" => $this->cashbox->id,
            "amount" => 100_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
        ])->count();
        $this->assertEquals(1, $countRequest);
    }

    public function testAmountToBig(): void
    {
        $this->actingAs($this->user);
        $response = $this->post('/api/withdrawals', [
            "cashbox_id" => $this->cashbox->id,
            "amount" => 500_000_000,
            "bank_code" => "04567",
            "account_number" => "1234567890",
        ], [
            "Idempotence-Key" => \Str::uuid(),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors("amount");
    }
}
