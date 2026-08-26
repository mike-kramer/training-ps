<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
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

    public function testCashboxList(): void
    {
        $user = User::factory()->create();

        $cashboxes = Cashbox::factory()
            ->count(3)
            ->state(new Sequence(
                ["user_id" => $user->id, "created_at" => now()],
                ["user_id" => $user->id, "created_at" => now()->subMinute()],
                ["user_id" => $user->id, "created_at" => now()->subMinutes(3)],
            ))
            ->create();

        $otherUser = User::factory()->create();
        Cashbox::factory()->state(["user_id" => $otherUser->id])->create();

        $response = $this->actingAs($user)->get("/api/cashboxes");
        $response->assertOk();
        $data = $cashboxes->map(function (Cashbox $cashbox) {
            $cashboxData = $cashbox->toArray();
            unset($cashboxData["secret_key"]);
            return $cashboxData;
        })->toArray();
        $response->assertJson([
            "data" => $data,
        ]);
        $response->assertJsonMissingPath("data.0.secret_key");
    }

    public function testUpdateCashbox(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()
            ->state(["user_id" => $user->id])
            ->create();
        $newData = [
            "name" => "Test Cashbox",
            "success_url" => "https://example.com/success",
            "fail_url" => "https://example.com/fail",
            "webhook_url" => "https://example.com/webhook",
        ];
        $resp = $this->actingAs($user)->put("/api/cashboxes/{$cashbox->id}", $newData);
        $resp->assertOk();
        $cashbox->refresh();
        $this->assertEquals("Test Cashbox", $cashbox->name);
        $this->assertEquals("https://example.com/success", $cashbox->success_url);
        $this->assertEquals("https://example.com/fail", $cashbox->fail_url);
        $this->assertEquals("https://example.com/webhook", $cashbox->webhook_url);

        $this->assertLog("cashbox-updated",
            $user->id,
            cashbox_id: $cashbox->id,
            parameters: $newData
        );

        $user1 = User::factory()->create();
        $cashbox1 = Cashbox::factory()->state(["user_id" => $user1->id])->create();
        $resp = $this->actingAs($user)->put("/api/cashboxes/{$cashbox1->id}", $newData);
        $resp->assertForbidden();
    }

    public function testDeleteCashbox(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()
            ->state(["user_id" => $user->id])
            ->create();
        $resp = $this->actingAs($user)->delete("/api/cashboxes/{$cashbox->id}");
        $resp->assertOk();

        $this->assertSoftDeleted("cashboxes", ["id" => $cashbox->id]);

        $this->assertLog("cashbox-deleted", $user->id, cashbox_id: $cashbox->id);

        $user1 = User::factory()->create();
        $cashbox1 = Cashbox::factory()
            ->state(["user_id" => $user1->id])
            ->create();
        $resp = $this->actingAs($user)->delete("/api/cashboxes/{$cashbox1->id}");
        $resp->assertForbidden();

    }
}
