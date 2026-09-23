<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashboxStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function testOwnerSeesPaymentCountAverageAndEmptyDays(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'o1',
            'amount' => 10000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 10:00:00',
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'o2',
            'amount' => 20000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 12:00:00',
            'updated_at' => '2026-09-01 12:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'o3',
            'amount' => 30000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-03 09:00:00',
            'updated_at' => '2026-09-03 09:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=day&from=2026-09-01&to=2026-09-03"
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                ['period' => '2026-09-01', 'payments_count' => 2, 'average_check' => 15000],
                ['period' => '2026-09-02', 'payments_count' => 0, 'average_check' => 0],
                ['period' => '2026-09-03', 'payments_count' => 1, 'average_check' => 30000],
            ],
        ]);
    }

    public function testIgnoresNonPaidPayments(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'pending',
            'amount' => 99999,
            'status' => Payment::STATUS_PENDING,
            'created_at' => '2026-09-01 10:00:00',
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'failed',
            'amount' => 88888,
            'status' => Payment::STATUS_FAILED,
            'created_at' => '2026-09-01 11:00:00',
            'updated_at' => '2026-09-01 11:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'paid',
            'amount' => 1000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 12:00:00',
            'updated_at' => '2026-09-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=day&from=2026-09-01&to=2026-09-01"
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.0.payments_count', 1);
        $response->assertJsonPath('data.0.average_check', 1000);
    }

    public function testForeignCashboxForbidden(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=day&from=2026-09-01&to=2026-09-01"
        );

        $response->assertForbidden();
    }

    public function testGuestUnauthorized(): void
    {
        $cashbox = Cashbox::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=day&from=2026-09-01&to=2026-09-01"
        );

        $response->assertUnauthorized();
    }

    public function testValidationErrors(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/cashboxes/{$cashbox->id}/statistics");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['period', 'from', 'to']);
    }

    public function testMonthPeriodSmoke(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'jan',
            'amount' => 5000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-01-15 10:00:00',
            'updated_at' => '2026-01-15 10:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=month&from=2026-01-01&to=2026-03-01"
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                ['period' => '2026-01-01', 'payments_count' => 1, 'average_check' => 5000],
                ['period' => '2026-02-01', 'payments_count' => 0, 'average_check' => 0],
                ['period' => '2026-03-01', 'payments_count' => 0, 'average_check' => 0],
            ],
        ]);
    }

    public function testYearPeriodSmoke(): void
    {
        $user = User::factory()->create();
        $cashbox = Cashbox::factory()->create(['user_id' => $user->id]);

        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'y2025',
            'amount' => 7000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2025-06-01 10:00:00',
            'updated_at' => '2025-06-01 10:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/cashboxes/{$cashbox->id}/statistics?period=year&from=2024-01-01&to=2026-01-01"
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                ['period' => '2024-01-01', 'payments_count' => 0, 'average_check' => 0],
                ['period' => '2025-01-01', 'payments_count' => 1, 'average_check' => 7000],
                ['period' => '2026-01-01', 'payments_count' => 0, 'average_check' => 0],
            ],
        ]);
    }
}
