<?php

namespace Database\Seeders;

use App\Models\Users\User;
use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Repositories\Users\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // ================================
            // País: Venezuela
            // ================================
            $venezuela = Country::where('iso_code_2', 'VE')->first();

            if (!$venezuela) {
                $this->command->warn('País Venezuela no encontrado. Ejecute CountriesSeeder primero.');
                return;
            }

            // ================================
            // Crear o recuperar usuario admin
            // ================================
            $userRepo = app(UserRepository::class);

            $admin = User::firstOrCreate(
                ['email' => 'admin@playrke.com'],
                [
                    'username' => 'admin',
                    'first_name' => 'Play',
                    'last_name' => 'RKE',
                    'country_id' => $venezuela->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make('EHC-4286-RKE'),
                    'remember_token' => Str::random(10),
                    'referral_code' => $userRepo->generateUniqueReferralCode(),
                    'status' => 'ACTIVE',
                ]
            );

            // ================================
            // Rol de administrador
            // ================================
            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
                $this->command->info('Rol admin asignado correctamente.');
            }

            // ================================
            // Monedero VES
            // ================================
            $currency = Currency::where('iso_code', 'VES')->first();

            if (!$currency) {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            } else {
                $walletExists = $admin->wallets()->where('currency_id', $currency->id)->exists();

                if (!$walletExists) {
                    $admin->wallets()->create([
                        'currency_id' => $currency->id,
                        'balance' => 0,
                        'status' => 'ACTIVE',
                    ]);

                    $this->command->info('Monedero VES creado para el usuario admin.');
                }
            }

            $this->command->info("✅ Usuario Admin asegurado exitosamente. ID: {$admin->id}");
        } catch (\Throwable $e) {
            $this->command->error("❌ Error en UserSeeder: {$e->getMessage()}");
            throw $e;
        }
    }
}
