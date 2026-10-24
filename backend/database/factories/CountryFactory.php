<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        // Faker's unique() only tracks values generated in this process; it
        // does not know about country codes already seeded in the database.
        // Avoid intermittent unique-index violations when a generated code
        // (for example TC) already exists in countries.
        do {
            $isoCode2 = strtoupper(fake()->unique()->lexify('??'));
        } while (Country::where('iso_code_2', $isoCode2)->exists());

        do {
            $isoCode3 = strtoupper(fake()->unique()->lexify('???'));
        } while (Country::where('iso_code_3', $isoCode3)->exists());

        return [
            'name' => fake()->unique()->country(),
            'iso_code_2' => $isoCode2,
            'iso_code_3' => $isoCode3,
            'status' => 'ACTIVE',
        ];
    }
}
