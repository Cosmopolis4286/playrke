<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Admin;
use App\Repositories\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            // Usuarios demo por rol
            // ================================
            $demoUsers = [
                // Admin
                [
                    'email'       => 'admin@playrke.com',
                    'username'    => 'admin',
                    'first_name'  => 'Play',
                    'last_name'   => 'RKE',
                    'password'    => 'password123',
                    'role'        => 'admin',
                    'description' => 'Administrador del sistema',
                ],
                // Commerce
                [
                    'email'       => 'commerce@playrke.com',
                    'username'    => 'commerce_demo',
                    'first_name'  => 'Comercio',
                    'last_name'   => 'Demo',
                    'password'    => 'password123',
                    'role'        => 'commerce',
                    'description' => 'Comercio de prueba',
                ],
                // Seller (gestionado por el commerce)
                [
                    'email'       => 'seller@playrke.com',
                    'username'    => 'seller_demo',
                    'first_name'  => 'Vendedor',
                    'last_name'   => 'Demo',
                    'password'    => 'password123',
                    'role'        => 'seller',
                    'description' => 'Vendedor asociado al comercio demo',
                ],
                // Customer (API guard)
                [
                    'email'       => 'customer@playrke.com',
                    'username'    => 'customer_demo',
                    'first_name'  => 'Cliente',
                    'last_name'   => 'Demo',
                    'password'    => 'password123',
                    'role'        => 'customer',
                    'description' => 'Cliente de prueba',
                ],
                // Players
                [
                    'email'       => 'player1@test.com',
                    'username'    => 'player1',
                    'first_name'  => 'Juan',
                    'last_name'   => 'Pérez',
                    'password'    => 'password123',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 1',
                ],
                [
                    'email'       => 'player2@test.com',
                    'username'    => 'player2',
                    'first_name'  => 'Ana',
                    'last_name'   => 'Gómez',
                    'password'    => 'password123',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 2',
                ],
                [
                    'email'       => 'player3@test.com',
                    'username'    => 'player3',
                    'first_name'  => 'Luis',
                    'last_name'   => 'Martínez',
                    'password'    => 'password123',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 3',
                ],
            ];

            foreach ($demoUsers as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'username'           => $data['username'],
                        'first_name'         => $data['first_name'],
                        'last_name'          => $data['last_name'],
                        'country_id'         => $venezuela->id,
                        'email_verified_at'  => now(),
                        'password'           => Hash::make($data['password']),
                        'remember_token'     => Str::random(10),
                        'referral_code'      => $userRepo->generateUniqueReferralCode(),
                        'status'             => 'ACTIVE',
                        'is_demo_user'       => true,
                        'demo_password'      => $data['password'],
                    ]
                );

                if (!$user->hasRole($data['role'])) {
                    $user->assignRole($data['role']);
                }

                // Si el rol es admin, crear registro en tabla admins
                if ($data['role'] === 'admin') {
                    $this->createAdminRecord($user, $data, $venezuela);
                }

                $this->command->info("✔ {$data['role']}: {$user->email}");
            }

            // ================================
            // Monedero VES para todos los demos
            // ================================
            $currency = Currency::where('iso_code', 'VES')->first();

            if (!$currency) {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            } else {
                $demoEmails = array_column($demoUsers, 'email');
                $users = User::whereIn('email', $demoEmails)->get();

                foreach ($users as $user) {
                    if (!$user->wallets()->where('currency_id', $currency->id)->exists()) {
                        $user->wallets()->create([
                            'currency_id' => $currency->id,
                            'balance'     => 0,
                            'status'      => 'ACTIVE',
                        ]);
                        $this->command->info("  → Monedero VES creado para {$user->email}.");
                    }
                }
            }

            $this->command->info('✅ Usuarios demo asegurados exitosamente.');
        } catch (\Throwable $e) {
            $this->command->error("❌ Error en UserSeeder: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Crear registro en tabla admins para usuarios con rol admin
     */
    private function createAdminRecord(User $user, array $data, Country $country): void
    {
        // Verificar si ya existe el registro admin
        if (Admin::where('user_id', $user->id)->exists()) {
            return;
        }

        // Obtener moneda por defecto (VES)
        $currency = Currency::where('iso_code', 'VES')->first();
        
        // Generar código único
        $uniqueCode = $this->generateUniqueCode();

        // Crear registro en unique_codes si no existe
        $this->createUniqueCode($uniqueCode);

        // Permisos según el tipo de admin
        $permissions = $this->getAdminPermissions($data['email']);

        Admin::create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'currency_id' => $currency?->id,
            'unique_code_id' => $this->getUniqueCodeId($uniqueCode),
            'status' => 'ACTIVE',
            'permissions' => json_encode($permissions),
            'last_login_at' => null,
        ]);

        $this->command->info("  → Registro admin creado para {$user->email}");
    }

    /**
     * Generar código único para admin
     */
    private function generateUniqueCode(): string
    {
        return 'ADM-' . strtoupper(uniqid());
    }

    /**
     * Crear registro en tabla unique_codes
     */
    private function createUniqueCode(string $code): void
    {
        DB::table('unique_codes')->insertOrIgnore([
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener ID del código único
     */
    private function getUniqueCodeId(string $code): ?int
    {
        $record = DB::table('unique_codes')->where('code', $code)->first();
        return $record?->id;
    }

    /**
     * Obtener permisos según el email del admin
     */
    private function getAdminPermissions(string $email): array
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
            default => [
                'manage_exchange_rates',
                'view_reports',
            ],
        };
    }
}
