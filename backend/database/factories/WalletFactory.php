<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Wallet\Models\Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'balance' => fake()->randomFloat(2, 0, 1000),
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ];
    }
}
