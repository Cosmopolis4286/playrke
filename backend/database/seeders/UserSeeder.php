<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use App\Models\Currency;
use App\Models\TimeZone;
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

            $caracasTz = TimeZone::where('name', 'America/Caracas')->first();

            if (!$caracasTz) {
                $this->command->warn('⚠ TimeZone America/Caracas no encontrada. Ejecute TimeZoneSeeder primero.');
            }

            $userRepo = app(UserRepository::class);

            // ================================
            // Usuarios demo por rol
            // ================================
            $demoUsers = [
                // SuperAdmin
                [
                    'email'       => 'superadmin@playrke.com',
                    'username'    => 'superadmin',
                    'first_name'  => 'Super',
                    'last_name'   => 'Admin',
                    'password'    => 'password123',
                    'role'        => 'superadmin',
                    'description' => 'Super Administrador del sistema',
                ],
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
                        'time_zone_id'       => $caracasTz?->id,
                        'email_verified_at'  => now(),
                        'password'           => Hash::make($data['password']),
                        'remember_token'     => Str::random(10),
                        'referral_code'      => $userRepo->generateUniqueReferralCode(),
                        'status'             => 'ACTIVE',
                        'is_demo_user'       => true,
                        'demo_password'      => $data['password'],
                    ]
                );

                // Asignar rol según corresponda
                try {
                    $guardName = $data['role'] === 'customer' ? 'api' : 'web';

                    // Buscar el rol por nombre y guard
                    $role = \Spatie\Permission\Models\Role::where('name', $data['role'])
                        ->where('guard_name', $guardName)
                        ->first();

                    if ($role && !$user->hasRole($data['role'], $guardName)) {
                        $user->assignRole($role);
                        $this->command->info("  ✅ Rol {$data['role']} asignado");
                    }
                } catch (\Exception $e) {
                    // Silenciar errores de rol para continuar con el proceso
                    $this->command->warn("⚠ Error rol {$data['role']}: " . substr($e->getMessage(), 0, 50) . "...");
                }

                $this->command->info("✔ {$data['role']}: {$user->email}");
            }

            // ================================
            // Billetera VES para usuarios no administrativos (simplificado)
            // ================================
            $currency = Currency::where('iso_code', 'VES')->first();

            if (!$currency) {
                $this->command->warn('Moneda VES no encontrada. Ejecute CurrenciesSeeder primero.');
            } else {
                $demoEmails = array_column($demoUsers, 'email');
                // Excluir admin y superadmin de la creación de billeteras
                $adminEmails = ['admin@playrke.com', 'superadmin@playrke.com'];
                $nonAdminEmails = array_diff($demoEmails, $adminEmails);
                
                $users = User::whereIn('email', $nonAdminEmails)->get();

                foreach ($users as $user) {
                    if (!$user->wallets()->where('currency_id', $currency->id)->exists()) {
                        $user->wallets()->create([
                            'currency_id' => $currency->id,
                            'balance'     => 0,
                            'status'      => 'ACTIVE',
                        ]);
                        $this->command->info("  → Billetera VES creada para {$user->email}.");
                    }
                }
            }

            // ================================
            // Asignar timezone a usuarios demo existentes sin timezone
            // ================================
            if ($caracasTz) {
                $demoEmails = array_column($demoUsers, 'email');
                $updated = User::whereIn('email', $demoEmails)
                    ->whereNull('time_zone_id')
                    ->update(['time_zone_id' => $caracasTz->id]);

                if ($updated > 0) {
                    $this->command->info("  → time_zone_id America/Caracas asignada a {$updated} usuario(s) existente(s).");
                }
            }

            $this->command->info('✅ Usuarios demo asegurados exitosamente.');
        } catch (\Throwable $e) {
            $this->command->error("❌ Error en UserSeeder: {$e->getMessage()}");
            throw $e;
        }
    }
}
