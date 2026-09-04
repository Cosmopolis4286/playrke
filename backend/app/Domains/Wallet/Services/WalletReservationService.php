<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Domains\Wallet\Models\WalletMovement;
use DomainException;
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
     */
    public function capture(
        WalletHold $hold,
        string $movementType,
        ?float $amount = null,
        ?string $description = null,
    ) : WalletMovement {
        $amount ??= (float) $hold->amount;

        if ($amount > (float) $hold->amount) {
            throw new DomainException('No se puede capturar más de lo reservado en el hold.');
        }

        if (! $hold->isActive()) {
            throw new DomainException('El hold ya fue resuelto (capturado o liberado).');
        }

        $wallet = Wallet::findOrFail($hold->wallet_id);

        // El débito real (lock, split bono/balance, WalletMovement, broadcast,
        // notificación) lo hace WalletService::recordHoldCapture() — mismo
        // patrón que cualquier otro gasto de juego (recordCardPurchase,
        // recordTriviaEntry). Este servicio solo gestiona el ciclo de vida
        // del hold en sí.
        $movement = $this->walletService->recordHoldCapture(
            wallet: $wallet,
            amount: $amount,
            movementType: $movementType,
            description: $description,
            holdId: (string) $hold->id,
        );

        $hold->update([
            'status' => WalletHold::STATUS_CAPTURED,
            'captured_movement_id' => (string) $movement->id,
            'resolved_at' => now(),
        ]);

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
                throw new DomainException('Saldo disponible insuficiente para esta reserva.');
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
     */
    public function release(WalletHold $hold) : WalletHold
    {
        if (! $hold->isActive()) {
            throw new DomainException('El hold ya fue resuelto (capturado o liberado).');
        }

        $hold->update([
            'status' => WalletHold::STATUS_RELEASED,
            'resolved_at' => now(),
        ]);

        return $hold->fresh();
    }
}
