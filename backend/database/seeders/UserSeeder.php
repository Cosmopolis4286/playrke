<?php

namespace Database\Seeders;

use App\Models\Users\User;
use App\Repositories\Users\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de usuario admin
     */
    public function run(): void
    {
        try {
            // ========================
            // Obtener país Venezuela
            // ========================
            $venezuela = \App\Models\Countries\Country::where('iso_code_2', 'VE')->first();
            if (!$venezuela) {
                $this->command->warn('País Venezuela no encontrado. Ejecute primero CountriesSeeder.');
                return;
            }

            // ========================
            // Crear o recuperar usuario Admin
            // ========================
            $admin = User::firstOrCreate(
                ['email' => 'admin@playrke.com'],
                [
                    'username' => 'admin',
                    'first_name' => 'Play',
                    'last_name' => 'RKE',
                    'country_id' => $venezuela->id,
                    'time_zone_id' => null,
                    'profile_photo_url' => null,
                    'phone_number' => null,
                    'email_verified_at' => now(),
                    'password' => Hash::make('EHC-4286-RKE'),
                    'remember_token' => Str::random(10),
                    'referral_code' => app(UserRepository::class)->generateUniqueReferralCode(),
                    'referred_by_user_id' => null,
                    'status' => 'ACTIVE',
                ]
            );

            // ========================
            // Asignar rol Admin si no lo tiene
            // ========================
            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
                $this->command->info('Rol admin asignado correctamente');
            }

            // ========================
            // Crear monedero VES si no existe
            // ========================
            $currencyVES = \App\Models\Currencies\Currency::where('iso_code', 'VES')->first();
            if (!$currencyVES) {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            } elseif (!$admin->wallets()->where('currency_id', $currencyVES->id)->exists()) {
                $admin->wallets()->create([
                    'currency_id' => $currencyVES->id,
                    'balance' => 0.00,
                    'status' => 'ACTIVE',
                ]);
                $this->command->info('Monedero VES creado para el usuario admin');
            }

            $this->command->info("✅ Usuario Admin creado exitosamente con ID: {$admin->id}");
        } catch (\Exception $e) {
            $this->command->error("❌ Error en UserSeeder: " . $e->getMessage());
            throw $e;
        }
    }
}
