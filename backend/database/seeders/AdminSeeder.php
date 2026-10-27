<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        // Desactivar restricciones de claves foráneas temporalmente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Limpiar tabla admins
        Admin::query()->delete();

        // Reactivar restricciones
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Obtener datos base
        $country = Country::first() ?? Country::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();

        // Crear usuarios admin si no existen
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@playrke.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Exchange Rate Admin',
                'email' => 'exchange@playrke.com',
                'password' => Hash::make('exchange123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'System Admin',
                'email' => 'system@playrke.com',
                'password' => Hash::make('system123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($adminUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData,
            );

            // Asignar rol de admin al usuario usando el nombre del rol
            $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'web')->first();
            if ($adminRole) {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            } else {
                $this->command->warn("⚠ No se encontró el rol 'admin' para asignar al usuario {$user->email}");
            }

            // Crear registro admin correspondiente
            Admin::factory()->create([
                'user_id' => $user->id,
                'country_id' => $country->id,
                'currency_id' => $currency->id,
                'unique_code_id' => $this->generateUniqueCode(),
                'permissions' => $this->getPermissionsForUser($userData['email']),
            ]);
        }

        $this->command->info('Admin users created successfully:');
        $this->command->info('- admin@playrke.com (password: admin123)');
        $this->command->info('- exchange@playrke.com (password: exchange123)');
        $this->command->info('- system@playrke.com (password: system123)');
    }

    /**
     * Generar código único para admin
     */
    private function generateUniqueCode() : string
    {
        return 'ADM-' . strtoupper(uniqid());
    }

    /**
     * Obtener permisos según el tipo de admin
     */
    private function getPermissionsForUser(string $email) : array
    {
        return match (true) {
            str_contains($email, 'admin@playrke.com') => [
                'manage_exchange_rates',
                'manage_users',
                'manage_commerces',
                'manage_sellers',
                'manage_customers',
                'view_reports',
                'system_settings',
                'audit_logs',
                'manage_treasurers',
            ],
            str_contains($email, 'exchange@playrke.com') => [
                'manage_exchange_rates',
                'view_reports',
                'view_audit_logs',
            ],
            str_contains($email, 'system@playrke.com') => [
                'system_settings',
                'view_reports',
                'audit_logs',
                'manage_users',
            ],
            default => [
                'view_reports',
            ],
        };
    }
}
