<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Services;

use App\Domains\Marketplace\Crypto\Contracts\CryptoGateway;
use App\Domains\Marketplace\Crypto\Exceptions\CryptoCustodyNotConfiguredException;
use App\Domains\Marketplace\Crypto\Exceptions\InvalidWithdrawalStateException;
use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Domains\Marketplace\Crypto\Models\CryptoWithdrawal;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Domains\Wallet\Services\WalletLimitService;
use App\Domains\Wallet\Services\WalletReservationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta el ciclo de vida completo de un retiro cripto — ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 14 y 16.
 *
 * REGLA CENTRAL: el saldo del usuario nunca disminuye definitivamente antes
 * de un estado coherente. request() reserva el monto vía WalletHold (no
 * toca wallets.balance); solo complete() lo captura como gasto real, y
 * únicamente después de que la transacción on-chain ya fue confirmada.
 * Cualquier salida antes de eso (reject/cancel/fail) libera el hold sin
 * mover saldo.
 *
 * FASE 7 (esqueleto, sin custodia real): broadcast() SIEMPRE falla con
 * CryptoCustodyNotConfiguredException porque llama a
 * CryptoGateway::broadcastWithdrawal(), y la única implementación hoy
 * (TronUsdtProvider) es un stub — ver ese archivo. Esto significa que en
 * este estado NINGÚN retiro puede pasar de APPROVED a PROCESSING/
 * BROADCASTED en la práctica; el flujo completo (incluido ese límite) está
 * construido y probado para que el día que exista un CryptoGateway real
 * detrás, baste con reemplazar ese binding — ningún método de este
 * servicio necesita cambiar.
 */
final class WithdrawalService
{
    public function __construct(
        private readonly WalletFraudService $fraudService,
        private readonly WalletLimitService $limitService,
        private readonly WalletReservationService $reservationService,
        private readonly CryptoGateway $gateway,
    ) {
    }

    /**
     * REVIEWING -> APPROVED. No mueve saldo — solo autoriza a que
     * broadcast() pueda ejecutarse.
     */
    public function approve(CryptoWithdrawal $withdrawal, User $admin) : CryptoWithdrawal
    {
        $affected = CryptoWithdrawal::where('id', $withdrawal->id)
            ->where('status', CryptoWithdrawal::STATUS_REVIEWING)
            ->update([
                'status' => CryptoWithdrawal::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

        if ($affected === 0) {
            throw new InvalidWithdrawalStateException('El retiro no está en estado REVIEWING.');
        }

        return $withdrawal->fresh();
    }

    /**
     * APPROVED -> PROCESSING -> BROADCASTED. Pide al CryptoGateway que
     * firme y transmita — con la implementación stub actual
     * (TronUsdtProvider) esto SIEMPRE lanza
     * CryptoCustodyNotConfiguredException, dejando el registro en FAILED
     * (nunca a medio camino, nunca con el hold ya capturado).
     */
    public function broadcast(CryptoWithdrawal $withdrawal) : CryptoWithdrawal
    {
        $affected = CryptoWithdrawal::where('id', $withdrawal->id)
            ->where('status', CryptoWithdrawal::STATUS_APPROVED)
            ->update(['status' => CryptoWithdrawal::STATUS_PROCESSING]);

        if ($affected === 0) {
            throw new InvalidWithdrawalStateException('El retiro no está en estado APPROVED.');
        }

        try {
            $txHash = $this->gateway->broadcastWithdrawal($withdrawal->destination_address, (float) $withdrawal->amount);
        } catch (CryptoCustodyNotConfiguredException $e) {
            // FAILED es un estado terminal — el hold debe liberarse aquí
            // mismo, nunca dejarlo para un método posterior. fail() exige
            // status PROCESSING/BROADCASTED como entrada, así que no sirve
            // para liberar un hold sobre un registro que este catch ya dejó
            // en FAILED.
            CryptoWithdrawal::where('id', $withdrawal->id)->update(['status' => CryptoWithdrawal::STATUS_FAILED]);
            $this->releaseHoldIfActive($withdrawal);
            Log::error('[WithdrawalService] broadcast() falló — sin custodia real configurada (Fase 7 esqueleto)', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $transaction = CryptoTransaction::create([
            'tx_hash' => $txHash,
            'network' => $withdrawal->network,
            'token' => strtoupper($withdrawal->currency->iso_code),
            'direction' => CryptoTransaction::DIRECTION_WITHDRAWAL,
            'from_address' => 'internal-treasury',
            'to_address' => $withdrawal->destination_address,
            'amount' => $withdrawal->amount,
            'status' => CryptoTransaction::STATUS_PENDING,
            'confirmations' => 0,
            'user_id' => $withdrawal->user_id,
            'wallet_id' => $withdrawal->wallet_id,
        ]);

        CryptoWithdrawal::where('id', $withdrawal->id)->update([
            'status' => CryptoWithdrawal::STATUS_BROADCASTED,
            'crypto_transaction_id' => $transaction->id,
        ]);

        return $withdrawal->fresh();
    }

    /**
     * Cancela una solicitud del propio usuario, antes de que un admin la
     * apruebe. Estados permitidos: REQUESTED, RESERVED, REVIEWING — una vez
     * APPROVED, solo un admin puede detenerla (no hay un caso de uso real
     * hoy para que el usuario cancele después de aprobado).
     */
    public function cancel(CryptoWithdrawal $withdrawal) : CryptoWithdrawal
    {
        return DB::transaction(function() use ($withdrawal) {
            $affected = CryptoWithdrawal::whereIn('status', [
                CryptoWithdrawal::STATUS_REQUESTED,
                CryptoWithdrawal::STATUS_RESERVED,
                CryptoWithdrawal::STATUS_REVIEWING,
            ])
                ->where('id', $withdrawal->id)
                ->update(['status' => CryptoWithdrawal::STATUS_CANCELLED]);

            if ($affected === 0) {
                throw new InvalidWithdrawalStateException('El retiro ya no puede cancelarse desde su estado actual.');
            }

            $this->releaseHoldIfActive($withdrawal);

            return $withdrawal->fresh();
        });
    }

    /**
     * BROADCASTED -> COMPLETED. Solo aquí se captura el hold como gasto
     * real — después de que la transacción on-chain ya alcanzó las
     * confirmaciones mínimas (verificado por el caller, ej. un job
     * periódico que consulta CryptoGateway::getConfirmations()).
     */
    public function complete(CryptoWithdrawal $withdrawal) : CryptoWithdrawal
    {
        return DB::transaction(function() use ($withdrawal) {
            $affected = CryptoWithdrawal::where('id', $withdrawal->id)
                ->where('status', CryptoWithdrawal::STATUS_BROADCASTED)
                ->update(['status' => CryptoWithdrawal::STATUS_CONFIRMED]);

            if ($affected === 0) {
                throw new InvalidWithdrawalStateException('El retiro no está en estado BROADCASTED.');
            }

            $hold = WalletHold::findOrFail($withdrawal->wallet_hold_id);
            $this->reservationService->capture(
                hold: $hold,
                movementType: WalletMovement::TYPE_CRYPTO_WITHDRAWAL,
                description: "Retiro cripto completado — {$withdrawal->destination_address}",
            );

            CryptoWithdrawal::where('id', $withdrawal->id)->update(['status' => CryptoWithdrawal::STATUS_COMPLETED]);

            Log::info('[WithdrawalService] Retiro completado', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'amount' => $withdrawal->amount,
            ]);

            return $withdrawal->fresh();
        });
    }

    /**
     * Marca un retiro BROADCASTED/PROCESSING como fallido tras confirmar
     * que la transacción on-chain nunca se confirmó (ej. reemplazada,
     * expiró) — libera el hold, el usuario recupera su saldo.
     */
    public function fail(CryptoWithdrawal $withdrawal) : CryptoWithdrawal
    {
        return DB::transaction(function() use ($withdrawal) {
            $affected = CryptoWithdrawal::whereIn('status', [
                CryptoWithdrawal::STATUS_PROCESSING,
                CryptoWithdrawal::STATUS_BROADCASTED,
            ])
                ->where('id', $withdrawal->id)
                ->update(['status' => CryptoWithdrawal::STATUS_FAILED]);

            if ($affected === 0) {
                throw new InvalidWithdrawalStateException('El retiro no puede marcarse como fallido desde su estado actual.');
            }

            $this->releaseHoldIfActive($withdrawal);

            return $withdrawal->fresh();
        });
    }

    /**
     * REVIEWING -> REJECTED. Libera el hold — el usuario recupera su saldo
     * disponible de inmediato, sin generar ningún WalletMovement.
     */
    public function reject(CryptoWithdrawal $withdrawal, User $admin, string $reason) : CryptoWithdrawal
    {
        return DB::transaction(function() use ($withdrawal, $admin, $reason) {
            $affected = CryptoWithdrawal::where('id', $withdrawal->id)
                ->where('status', CryptoWithdrawal::STATUS_REVIEWING)
                ->update([
                    'status' => CryptoWithdrawal::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

            if ($affected === 0) {
                throw new InvalidWithdrawalStateException('El retiro no está en estado REVIEWING.');
            }

            $this->releaseHoldIfActive($withdrawal);

            return $withdrawal->fresh();
        });
    }

    /**
     * REQUESTED -> RESERVED. Valida fraude/límites, reserva el monto vía
     * WalletHold. No mueve saldo real.
     */
    public function request(
        User $user,
        Wallet $wallet,
        float $amount,
        string $destinationAddress,
    ) : CryptoWithdrawal {
        $this->fraudService->checkWithdrawalVelocity((string) $user->id);
        $kycLevel = (int) ($user->kyc_level ?? 0);
        $this->limitService->assertWithinLimits($wallet, $amount, $kycLevel);

        return DB::transaction(function() use ($user, $wallet, $amount, $destinationAddress) {
            $withdrawal = CryptoWithdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency_id,
                'amount' => $amount,
                'destination_address' => $destinationAddress,
                'network' => $this->gateway->network(),
                'status' => CryptoWithdrawal::STATUS_REQUESTED,
            ]);

            // hold() valida saldo disponible y lanza DomainException si no
            // alcanza — el registro queda en REQUESTED, nunca a medio camino.
            $hold = $this->reservationService->hold(
                wallet: $wallet,
                amount: $amount,
                description: "Retiro cripto #{$withdrawal->id} a {$destinationAddress}",
            );

            $withdrawal->update([
                'wallet_hold_id' => $hold->id,
                'status' => CryptoWithdrawal::STATUS_RESERVED,
            ]);

            return $withdrawal->fresh();
        });
    }

    /**
     * RESERVED -> REVIEWING. Marca la solicitud como en revisión manual —
     * paso explícito antes de aprobar/rechazar, para que quede registrado
     * quién la está evaluando (reviewed_by se completa recién en approve/
     * reject, no aquí).
     */
    public function startReview(CryptoWithdrawal $withdrawal) : CryptoWithdrawal
    {
        $affected = CryptoWithdrawal::where('id', $withdrawal->id)
            ->where('status', CryptoWithdrawal::STATUS_RESERVED)
            ->update(['status' => CryptoWithdrawal::STATUS_REVIEWING]);

        if ($affected === 0) {
            throw new InvalidWithdrawalStateException('El retiro no está en estado RESERVED.');
        }

        return $withdrawal->fresh();
    }

    private function releaseHoldIfActive(CryptoWithdrawal $withdrawal) : void
    {
        if (! $withdrawal->wallet_hold_id) {
            return;
        }

        $hold = WalletHold::find($withdrawal->wallet_hold_id);
        if ($hold && $hold->isActive()) {
            $this->reservationService->release($hold);
        }
    }
}
