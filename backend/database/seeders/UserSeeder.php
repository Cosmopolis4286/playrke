<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use App\Models\Currency;
use App\Repositories\UserRepository;
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

            $userRepo = app(UserRepository::class);

            // ================================
            // Crear o recuperar usuario admin
            // ================================
            $admin = User::firstOrCreate(
                ['email' => 'admin@playrke.com'],
                [
                    'username' => 'admin',
                    'first_name' => 'Play',
                    'last_name' => 'RKE',
                    'country_id' => $venezuela->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'remember_token' => Str::random(10),
                    'referral_code' => $userRepo->generateUniqueReferralCode(),
                    'status' => 'ACTIVE',
                    'is_demo_user' => true,
                    'demo_password' => 'password123',
                ]
            );

            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
                $this->command->info('Rol admin asignado correctamente.');
            }

            // ================================
            // Crear usuarios demo player
            // ================================
            $demoPlayers = [
                [
                    'email' => 'player1@test.com',
                    'username' => 'player1',
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez',
                    'password' => 'password123',
                    'description' => 'Jugador de prueba 1',
                ],
                [
                    'email' => 'player2@test.com',
                    'username' => 'player2',
                    'first_name' => 'Ana',
                    'last_name' => 'Gómez',
                    'password' => 'password123',
                    'description' => 'Jugador de prueba 2',
                ],
                [
                    'email' => 'player3@test.com',
                    'username' => 'player3',
                    'first_name' => 'Luis',
                    'last_name' => 'Martínez',
                    'password' => 'password123',
                    'description' => 'Jugador de prueba 3',
                ],
            ];

            foreach ($demoPlayers as $playerData) {
                $player = User::firstOrCreate(
                    ['email' => $playerData['email']],
                    [
                        'username' => $playerData['username'],
                        'first_name' => $playerData['first_name'],
                        'last_name' => $playerData['last_name'],
                        'country_id' => $venezuela->id,
                        'email_verified_at' => now(),
                        'password' => Hash::make($playerData['password']),
                        'remember_token' => Str::random(10),
                        'referral_code' => $userRepo->generateUniqueReferralCode(),
                        'status' => 'ACTIVE',
                        'is_demo_user' => true,
                        'demo_password' => $playerData['password'],
                    ]
                );

                if (!$player->hasRole('player')) {
                    $player->assignRole('player');
                }

                $this->command->info("Usuario demo player creado o actualizado: {$player->email}");
            }

            // ================================
            // Monedero VES para admin y demos
            // ================================
            $currency = Currency::where('iso_code', 'VES')->first();

            if (!$currency) {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            } else {
                $usersToAssignWallet = collect([$admin])->concat(
                    User::whereIn('email', array_column($demoPlayers, 'email'))->get()
                );

                foreach ($usersToAssignWallet as $user) {
                    $walletExists = $user->wallets()->where('currency_id', $currency->id)->exists();

                    if (!$walletExists) {
                        $user->wallets()->create([
                            'currency_id' => $currency->id,
                            'balance' => 0,
                            'status' => 'ACTIVE',
                        ]);
                        $this->command->info("Monedero VES creado para el usuario {$user->email}.");
                    }
                }
            }

            $this->command->info("✅ Usuarios admin y demo player asegurados exitosamente.");
        } catch (\Throwable $e) {
            $this->command->error("❌ Error en UserSeeder: {$e->getMessage()}");
            throw $e;
        }
    }
}
