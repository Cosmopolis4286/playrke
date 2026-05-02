<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Models\WithdrawalLimit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class WithdrawalLimitService
{
    /**
     * Lanza DomainException si el retiro viola algún límite.
     * Se llama desde WalletService::requestWithdraw() antes de ejecutar.
     *
     * @param  Wallet $wallet      La wallet del usuario ya resuelta.
     * @param  float  $amount      Monto solicitado.
     * @param  int    $kycLevel    Nivel KYC actual del usuario (0/1/2).
     */
    public function assertWithinLimits(Wallet $wallet, float $amount, int $kycLevel): void
    {
        $limit = WithdrawalLimit::resolve((string) $wallet->currency_id, $kycLevel);

        if (! $limit) {
            // Sin configuración → bloquear por precaución
            throw new \DomainException('No hay límites de retiro configurados para esta moneda. Contacta soporte.');
        }

        // ── Mínimo por transacción ────────────────────────────────────────────
        if ($amount < $limit->min_per_transaction) {
            throw new \DomainException(
                "El monto mínimo de retiro es {$limit->min_per_transaction}."
            );
        }

        // ── Máximo por transacción ────────────────────────────────────────────
        if ($amount > $limit->max_per_transaction) {
            throw new \DomainException(
                "El monto máximo por retiro es {$limit->max_per_transaction}."
            );
        }

        $walletId = (string) $wallet->id;

        // ── Límite diario ─────────────────────────────────────────────────────
        $dailyUsed = $this->sumWithdrawals($walletId, 'daily');
        if (($dailyUsed + $amount) > $limit->max_daily) {
            $remaining = max(0, $limit->max_daily - $dailyUsed);
            throw new \DomainException(
                "Límite diario de retiro alcanzado. Puedes retirar hasta {$remaining} hoy."
            );
        }

        // ── Límite semanal ────────────────────────────────────────────────────
        $weeklyUsed = $this->sumWithdrawals($walletId, 'weekly');
        if (($weeklyUsed + $amount) > $limit->max_weekly) {
            $remaining = max(0, $limit->max_weekly - $weeklyUsed);
            throw new \DomainException(
                "Límite semanal de retiro alcanzado. Puedes retirar hasta {$remaining} esta semana."
            );
        }

        // ── Límite mensual ────────────────────────────────────────────────────
        $monthlyUsed = $this->sumWithdrawals($walletId, 'monthly');
        if (($monthlyUsed + $amount) > $limit->max_monthly) {
            $remaining = max(0, $limit->max_monthly - $monthlyUsed);
            throw new \DomainException(
                "Límite mensual de retiro alcanzado. Puedes retirar hasta {$remaining} este mes."
            );
        }
    }

    /**
     * Suma de retiros COMPLETADOS o PENDING en el período indicado.
     * Usa caché corta (60 s) para no golpear la DB en cada validación.
     */
    private function sumWithdrawals(string $walletId, string $period): float
    {
        $cacheKey = "wlimit:{$walletId}:{$period}:" . now()->format($this->cacheSegment($period));

        return (float) Cache::remember($cacheKey, 60, function () use ($walletId, $period) {
            $from = match ($period) {
                'daily'   => Carbon::today(),
                'weekly'  => Carbon::now()->startOfWeek(),
                'monthly' => Carbon::now()->startOfMonth(),
            };

            return WalletMovement::where('wallet_id', $walletId)
                ->where('type', WalletMovement::TYPE_WITHDRAWAL)
                ->whereIn('status', [WalletMovement::STATUS_PENDING, WalletMovement::STATUS_COMPLETED])
                ->where('created_at', '>=', $from)
                ->sum('amount');
        });
    }

    private function cacheSegment(string $period): string
    {
        return match ($period) {
            'daily'   => 'Y-m-d',
            'weekly'  => 'Y-W',
            'monthly' => 'Y-m',
        };
    }

    /**
     * Devuelve un resumen de uso de límites para el frontend/perfil.
     */
    public function usage(Wallet $wallet, int $kycLevel): array
    {
        $limit    = WithdrawalLimit::resolve((string) $wallet->currency_id, $kycLevel);
        $walletId = (string) $wallet->id;

        if (! $limit) {
            return [];
        }

        return [
            'kyc_level'    => $kycLevel,
            'per_tx'       => ['max' => $limit->max_per_transaction, 'min' => $limit->min_per_transaction],
            'daily'        => ['used' => $this->sumWithdrawals($walletId, 'daily'),   'max' => $limit->max_daily],
            'weekly'       => ['used' => $this->sumWithdrawals($walletId, 'weekly'),  'max' => $limit->max_weekly],
            'monthly'      => ['used' => $this->sumWithdrawals($walletId, 'monthly'), 'max' => $limit->max_monthly],
        ];
    }
}
