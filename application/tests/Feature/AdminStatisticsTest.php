<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $roleAdmin = Role::where('name', 'admin')->first();
        $this->adminUser = User::factory()->for($roleAdmin)->create();
    }

    public function testIncomeByCashboxWithEmptyPeriods(): void
    {
        $merchant = User::factory()->create();
        $cashbox = Cashbox::factory()->create([
            'user_id' => $merchant->id,
            'name' => 'Shop A',
        ]);
        $otherCashbox = Cashbox::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Silent Shop',
        ]);

        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'a1',
            'amount' => 10000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 10:00:00',
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashbox->id,
            'order_id' => 'a2',
            'amount' => 5000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-03 10:00:00',
            'updated_at' => '2026-09-03 10:00:00',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(
            '/api/admin/statistics?period=day&from=2026-09-01&to=2026-09-03&group_by=cashbox'
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                [
                    'entity_id' => $cashbox->id,
                    'entity_label' => 'Shop A',
                    'periods' => [
                        ['period' => '2026-09-01', 'income' => 1000],
                        ['period' => '2026-09-02', 'income' => 0],
                        ['period' => '2026-09-03', 'income' => 500],
                    ],
                ],
            ],
        ]);
        $this->assertDatabaseHas('cashboxes', ['id' => $otherCashbox->id]);
    }

    public function testIncomeByUser(): void
    {
        $merchant = User::factory()->create(['email' => 'merchant@example.com']);
        $cashboxOne = Cashbox::factory()->create(['user_id' => $merchant->id]);
        $cashboxTwo = Cashbox::factory()->create(['user_id' => $merchant->id]);

        Payment::factory()->create([
            'cashbox_id' => $cashboxOne->id,
            'order_id' => 'u1',
            'amount' => 20000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 10:00:00',
            'updated_at' => '2026-09-01 10:00:00',
        ]);
        Payment::factory()->create([
            'cashbox_id' => $cashboxTwo->id,
            'order_id' => 'u2',
            'amount' => 10000,
            'status' => Payment::STATUS_PAID,
            'created_at' => '2026-09-01 11:00:00',
            'updated_at' => '2026-09-01 11:00:00',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(
            '/api/admin/statistics?period=day&from=2026-09-01&to=2026-09-01&group_by=user'
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'data' => [
                [
                    'entity_id' => $merchant->id,
                    'entity_label' => 'merchant@example.com',
                    'periods' => [
                        ['period' => '2026-09-01', 'income' => 3000],
                    ],
                ],
            ],
        ]);
    }

    public function testRegularUserForbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(
            '/api/admin/statistics?period=day&from=2026-09-01&to=2026-09-01&group_by=cashbox'
        );

        $response->assertForbidden();
    }

    public function testValidationErrors(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/api/admin/statistics');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['period', 'from', 'to', 'group_by']);
    }
}
