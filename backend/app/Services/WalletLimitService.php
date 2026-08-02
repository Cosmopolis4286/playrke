<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletLimit;
use App\Models\WalletMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class WalletLimitService
{
    /**
     * Lanza DomainException si el depósito viola algún límite.
     * Se llama desde WalletService::requestDeposit() antes de ejecutar.
     *
     * @param  Wallet $wallet   La wallet del usuario ya resuelta.
     * @param  float  $amount   Monto solicitado.
     * @param  int    $kycLevel Nivel KYC actual del usuario (0/1/2).
     */
    public function assertDepositWithinLimits(Wallet $wallet, float $amount, int $kycLevel): void
    {
        $limit = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);

        if (! $limit) {
            throw new \DomainException('No hay límites de depósito configurados para esta moneda. Contacta soporte.');
        }

        if ($amount < $limit->min_deposit) {
            throw new \DomainException(
                "El monto mínimo de depósito es {$limit->min_deposit}."
            );
        }

        if ($amount > $limit->max_deposit) {
            throw new \DomainException(
                "El monto máximo por depósito es {$limit->max_deposit}."
            );
        }
    }

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
        $limit = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);

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
        $sums     = $this->withdrawalSums($walletId);

        // ── Límite diario ─────────────────────────────────────────────────────
        if (($sums['daily'] + $amount) > $limit->max_daily) {
            $remaining = max(0, $limit->max_daily - $sums['daily']);
            throw new \DomainException(
                "Límite diario de retiro alcanzado. Puedes retirar hasta {$remaining} hoy."
            );
        }

        // ── Límite semanal ────────────────────────────────────────────────────
        if (($sums['weekly'] + $amount) > $limit->max_weekly) {
            $remaining = max(0, $limit->max_weekly - $sums['weekly']);
            throw new \DomainException(
                "Límite semanal de retiro alcanzado. Puedes retirar hasta {$remaining} esta semana."
            );
        }

        // ── Límite mensual ────────────────────────────────────────────────────
        if (($sums['monthly'] + $amount) > $limit->max_monthly) {
            $remaining = max(0, $limit->max_monthly - $sums['monthly']);
            throw new \DomainException(
                "Límite mensual de retiro alcanzado. Puedes retirar hasta {$remaining} este mes."
            );
        }
    }

    /**
     * Sumas de retiros COMPLETADOS o PENDING para los tres períodos en una sola query.
     * Caché por período con clave que incluye el segmento temporal, expiración al fin del día.
     *
     * @return array{daily: float, weekly: float, monthly: float}
     */
    private function withdrawalSums(string $walletId): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $week  = Carbon::now()->format('Y-W');
        $month = Carbon::now()->format('Y-m');

        $cacheKey = "wlimit:{$walletId}:sums:{$today}:{$week}:{$month}";
        $secondsUntilMidnight = Carbon::now()->secondsUntilEndOfDay() + 1;

        return Cache::remember($cacheKey, $secondsUntilMidnight, function () use ($walletId) {
            $monthStart  = Carbon::now()->startOfMonth();
            $weekStart   = Carbon::now()->startOfWeek();
            $todayStart  = Carbon::today();

            $rows = WalletMovement::where('wallet_id', $walletId)
                ->where('type', WalletMovement::TYPE_WITHDRAWAL)
                ->whereIn('status', [WalletMovement::STATUS_PENDING, WalletMovement::STATUS_COMPLETED])
                ->where('created_at', '>=', $monthStart)
                ->get(['amount', 'created_at']);

            $daily   = 0.0;
            $weekly  = 0.0;
            $monthly = 0.0;

            foreach ($rows as $row) {
                $amount   = (float) $row->amount;
                $monthly += $amount;
                if ($row->created_at >= $weekStart) {
                    $weekly += $amount;
                }
                if ($row->created_at >= $todayStart) {
                    $daily += $amount;
                }
            }

            return compact('daily', 'weekly', 'monthly');
        });
    }

    /**
     * Devuelve un resumen de uso de límites para el frontend/perfil.
     */
    public function usage(Wallet $wallet, int $kycLevel): array
    {
        $limit    = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);
        $walletId = (string) $wallet->id;

        if (! $limit) {
            return [];
        }

        $sums = $this->withdrawalSums($walletId);

        return [
            'kyc_level' => $kycLevel,
            'deposit'   => ['min' => $limit->min_deposit, 'max' => $limit->max_deposit],
            'per_tx'    => ['max' => $limit->max_per_transaction, 'min' => $limit->min_per_transaction],
            'daily'     => ['used' => $sums['daily'],   'max' => $limit->max_daily],
            'weekly'    => ['used' => $sums['weekly'],  'max' => $limit->max_weekly],
            'monthly'   => ['used' => $sums['monthly'], 'max' => $limit->max_monthly],
        ];
    }
}
