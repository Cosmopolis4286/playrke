<?php

namespace Database\Factories\Roles;

use App\Models\Roles\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roles\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Will be set in seeder
            'country_id' => 1, // Default country
            'currency_id' => 1, // Default currency
            'unique_code_id' => null, // Will be set in seeder
            'status' => 'ACTIVE',
            'permissions' => json_encode([
                'manage_exchange_rates',
                'manage_users',
                'view_reports',
                'system_settings'
            ]),
            'last_login_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'INACTIVE',
        ]);
    }

    /**
     * Indicate that the admin is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'BLOCKED',
        ]);
    }

    /**
     * Admin with limited permissions.
     */
    public function withLimitedPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => json_encode([
                'manage_exchange_rates',
                'view_reports'
            ]),
        ]);
    }

    /**
     * Admin with full permissions.
     */
    public function withFullPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => json_encode([
                'manage_exchange_rates',
                'manage_users',
                'manage_commerces',
                'manage_sellers',
                'manage_customers',
                'view_reports',
                'system_settings',
                'audit_logs'
            ]),
        ]);
    }
}
