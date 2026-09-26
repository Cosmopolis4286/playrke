<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Country;
use App\Models\Currency;
use App\Models\TimeZone;
use App\Repositories\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
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
            // Cada rol tiene una contraseña robusta y ÚNICA (16+ chars,
            // mayúsculas/minúsculas/dígitos/símbolos). Antes las 10 cuentas
            // compartían literalmente 'password123' — cualquiera que
            // conociera un email demo tenía acceso a TODOS los roles,
            // incluido superadmin. Fijas (no aleatorias) a propósito: deben
            // ser reproducibles entre entornos y compartibles con QA sin
            // reconsultar la BD en cada refresh.
            $demoUsers = [
                // SuperAdmin
                [
                    'email'       => 'superadmin@playrke.com',
                    'username'    => 'superadmin',
                    'first_name'  => 'Super',
                    'last_name'   => 'Admin',
                    'password'    => 'Sp4dm!n#Rk3-Vzla9Q',
                    'role'        => 'superadmin',
                    'description' => 'Super Administrador del sistema',
                    // users.phone_number (cuenta personal) — formato validado
                    // por PhonePatterns para VE, distinto del 'phone' de
                    // negocio que viven en commerces/sellers/etc.
                    'phone_number' => '+58 0412-1000001',
                ],
                // Admin
                [
                    'email'       => 'admin@playrke.com',
                    'username'    => 'admin',
                    'first_name'  => 'Play',
                    'last_name'   => 'RKE',
                    'password'    => 'Adm1n!Rke#Panel7Wz',
                    'role'        => 'admin',
                    'description' => 'Administrador del sistema',
                    'phone_number' => '+58 0412-1000002',
                ],
                // Commerce
                [
                    'email'       => 'commerce@playrke.com',
                    'username'    => 'commerce_demo',
                    'first_name'  => 'Comercio',
                    'last_name'   => 'Demo',
                    'password'    => 'C0merc10!Demo#4Lt9',
                    'role'        => 'commerce',
                    'description' => 'Comercio de prueba',
                    'phone_number' => '+58 0412-1000003',
                ],
                // Commerce #2 — segundo comercio de prueba, independiente del
                // primero (útil para probar escenarios con más de un árbol
                // Comercio→Sucursal→Vendedor, ej. filtros/reasignación en
                // /admin/sellers).
                [
                    'email'       => 'commerce2@playrke.com',
                    'username'    => 'commerce_demo_2',
                    'first_name'  => 'Comercio',
                    'last_name'   => 'Demo Dos',
                    'password'    => 'C0merc10!Dos#8Xn2',
                    'role'        => 'commerce',
                    'description' => 'Segundo comercio de prueba',
                    'phone_number' => '+58 0412-1000004',
                ],
                // Subsidiary — agencia de Comercio Demo (jerarquía real
                // Comercio → Sucursal → Vendedor, ver DemoRoleDataSeeder).
                [
                    'email'       => 'subsidiary@playrke.com',
                    'username'    => 'subsidiary_demo',
                    'first_name'  => 'Sucursal',
                    'last_name'   => 'Demo',
                    'password'    => 'Sucurs4l!Demo#3Ky7',
                    'role'        => 'subsidiary',
                    'description' => 'Agencia/sucursal de prueba',
                    'phone_number' => '+58 0412-1000005',
                ],
                // Seller (gestionado por la Sucursal Demo, que a su vez
                // pertenece a Comercio Demo — cadena completa de jerarquía)
                [
                    'email'       => 'seller@playrke.com',
                    'username'    => 'seller_demo',
                    'first_name'  => 'Vendedor',
                    'last_name'   => 'Demo',
                    'password'    => 'V3ndedor!Demo#6Fp1',
                    'role'        => 'seller',
                    'description' => 'Vendedor asociado a la sucursal demo',
                    'phone_number' => '+58 0412-1000006',
                ],
                // Customer (API guard)
                [
                    'email'       => 'customer@playrke.com',
                    'username'    => 'customer_demo',
                    'first_name'  => 'Cliente',
                    'last_name'   => 'Demo',
                    'password'    => 'Cl13nte!Demo#2Rb5',
                    'role'        => 'customer',
                    'description' => 'Cliente de prueba',
                    'phone_number' => '+58 0412-1000007',
                ],
                // Players
                [
                    'email'       => 'player1@test.com',
                    'username'    => 'player1',
                    'first_name'  => 'Juan',
                    'last_name'   => 'Pérez',
                    'password'    => 'Pl4y3r1!Juan#9Tm4',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 1',
                    'phone_number' => '+58 0414-1000008',
                ],
                [
                    'email'       => 'player2@test.com',
                    'username'    => 'player2',
                    'first_name'  => 'Ana',
                    'last_name'   => 'Gómez',
                    'password'    => 'Pl4y3r2!Ana#5Wq8',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 2',
                    'phone_number' => '+58 0414-1000009',
                ],
                [
                    'email'       => 'player3@test.com',
                    'username'    => 'player3',
                    'first_name'  => 'Luis',
                    'last_name'   => 'Martínez',
                    'password'    => 'Pl4y3r3!Luis#1Zc6',
                    'role'        => 'player',
                    'description' => 'Jugador de prueba 3',
                    'phone_number' => '+58 0414-1000010',
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
                        'phone_number'       => $data['phone_number'],
                    ]
                );

                // Asignar rol según corresponda.
                // guard_name siempre 'web': RoleSeeder crea TODOS los roles con
                // guard 'web' (incluido 'customer', cambiado explícitamente desde
                // 'api' para que tenga acceso al menú — ver RoleSeeder::run()).
                // La excepción especial que quedaba aquí buscaba 'customer' con
                // guard 'api', que ya no existe, así que el rol nunca se asignaba
                // al usuario demo de ese rol (badge de rol vacío en /admin/users).
                try {
                    $role = Role::where('name', $data['role'])
                        ->where('guard_name', 'web')
                        ->first();

                    if ($role && !$user->hasRole($data['role'])) {
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
            $this->command->error("❌ Error en TestUserSeeder: {$e->getMessage()}");
            throw $e;
        }
    }
}
