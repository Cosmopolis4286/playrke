<?php

namespace Database\Seeders;

use App\Models\Users\User;
use App\Models\Wallets\Wallet;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // País Venezuela
            $venezuelaCountry = \App\Models\Countries\Country::where('iso_code_2', 'VE')->first();

            if (!$venezuelaCountry) {
                $this->command->warn('País Venezuela no encontrado. Ejecute CountriesSeeder primero.');
                return;
            }

            // Verificar si el usuario admin ya existe
            $existingAdmin = User::where('email', 'admin@playrke.com')->first();

            if ($existingAdmin) {
                $this->command->info('Usuario admin ya existe con ID: ' . $existingAdmin->id);
                return;
            }

            // Crear usuario Admin
            $admin = User::create([
                'username' => 'admin',
                'first_name' => 'Play',
                'last_name' => 'RKE',
                'country_id' => $venezuelaCountry->id,
                'time_zone_id' => null,
                'profile_photo_url' => null,
                'phone_number' => null,
                'email' => 'admin@playrke.com',
                'email_verified_at' => now(),
                'password' => Hash::make('EHC-4286-RKE'),
                'remember_token' => Str::random(10),
                'referral_code' => UserRepository::generateUniqueReferralCode(),
                'referred_by_user_id' => null,
                'status' => 'ACTIVE',
            ]);

            // Asignar rol Admin
            try {
                $admin->assignRole('admin');
                $this->command->info('Rol admin asignado correctamente');
            } catch (\Exception $e) {
                $this->command->warn('No se pudo asignar el rol admin: ' . $e->getMessage());
            }

            // Crear monedero VES
            $currency = \App\Models\Currencies\Currency::where('iso_code', 'VES')->first();

            if ($currency) {
                Wallet::create([
                    'currency_id' => $currency->id,
                    'user_id' => $admin->id,
                    'balance' => 0.00,
                    'is_active' => true,
                ]);

                $this->command->info('Monedero VES creado para el usuario admin');
            } else {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            }

            $this->command->info("✅ Usuario Admin creado exitosamente con ID: {$admin->id}");
        } catch (\Exception $e) {
            $this->command->error("❌ Error en UserSeeder: " . $e->getMessage());
            throw $e;
        }
    }
}
