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

class GetPaymentSumTest extends TestCase
{
    use RefreshDatabase;
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
        $this->users = User::factory()->count(7)->create();
        $this->cashboxes = Cashbox::factory()->state(
            new Sequence(
                ...$this->users->map(fn ($user) => ["user_id" => $user->id])
            )
        )->count(7)->create();
        $this->now = CarbonImmutable::now();
        $this->payments = Payment::factory()->state(
            new Sequence(
                ...$this->cashboxes->map(
                fn ($cashbox, int $index) => [
                    "cashbox_id" => $cashbox->id,
                    "status" => Payment::STATUS_PAID,
                    "created_at" => $this->now->subMinutes($index),
                ]
            )
            )
        )->count(7)->create();
    }

    public function testGetSum(): void
    {
        $expectedSum = $this->payments->sum("amount");
        $response = $this->get('/api/payments-sum');

        $response->assertStatus(200);
        $response->assertJson([
            "success" => true,
            "data" => [
                "sum" => $expectedSum,
            ]
        ]);
    }

    public function testUpdatePaymentSumCache(): void
    {
        $this->travelTo($this->payments[5]->created_at);
        $expectedSum = $this->payments[5]->amount + $this->payments[6]->amount;
        $this->artisan("update:payment-sum-cache");

        $cachingDate = \Cache::get("paymentSumCacheDate");
        $cachedSum = \Cache::get("paymentSumCachedSum", 0);

        $this->assertEquals($this->payments[5]->created_at, $cachingDate);
        $this->assertEquals($expectedSum, $cachedSum);
    }
}
