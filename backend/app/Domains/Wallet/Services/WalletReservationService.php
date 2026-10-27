<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta reservas de saldo (WalletHold) para partidas con duración, donde
 * el jugador puede retirarse antes de que el resultado final se conozca
 * (ej. una carga que el transportista abandona a mitad de ruta). No
 * reemplaza a WalletService::recordTriviaEntry()-style debit inmediato —
 * ese patrón sigue siendo correcto para juegos de resultado instantáneo
 * (Bingo, Trivia).
 *
 * Un hold nunca toca wallets.balance/bonus_balance mientras está HELD.
 * Solo al capturarse genera un WalletMovement real (gasto); al liberarse,
 * el saldo simplemente vuelve a estar disponible sin ningún movimiento.
 */
final class WalletReservationService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    /**
     * Convierte un hold HELD en un gasto real: genera un WalletMovement de
     * tipo $movementType y marca el hold como CAPTURED. El monto capturado
     * puede ser menor o igual al reservado (ej. penalización parcial de
     * retiro) — el remanente, si lo hay, queda simplemente liberado (nunca
     * se cobra más de lo reservado).
     *
     * lockForUpdate() sobre el propio hold (no solo sobre la wallet, que ya
     * bloquea recordHoldCapture()): sin esto, dos llamadas concurrentes con
     * el mismo $hold (doble clic, reintento de red) pueden ambas leer
     * isActive()===true antes de que cualquiera actualice status, y ambas
     * ejecutan recordHoldCapture() completo — doble débito real desde la
     * misma reserva, ya que ese método no valida idempotencia por holdId.
     */
    public function capture(
        WalletHold $hold,
        string $movementType,
        ?float $amount = null,
        ?string $description = null,
    ) : WalletMovement {
        $amount ??= (float) $hold->amount;

        if ($amount > (float) $hold->amount) {
            throw WalletDomainException::holdCaptureExceedsReservedAmount();
        }

        $movement = DB::transaction(function() use ($hold, $movementType, $amount, $description) {
            $lockedHold = WalletHold::where('id', $hold->id)->lockForUpdate()->firstOrFail();

            if (! $lockedHold->isActive()) {
                throw WalletDomainException::holdAlreadyResolved();
            }

            $wallet = Wallet::findOrFail($lockedHold->wallet_id);

            // El débito real (lock de wallet, split bono/balance,
            // WalletMovement, broadcast, notificación) lo hace
            // WalletService::recordHoldCapture() — mismo patrón que
            // cualquier otro gasto de juego (recordCardPurchase,
            // recordTriviaEntry). Este servicio solo gestiona el ciclo de
            // vida del hold en sí.
            $movement = $this->walletService->recordHoldCapture(
                wallet: $wallet,
                amount: $amount,
                movementType: $movementType,
                description: $description,
                holdId: (string) $lockedHold->id,
            );

            $lockedHold->update([
                'status' => WalletHold::STATUS_CAPTURED,
                'captured_movement_id' => (string) $movement->id,
                'resolved_at' => now(),
            ]);

            return $movement;
        });

        return $movement;
    }

    /**
     * Crea una reserva HELD sobre la wallet indicada. Lanza DomainException
     * si el saldo disponible (jugable menos holds activos) es insuficiente.
     */
    public function hold(
        Wallet $wallet,
        float $amount,
        ?string $gameSessionId = null,
        ?string $description = null,
    ) : WalletHold {
        return DB::transaction(function() use ($wallet, $amount, $gameSessionId, $description) {
            $locked = Wallet::lockForUpdate()->findOrFail($wallet->id);

            if (! $locked->hasSufficientAvailableBalance($amount)) {
                throw WalletDomainException::insufficientAvailableBalanceForHold();
            }

            return WalletHold::create([
                'wallet_id' => (string) $locked->id,
                'game_session_id' => $gameSessionId,
                'amount' => $amount,
                'status' => WalletHold::STATUS_HELD,
                'description' => $description,
            ]);
        });
    }

    /**
     * Libera un hold HELD sin generar ningún WalletMovement — el saldo
     * simplemente vuelve a estar disponible.
     *
     * lockForUpdate() por el mismo motivo que capture(): sin él, una
     * liberación y una captura concurrentes sobre el mismo hold podrían
     * ambas pasar isActive() y dejar el hold en un estado inconsistente
     * (o liberar dos veces).
     */
    public function release(WalletHold $hold) : WalletHold
    {
        return DB::transaction(function() use ($hold) {
            $lockedHold = WalletHold::where('id', $hold->id)->lockForUpdate()->firstOrFail();

            if (! $lockedHold->isActive()) {
                throw WalletDomainException::holdAlreadyResolved();
            }

            $lockedHold->update([
                'status' => WalletHold::STATUS_RELEASED,
                'resolved_at' => now(),
            ]);

            return $lockedHold->fresh();
        });
    }
}
