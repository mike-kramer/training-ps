<?php

namespace Database\Factories;

use App\Models\Cashbox;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CashboxFactory extends Factory
{
    protected $model = Cashbox::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomNumber(),
            'name' => $this->faker->name(),
            'success_url' => $this->faker->url(),
            'fail_url' => $this->faker->url(),
            'webhook_url' => $this->faker->url(),
            'secret_key' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
