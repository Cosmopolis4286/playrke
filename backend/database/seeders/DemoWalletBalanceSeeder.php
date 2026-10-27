<?php

namespace Database\Seeders;

use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Carga saldo de prueba a los 3 jugadores demo, para poder probar flujos
 * reales (wallet-movements, límites, retiros) sin tener que depositar a mano
 * cada vez que se resiembra la BD.
 *
 * Antes esto se hacía a mano vía tinker en cada sesión de desarrollo —
 * nunca vivió en un seeder real, así que se perdía en cada migrate:fresh
 * (mismo patrón que exchange_rates/wallet_limits antes de
 * ExchangeRateSeeder/WalletLimitSeeder). Ver auditoría de seeders faltantes.
 *
 * Por jugador:
 * - 1000 VES de saldo YA DISPONIBLE: modelado como un WalletMovement DEPOSIT
 *   COMPLETED (como si viniera de datos de prueba/backfill), incrementando
 *   el balance real vía el mismo WalletRepository que usa el flujo de
 *   aprobación de producción (WalletService::approveDeposit()) — así el
 *   balance y el historial de movimientos quedan consistentes entre sí, en
 *   vez de tocar wallets.balance directo sin dejar rastro.
 * - 1500 VES en un DEPOSIT PENDING: no toca balance (mismo invariante que
 *   WalletService::requestDeposit(), balance_before == balance_after
 *   mientras está pendiente), queda esperando que un admin/superadmin lo
 *   apruebe/rechace desde /wallet-movements con el flujo real.
 *
 * Ambos montos están muy por debajo de max_deposit del límite VES Nivel 0
 * (18.000, ver WalletLimitSeeder), así que no chocan con los límites
 * recién calibrados.
 *
 * Debe correr DESPUÉS de DemoRoleDataSeeder (necesita que la wallet VES del
 * jugador ya exista) — ver DatabaseSeeder::run().
 */
class DemoWalletBalanceSeeder extends Seeder
{
    private const AVAILABLE_BALANCE = 1000.00;
    private const PENDING_DEPOSIT = 1500.00;
    private const PLAYER_EMAILS = ['player1@test.com', 'player2@test.com', 'player3@test.com'];

    public function run() : void
    {
        $walletRepository = app(WalletRepositoryInterface::class);

        foreach (self::PLAYER_EMAILS as $email) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->command->warn("⚠ {$email} no encontrado — ejecuta TestUserSeeder primero. Omitido.");

                continue;
            }

            $wallet = $user->wallets()->first();

            if (! $wallet) {
                $this->command->warn("⚠ {$email} sin wallet — ejecuta DemoRoleDataSeeder/TestUserSeeder primero. Omitido.");

                continue;
            }

            // Idempotente: si ya tiene algún movimiento de saldo demo, no duplicar en cada re-seed.
            $alreadySeeded = WalletMovement::where('wallet_id', $wallet->id)
                ->where('description', 'Saldo demo inicial')
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            $balanceBefore = (float) $wallet->balance;

            WalletMovement::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => WalletMovement::TYPE_DEPOSIT,
                'status' => WalletMovement::STATUS_COMPLETED,
                'amount' => self::AVAILABLE_BALANCE,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + self::AVAILABLE_BALANCE,
                'description' => 'Saldo demo inicial',
                'processed_at' => now(),
            ]);

            $walletRepository->incrementBalance((string) $wallet->id, self::AVAILABLE_BALANCE);
            $wallet->refresh();

            WalletMovement::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => WalletMovement::TYPE_DEPOSIT,
                'status' => WalletMovement::STATUS_PENDING,
                'amount' => self::PENDING_DEPOSIT,
                'balance_before' => (float) $wallet->balance,
                'balance_after' => (float) $wallet->balance,
                'description' => 'Depósito pendiente de confirmación (demo, pendiente de aprobación por superadmin)',
            ]);

            $this->command->info("✔ {$email}: saldo +" . self::AVAILABLE_BALANCE . " (balance={$wallet->balance}), depósito pendiente de " . self::PENDING_DEPOSIT);
        }

        $this->command->info('✅ DemoWalletBalanceSeeder completado.');
    }
}
