<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class CashboxTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;
    public function testCashboxSuccessfulCreation(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post("/api/cashboxes", [
            "name" => "Test Cashbox",
            "success_url" => "https://example.com/success",
            "fail_url" => "https://example.com/fail",
            "webhook_url" => "https://example.com/webhook",
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas("cashboxes", [
            "name" => "Test Cashbox",
            "success_url" => "https://example.com/success",
            "fail_url" => "https://example.com/fail",
            "webhook_url" => "https://example.com/webhook",
        ]);
        $cashbox = Cashbox::query()
            ->where("user_id", $user->id)
            ->where("name", "Test Cashbox")->first();
        $this->assertNotNull($cashbox->secret_key);

        $this->assertLog("cashbox-created", $user->id, cashbox_id: $cashbox->id);
    }

    public function testInvalidCashboxCreation()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post("/api/cashboxes", [
            "name" => "",
            "success_url" => "",
            "fail_url" => "",
            "webhook_url" => "",
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(["name", "success_url", "fail_url", "webhook_url"]);

        $response = $this->actingAs($user)->post("/api/cashboxes", [
            "name" => "some",
            "success_url" => "not-url",
            "fail_url" => "not-url",
            "webhook_url" => "not-url",
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(["success_url", "fail_url", "webhook_url"]);

        $cashbox = Cashbox::factory()->state(["user_id" => $user->id])->create();
        $response = $this->actingAs($user)->post("/api/cashboxes", [
            "name" => $cashbox->name,
            "success_url" => "http://example.com/success",
            "fail_url" => "http://example.com/fail",
            "webhook_url" => "http://example.com/webhook",
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(["name"]);
    }
}
