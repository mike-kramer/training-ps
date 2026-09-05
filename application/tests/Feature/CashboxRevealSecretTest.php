<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithAuditLogs;

class CashboxRevealSecretTest extends TestCase
{
    use RefreshDatabase, WithAuditLogs;

    public function testRevealSecretSuccess(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->state(['user_id' => $user->id])->create();

        $response = $this->actingAs($user)->postJson(
            "/api/cashboxes/{$cashbox->id}/reveal-secret",
            ['password' => 'password'],
        );

        $response->assertOk();
        $response->assertJson([
            'secret_key' => $cashbox->secret_key,
        ]);
        $this->assertLog('cashbox-secret-revealed', $user->id, cashbox_id: $cashbox->id);
    }

    public function testRevealSecretWrongPassword(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->state(['user_id' => $user->id])->create();

        $response = $this->actingAs($user)->postJson(
            "/api/cashboxes/{$cashbox->id}/reveal-secret",
            ['password' => 'wrong-password'],
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function testRevealSecretForbiddenForOtherUser(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $cashbox = Cashbox::factory()->state(['user_id' => $owner->id])->create();

        $response = $this->actingAs($other)->postJson(
            "/api/cashboxes/{$cashbox->id}/reveal-secret",
            ['password' => 'password'],
        );

        $response->assertForbidden();
    }

    public function testRevealSecretUnauthenticated(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->state(['user_id' => $user->id])->create();

        $this->postJson(
            "/api/cashboxes/{$cashbox->id}/reveal-secret",
            ['password' => 'password'],
        )->assertUnauthorized();
    }

    public function testSecretNotInCashboxList(): void
    {
        $user = User::factory()->create();
        Cashbox::factory()->state(['user_id' => $user->id])->create();

        $response = $this->actingAs($user)->getJson('/api/cashboxes');
        $response->assertOk();
        $response->assertJsonMissingPath('data.0.secret_key');
    }
}
