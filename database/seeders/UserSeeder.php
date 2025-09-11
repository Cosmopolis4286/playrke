<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallets\Wallet;
use App\Repositories\User\UserRepository;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // Instancia Snowflake
            $snowflake = new Snowflake();

            // Obtener país Venezuela de forma dinámica
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

            // Crear el usuario Admin con ID Snowflake
            $adminData = [
                'id' => $snowflake->id(),
                'username' => 'admin',
                'first_name' => 'Play',
                'last_name' => 'RKE',
                'country_id' => $venezuelaCountry->id,
                'time_zone_id' => null,
                'photo' => null,
                'phone_number' => null,
                'email' => 'admin@playrke.com',
                'email_verified_at' => now(),
                'password' => Hash::make('EHC-4286-RKE'),
                'remember_token' => Str::random(10),
                'referral_code' => UserRepository::generateUniqueReferralCode(),
                'referred_by' => null,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Insertar usando insert masivo
            User::insert([$adminData]);

            // Recuperar el usuario recién creado
            $user = User::find($adminData['id']);

            // Asignar rol Admin usando Spatie con verificación
            try {
                $user->assignRole('admin');
                $this->command->info('Rol admin asignado correctamente');
            } catch (\Exception $e) {
                $this->command->warn('No se pudo asignar el rol admin: ' . $e->getMessage());
            }

            // Crear monedero asociado con validación
            $currency = \App\Models\Currencies\Currency::where('iso_code', 'VES')->first();

            if ($currency) {
                Wallet::insert([[
                    'id' => $snowflake->id(),
                    'currency_id' => $currency->id,
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]]);

                $this->command->info('Monedero VES creado para el usuario admin');
            } else {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            }

            $this->command->info("Usuario Admin creado exitosamente con ID: {$user->id}");
        } catch (\Exception $e) {
            $this->command->error("Error en UsersSeeder: " . $e->getMessage());
            throw $e;
        }
    }
}
