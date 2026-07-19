<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'iso_code' => strtoupper(fake()->unique()->lexify('???')),
            'symbol' => fake()->randomElement(['$', '€', '₿']),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => 'FIAT',
            'country_id' => null,
            'decimal_places' => 2,
            'status' => 'ACTIVE',
        ];
    }
}
