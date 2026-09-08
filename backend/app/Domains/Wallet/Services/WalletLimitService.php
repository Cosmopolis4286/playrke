<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class WalletLimitService
{
    /**
     * Lanza DomainException si el depósito viola algún límite.
     * Se llama desde WalletService::requestDeposit() antes de ejecutar.
     *
     * @param Wallet $wallet   la wallet del usuario ya resuelta
     * @param float  $amount   monto solicitado
     * @param int    $kycLevel nivel KYC actual del usuario (0/1/2)
     */
    public function assertDepositWithinLimits(Wallet $wallet, float $amount, int $kycLevel) : void
    {
        $limit = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);

        if (! $limit) {
            throw WalletDomainException::depositLimitsNotConfigured();
        }

        if ($amount < $limit->min_deposit) {
            throw WalletDomainException::depositBelowMinimum((float) $limit->min_deposit);
        }

        if ($amount > $limit->max_deposit) {
            throw WalletDomainException::depositAboveMaximum((float) $limit->max_deposit);
        }
    }

    /**
     * Lanza DomainException si el retiro viola algún límite.
     * Se llama desde WalletService::requestWithdraw() antes de ejecutar.
     *
     * @param Wallet $wallet   la wallet del usuario ya resuelta
     * @param float  $amount   monto solicitado
     * @param int    $kycLevel nivel KYC actual del usuario (0/1/2)
     */
    public function assertWithinLimits(Wallet $wallet, float $amount, int $kycLevel) : void
    {
        $limit = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);

        if (! $limit) {
            // Sin configuración → bloquear por precaución
            throw WalletDomainException::withdrawalLimitsNotConfigured();
        }

        // ── Mínimo por transacción ────────────────────────────────────────────
        if ($amount < $limit->min_per_transaction) {
            throw WalletDomainException::withdrawalBelowMinimum((float) $limit->min_per_transaction);
        }

        // ── Máximo por transacción ────────────────────────────────────────────
        if ($amount > $limit->max_per_transaction) {
            throw WalletDomainException::withdrawalAboveMaximum((float) $limit->max_per_transaction);
        }

        $walletId = (string) $wallet->id;
        $sums = $this->withdrawalSums($walletId);

        // ── Límite diario ─────────────────────────────────────────────────────
        if (($sums['daily'] + $amount) > $limit->max_daily) {
            $remaining = max(0, $limit->max_daily - $sums['daily']);

            throw WalletDomainException::dailyWithdrawalLimitReached((float) $remaining);
        }

        // ── Límite semanal ────────────────────────────────────────────────────
        if (($sums['weekly'] + $amount) > $limit->max_weekly) {
            $remaining = max(0, $limit->max_weekly - $sums['weekly']);

            throw WalletDomainException::weeklyWithdrawalLimitReached((float) $remaining);
        }

        // ── Límite mensual ────────────────────────────────────────────────────
        if (($sums['monthly'] + $amount) > $limit->max_monthly) {
            $remaining = max(0, $limit->max_monthly - $sums['monthly']);

            throw WalletDomainException::monthlyWithdrawalLimitReached((float) $remaining);
        }
    }

    /**
     * Invalida la caché de sumas de retiro de una wallet. Debe llamarse
     * justo después de registrar cualquier WalletMovement de tipo retiro —
     * withdrawalSums() cachea hasta medianoche, así que sin esto un segundo
     * retiro en la misma ventana de caché sigue viendo la suma anterior a la
     * primera solicitud y assertWithinLimits() deja pasar retiros que en
     * conjunto exceden max_daily/max_weekly/max_monthly.
     */
    public function invalidateWithdrawalSums(string $walletId) : void
    {
        $today = Carbon::today()->format('Y-m-d');
        $week = Carbon::now()->format('Y-W');
        $month = Carbon::now()->format('Y-m');

        Cache::forget("wlimit:{$walletId}:sums:{$today}:{$week}:{$month}");
    }

    /**
     * Devuelve un resumen de uso de límites para el frontend/perfil.
     */
    public function usage(Wallet $wallet, int $kycLevel) : array
    {
        $limit = WalletLimit::resolve((string) $wallet->currency_id, $kycLevel);
        $walletId = (string) $wallet->id;

        if (! $limit) {
            return [];
        }

        $sums = $this->withdrawalSums($walletId);

        return [
            'kyc_level' => $kycLevel,
            'deposit' => ['min' => $limit->min_deposit, 'max' => $limit->max_deposit],
            'per_tx' => ['max' => $limit->max_per_transaction, 'min' => $limit->min_per_transaction],
            'daily' => ['used' => $sums['daily'],   'max' => $limit->max_daily],
            'weekly' => ['used' => $sums['weekly'],  'max' => $limit->max_weekly],
            'monthly' => ['used' => $sums['monthly'], 'max' => $limit->max_monthly],
        ];
    }

    /**
     * Agrega, para CADA WalletLimit configurado, cuánto ha retirado en total la
     * plataforma en el día/semana/mes actual dentro de esa combinación
     * (currency_id, kyc_level) — a diferencia de usage(), que es por wallet
     * individual, esto es la vista "administrador" para calibrar si un límite
     * está demasiado ajustado o demasiado holgado respecto al uso real.
     *
     * Se une wallet_movements → wallets (currency_id) → users (kyc_level del
     * dueño de la wallet, no el snapshot en el movimiento, que no existe) y se
     * agrupa por esa combinación. Cacheado igual que withdrawalSums(), expira
     * a medianoche.
     *
     * @return array<string, array{daily: float, weekly: float, monthly: float, transaction_count: int}>
     *         indexado por "{currency_id|global}:{kyc_level}"
     */
    public function usageSummary() : array
    {
        $today = Carbon::today()->format('Y-m-d');
        $cacheKey = "wlimit:usage_summary:{$today}";
        $secondsUntilMidnight = Carbon::now()->secondsUntilEndOfDay() + 1;

        return Cache::remember($cacheKey, $secondsUntilMidnight, function () {
            $monthStart = Carbon::now()->startOfMonth();
            $weekStart = Carbon::now()->startOfWeek();
            $todayStart = Carbon::today();

            $rows = WalletMovement::query()
                ->join('wallets', 'wallets.id', '=', 'wallet_movements.wallet_id')
                ->join('users', 'users.id', '=', 'wallets.user_id')
                ->where('wallet_movements.type', WalletMovement::TYPE_WITHDRAWAL)
                ->whereIn('wallet_movements.status', [WalletMovement::STATUS_PENDING, WalletMovement::STATUS_COMPLETED])
                ->where('wallet_movements.created_at', '>=', $monthStart)
                ->select([
                    'wallets.currency_id',
                    'users.kyc_level',
                    'wallet_movements.amount',
                    'wallet_movements.created_at',
                ])
                ->get();

            $summary = [];

            foreach ($rows as $row) {
                $key = $row->currency_id . ':' . $row->kyc_level;

                $summary[$key] ??= ['daily' => 0.0, 'weekly' => 0.0, 'monthly' => 0.0, 'transaction_count' => 0];

                $amount = (float) $row->amount;
                $summary[$key]['monthly'] += $amount;
                $summary[$key]['transaction_count']++;

                if ($row->created_at >= $weekStart) {
                    $summary[$key]['weekly'] += $amount;
                }
                if ($row->created_at >= $todayStart) {
                    $summary[$key]['daily'] += $amount;
                }
            }

            return $summary;
        });
    }

    /**
     * Sumas de retiros COMPLETADOS o PENDING para los tres períodos en una sola query.
     * Caché por período con clave que incluye el segmento temporal, expiración al fin del día.
     *
     * @return array{daily: float, weekly: float, monthly: float}
     */
    private function withdrawalSums(string $walletId) : array
    {
        $today = Carbon::today()->format('Y-m-d');
        $week = Carbon::now()->format('Y-W');
        $month = Carbon::now()->format('Y-m');

        $cacheKey = "wlimit:{$walletId}:sums:{$today}:{$week}:{$month}";
        $secondsUntilMidnight = Carbon::now()->secondsUntilEndOfDay() + 1;

        return Cache::remember($cacheKey, $secondsUntilMidnight, function() use ($walletId) {
            $monthStart = Carbon::now()->startOfMonth();
            $weekStart = Carbon::now()->startOfWeek();
            $todayStart = Carbon::today();

            $rows = WalletMovement::where('wallet_id', $walletId)
                ->where('type', WalletMovement::TYPE_WITHDRAWAL)
                ->whereIn('status', [WalletMovement::STATUS_PENDING, WalletMovement::STATUS_COMPLETED])
                ->where('created_at', '>=', $monthStart)
                ->get(['amount', 'created_at']);

            $daily = 0.0;
            $weekly = 0.0;
            $monthly = 0.0;

            foreach ($rows as $row) {
                $amount = (float) $row->amount;
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
}
