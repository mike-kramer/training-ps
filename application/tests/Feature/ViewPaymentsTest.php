<?php

namespace Tests\Feature;

use App\Models\Cashbox;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ViewPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    /**
     * @var Collection<Payment>
     */
    private Collection $payments;
    /**
     * @var Collection<User>
     */
    private Collection $users;
    /**
     * @var Collection<Cashbox>
     */
    private Collection $cashboxes;

    private CarbonImmutable $now;

    public function setUp(): void
    {
        parent::setUp();
        $roleAdmin = Role::where("name", "admin")->first();
        $this->adminUser = User::factory()->for($roleAdmin)->create();
        $this->users = User::factory()->count(7)->create();
        $this->cashboxes = Cashbox::factory()->state(
            new Sequence(
                ...$this->users->map(fn ($user) => ["user_id" => $user->id])
            )
        )->count(7)->create();
        $statuses = [
            Payment::STATUS_PENDING,
            Payment::STATUS_PAID,
            Payment::STATUS_PAID,
            Payment::STATUS_PAID,
            Payment::STATUS_FAILED,
            Payment::STATUS_FAILED,
            Payment::STATUS_FAILED,
        ];
        $this->now = CarbonImmutable::now();
        $this->payments = Payment::factory()->state(
            new Sequence(
                ...$this->cashboxes->map(
                    fn ($cashbox, int $index) => [
                        "cashbox_id" => $cashbox->id,
                        "status" => $statuses[$index],
                        "created_at" => $this->now->subMinutes($index),
                    ]
                )
            )
        )->count(7)->create();
    }

    public function testGetPaymentsList(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/api/admin/payments');
        $response->assertStatus(200);

        $response->assertJson([
            "success" => true,
            "data" => $this->payments->map(fn (Payment $payment) => $this->mapPayment($payment))->toArray(),
        ]);
    }

    private function mapPayment(Payment $payment): array
    {
        return [
            "id" => $payment->id,
            "cashbox_id" => $payment->cashbox_id,
            "user_id" => $payment->cashbox->user_id,
            "cashbox_name" => $payment->cashbox->name,
            "user_email" => $payment->cashbox->user->email,
            "amount" => $payment->amount,
            "status" => $payment->status,
            "created_at" => $payment->created_at->toISOString(),
        ];
    }
}
