<?php

namespace Database\Seeders;

use App\Contracts\Roles\Admin\AdminRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Pivots\Roles\Commerce\CommerceCurrency;
use App\Models\Pivots\Roles\Commerce\CommerceGame;
use App\Models\Pivots\Roles\Customer\CustomerCurrency;
use App\Models\Roles\Customer\CustomerGame;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use App\Models\Pivots\Roles\Seller\SellerGame;
use App\Models\Roles\Admin;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crea los registros de entidad para los usuarios demo
 * de roles admin, commerce, subsidiary, seller y customer.
 *
 * Jerarquía real de negocio (ver Seller::resolveCommerce()): Comercio →
 * Sucursal (opcional) → Vendedor. El vendedor demo se cuelga de la
 * Sucursal Demo, que a su vez pertenece a Comercio Demo, para que la ficha
 * de detalle y el organigrama muestren la cadena completa con un solo
 * conjunto de cuentas demo.
 *
 * Depende de: RoleSeeder, TestUserSeeder, CountrySeeder, CurrencySeeder, GameSeeder
 */
class DemoRoleDataSeeder extends Seeder
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AdminRepositoryInterface $adminRepository,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly WalletRepositoryInterface $walletRepository,
    ) {}

    public function run(): void
    {
        $venezuela  = Country::where('iso_code_2', 'VE')->first();
        $ves        = Currency::where('iso_code', 'VES')->first();
        $firstGame  = Game::first();

        if (! $venezuela || ! $ves) {
            $this->command->warn('⚠ Faltan Country VE o Currency VES. Ejecute los seeders previos.');
            return;
        }

        DB::transaction(function () use ($venezuela, $ves, $firstGame) {
            // ──────────────────────────────────────────────────────────
            // 0. SUPERADMIN
            // ──────────────────────────────────────────────────────────
            $superadminUser = User::where('email', 'superadmin@playrke.com')->first();

            if ($superadminUser && ! \App\Models\Roles\Admin::where('user_id', $superadminUser->id)->exists()) {
                $uniqueCode = $this->userService->createUniqueCode((string) $superadminUser->id);

                $superadmin = \App\Models\Roles\Admin::create([
                    'user_id' => $superadminUser->id,
                    'country_id' => $venezuela->id,
                    'currency_id' => $ves->id,
                    'unique_code_id' => $uniqueCode->id,
                    'status' => 'ACTIVE',
                    'permissions' => json_encode([
                        'superadmin.access',
                        'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage',
                        'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign',
                        'commerces.view', 'commerces.create', 'commerces.edit', 'commerces.delete', 'commerces.approve',
                        'sellers.view', 'sellers.create', 'sellers.edit', 'sellers.delete',
                        'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                        'treasury.view', 'treasury.manage', 'treasury.approve.transactions', 'treasury.reports',
                        'exchange.view', 'exchange.manage', 'exchange.update',
                        'games.view', 'games.create', 'games.edit', 'games.delete', 'games.manage',
                        'reports.view', 'reports.generate', 'reports.export', 'reports.financial', 'reports.gaming',
                        'system.settings', 'system.maintenance', 'system.logs', 'system.backup',
                        'audit.view', 'audit.export',
                    ]),
                    'last_login_at' => null,
                ]);

                $this->command->info("✔ SuperAdmin entity creada para {$superadminUser->email}");
            }

            // ──────────────────────────────────────────────────────────
            // 1. ADMIN
            // ──────────────────────────────────────────────────────────
            $adminUser = User::where('email', 'admin@playrke.com')->first();

            if ($adminUser && ! Admin::where('user_id', $adminUser->id)->exists()) {
                $uniqueCode = $this->userService->createUniqueCode((string) $adminUser->id);

                $admin = Admin::create([
                    'user_id' => $adminUser->id,
                    'country_id' => $venezuela->id,
                    'currency_id' => $ves->id,
                    'unique_code_id' => $uniqueCode->id,
                    'status' => 'ACTIVE',
                    'permissions' => json_encode([
                        'manage_exchange_rates',
                        'manage_users',
                        'manage_commerces',
                        'manage_sellers',
                        'manage_customers',
                        'view_reports',
                        'system_settings',
                        'audit_logs',
                        'manage_treasurers',
                    ]),
                    'last_login_at' => null,
                ]);

                $this->ensureWallet($adminUser->id, $ves->id);
                $this->command->info("✔ Admin entity creada para {$adminUser->email}");
            }

            // ──────────────────────────────────────────────────────────
            // 1. COMMERCE
            // ──────────────────────────────────────────────────────────
            $commerceUser = User::where('email', 'commerce@playrke.com')->first();

            if ($commerceUser && ! Commerce::where('user_id', $commerceUser->id)->exists()) {
                $uniqueCode = $this->userService->createUniqueCode((string) $commerceUser->id);

                $commerce = $this->commerceRepository->create([
                    'name'           => 'Comercio Demo',
                    'phone'          => '+58 412 0000001',
                    'commission'     => 5.00,
                    'country_id'     => $venezuela->id,
                    'user_id'        => $commerceUser->id,
                    'unique_code_id' => $uniqueCode->id,
                    'status'         => 'ACTIVE',
                ]);

                $this->syncCurrencyCommerce($commerce, $ves->id);
                if ($firstGame) $this->syncGameCommerce($commerce, $firstGame->id);

                $this->ensureWallet($commerceUser->id, $ves->id);
                $this->command->info("✔ Commerce entity creada para {$commerceUser->email}");
            }

            // ──────────────────────────────────────────────────────────
            // 1b. COMMERCE #2 (segundo comercio de prueba, independiente)
            // ──────────────────────────────────────────────────────────
            $commerce2User = User::where('email', 'commerce2@playrke.com')->first();

            if ($commerce2User && ! Commerce::where('user_id', $commerce2User->id)->exists()) {
                $uniqueCode2 = $this->userService->createUniqueCode((string) $commerce2User->id);

                $commerce2 = $this->commerceRepository->create([
                    'name'           => 'Comercio Demo Dos',
                    'phone'          => '+58 412 0000004',
                    'commission'     => 5.00,
                    'country_id'     => $venezuela->id,
                    'user_id'        => $commerce2User->id,
                    'unique_code_id' => $uniqueCode2->id,
                    'status'         => 'ACTIVE',
                ]);

                $this->syncCurrencyCommerce($commerce2, $ves->id);
                if ($firstGame) $this->syncGameCommerce($commerce2, $firstGame->id);

                $this->ensureWallet($commerce2User->id, $ves->id);
                $this->command->info("✔ Commerce entity (2) creada para {$commerce2User->email}");
            }

            // ──────────────────────────────────────────────────────────
            // 2. SUBSIDIARY (agencia de Comercio Demo — jerarquía real
            //    Comercio → Sucursal → Vendedor)
            // ──────────────────────────────────────────────────────────
            $subsidiaryUser = User::where('email', 'subsidiary@playrke.com')->first();
            $commerceUser   = $commerceUser ?? User::where('email', 'commerce@playrke.com')->first();
            $commerce       = $commerce ?? ($commerceUser ? Commerce::where('user_id', $commerceUser->id)->first() : null);

            if ($subsidiaryUser && $commerce && ! Subsidiary::where('user_id', $subsidiaryUser->id)->exists()) {
                $subsidiary = $this->subsidiaryRepository->create([
                    'name'         => 'Sucursal Demo',
                    // subsidiaries.phone es varchar(14) — sin espacios, a
                    // diferencia de commerces.phone (más ancha), donde el
                    // formato con espacios sí entra.
                    'phone'        => '+584120000005',
                    'commission'   => 4.00,
                    'commerce_id'  => $commerce->id,
                    'user_id'      => $subsidiaryUser->id,
                    'country_id'   => $venezuela->id,
                    'currency_id'  => $ves->id,
                    'status'       => 'ACTIVE',
                ]);

                $this->ensureWallet($subsidiaryUser->id, $ves->id);
                $this->command->info("✔ Subsidiary entity creada para {$subsidiaryUser->email}");
            }

            // ──────────────────────────────────────────────────────────
            // 3. SELLER (reporta a través de la Sucursal Demo, que a su vez
            //    pertenece a Comercio Demo — cadena completa de jerarquía;
            //    manager_user_id sigue siendo el Commerce raíz, nunca el
            //    user_id de la Subsidiary, ver Seller::resolveCommerce())
            // ──────────────────────────────────────────────────────────
            $sellerUser = User::where('email', 'seller@playrke.com')->first();
            $subsidiary = $subsidiary ?? ($subsidiaryUser ? Subsidiary::where('user_id', $subsidiaryUser->id)->first() : null);

            if ($sellerUser && $commerceUser && ! Seller::where('user_id', $sellerUser->id)->exists()) {
                $seller = $this->sellerRepository->create([
                    'name'            => 'Vendedor Demo',
                    'phone'           => '+58 412 0000002',
                    'commission'      => 3.00,
                    'country_id'      => $venezuela->id,
                    'user_id'         => $sellerUser->id,
                    'manager_user_id' => $commerceUser->id,
                    'subsidiary_id'   => $subsidiary?->id,
                    'status'          => 'ACTIVE',
                ]);

                $this->syncCurrencySeller($seller, $ves->id);
                if ($firstGame) $this->syncGameSeller($seller, $firstGame->id);

                $this->ensureWallet($sellerUser->id, $ves->id);
                $this->command->info("✔ Seller entity creada para {$sellerUser->email}");
            } elseif ($sellerUser && $subsidiary) {
                // Migración de datos demo preexistentes: el seller demo ya
                // existía de una corrida anterior a que se agregara la
                // Sucursal Demo (reportaba directo a Comercio Demo). Se
                // reasigna una sola vez para que la cadena de jerarquía
                // completa quede visible en las cuentas demo.
                $existingSeller = Seller::where('user_id', $sellerUser->id)->first();

                if ($existingSeller && $existingSeller->subsidiary_id !== $subsidiary->id) {
                    $existingSeller->update(['subsidiary_id' => $subsidiary->id]);
                    $this->command->info("✔ Seller demo reasignado a Sucursal Demo ({$sellerUser->email})");
                }
            }

            // ──────────────────────────────────────────────────────────
            // 3. CUSTOMER
            // ──────────────────────────────────────────────────────────
            $customerUser = User::where('email', 'customer@playrke.com')->first();

            if ($customerUser && ! Customer::where('user_id', $customerUser->id)->exists()) {
                $customer = $this->customerRepository->create([
                    'name'         => 'Cliente Demo',
                    'phone'        => '+58 412 0000003',
                    'commission'   => 2.00,
                    'country_id'   => $venezuela->id,
                    'currency_id'  => $ves->id,
                    'user_id'      => $customerUser->id,
                    'api_route'    => 'http://localhost/api/customer-demo',
                    'customer_key' => bin2hex(random_bytes(32)),
                    'status'       => 'ACTIVE',
                ]);

                $this->syncCurrencyCustomer($customer, $ves->id);
                if ($firstGame) $this->syncGameCustomer($customer, $firstGame->id);

                $this->ensureWallet($customerUser->id, $ves->id);
                $this->command->info("✔ Customer entity creada para {$customerUser->email}");
            }
        });

        $this->command->info('✅ DemoRoleDataSeeder completado.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────────

    private function ensureWallet(string $userId, string $currencyId): void
    {
        $exists = \App\Domains\Wallet\Models\Wallet::where('user_id', $userId)
            ->where('currency_id', $currencyId)
            ->exists();

        if (! $exists) {
            $this->walletRepository->create([
                'user_id'     => $userId,
                'currency_id' => $currencyId,
            ]);
        }
    }

    private function syncCurrencyCommerce(Commerce $commerce, string $currencyId): void
    {
        CommerceCurrency::updateOrCreate(
            ['commerce_id' => $commerce->id, 'level' => 1],
            ['currency_id' => $currencyId, 'status' => 'ACTIVE']
        );
    }

    private function syncGameCommerce(Commerce $commerce, string $gameId): void
    {
        CommerceGame::updateOrCreate(
            ['commerce_id' => $commerce->id, 'game_id' => $gameId],
            ['status' => 'ACTIVE']
        );
    }

    private function syncCurrencySeller(Seller $seller, string $currencyId): void
    {
        SellerCurrency::updateOrCreate(
            ['seller_id' => $seller->id, 'level' => 1],
            ['currency_id' => $currencyId, 'status' => 'ACTIVE']
        );
    }

    private function syncGameSeller(Seller $seller, string $gameId): void
    {
        SellerGame::updateOrCreate(
            ['seller_id' => $seller->id, 'game_id' => $gameId],
            ['status' => 'ACTIVE']
        );
    }

    private function syncCurrencyCustomer(Customer $customer, string $currencyId): void
    {
        CustomerCurrency::updateOrCreate(
            ['customer_id' => $customer->id, 'currency_id' => $currencyId],
            ['level' => 1, 'status' => 'ACTIVE']
        );
    }

    private function syncGameCustomer(Customer $customer, string $gameId): void
    {
        CustomerGame::updateOrCreate(
            ['customer_id' => $customer->id, 'game_id' => $gameId],
            ['status' => 'ACTIVE']
        );
    }
}
