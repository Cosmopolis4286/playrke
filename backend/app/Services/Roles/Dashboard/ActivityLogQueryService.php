<?php

namespace App\Services\Roles\Dashboard;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\User;
use Carbon\Carbon;

/**
 * Reconstruye el feed de "Actividad reciente" a partir de las tablas que ya
 * son la fuente de verdad de cada evento (users, bingo_card_lots,
 * platform_win_events, wallet_movements), sin persistir una copia aparte.
 *
 * Uso: reconciliar el feed en el cliente tras una desconexión de WebSocket
 * (`GET /dashboard/activity-log?since=<iso>`) — el realtime sigue siendo la
 * vía principal, esto solo rellena el hueco de lo que se perdió mientras el
 * socket estuvo caído.
 */
final class ActivityLogQueryService
{
    private const DEFAULT_LOOKBACK_MINUTES = 15;
    private const MAX_ITEMS = 50;

    /**
     * @param bool $includeWalletEvents Movimientos procesados solo son visibles
     *                                  para SuperAdmin en el feed general (mismo
     *                                  criterio que useAdminActivityRealtime.ts).
     */
    public function since(?Carbon $since, bool $includeWalletEvents) : array
    {
        $since ??= now()->subMinutes(self::DEFAULT_LOOKBACK_MINUTES);

        $items = [
            ...$this->newUsers($since),
            ...$this->cardSales($since),
            ...$this->winEvents($since),
        ];

        if ($includeWalletEvents) {
            $items = [...$items, ...$this->processedMovements($since)];
        }

        usort($items, fn (array $a, array $b) => strcmp($b['timestamp'], $a['timestamp']));

        return array_slice($items, 0, self::MAX_ITEMS);
    }

    private function cardSales(Carbon $since) : array
    {
        return BingoCardLot::with('room:id,uuid_code')
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(self::MAX_ITEMS)
            ->get(['id', 'room_id', 'card_count', 'sale_amount', 'created_at'])
            ->map(fn (BingoCardLot $lot) => [
                'id' => "card-lot-{$lot->id}",
                'type' => 'bingo',
                'title' => "{$lot->card_count} cartón(es) vendido(s)",
                'description' => 'Sala ' . ($lot->room?->uuid_code ?? '—') . ' · $' . number_format((float) $lot->sale_amount, 2),
                'timestamp' => $lot->created_at->toIso8601String(),
            ])
            ->all();
    }

    private function newUsers(Carbon $since) : array
    {
        return User::with('roles:id,name')
            ->excludeSystemAccounts()
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(self::MAX_ITEMS)
            ->get(['id', 'username', 'email', 'status', 'created_at'])
            ->map(fn (User $user) => [
                'id' => "user-{$user->id}",
                'type' => 'success',
                'title' => 'Nuevo ' . ($user->roles->first()?->name ?? 'usuario'),
                'description' => "@{$user->username}" . ($user->email ? " · {$user->email}" : ''),
                'timestamp' => $user->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Solo depósitos/retiros ya resueltos (aprobados o rechazados) — mismo
     * alcance que el evento WalletMovementProcessed en tiempo real, no
     * incluye compras/premios/entradas que nacen ya COMPLETED sin pasar por
     * aprobación manual (esas no tienen processed_at).
     */
    private function processedMovements(Carbon $since) : array
    {
        return WalletMovement::whereIn('status', [WalletMovement::STATUS_COMPLETED, WalletMovement::STATUS_REJECTED])
            ->whereIn('type', [WalletMovement::TYPE_DEPOSIT, WalletMovement::TYPE_WITHDRAWAL])
            ->whereNotNull('processed_at')
            ->where('processed_at', '>=', $since)
            ->latest('processed_at')
            ->limit(self::MAX_ITEMS)
            ->get(['id', 'type', 'status', 'processed_at'])
            ->map(fn (WalletMovement $movement) => [
                'id' => "movement-processed-{$movement->id}",
                'type' => $movement->status === WalletMovement::STATUS_COMPLETED ? 'success' : 'danger',
                'title' => $movement->status === WalletMovement::STATUS_COMPLETED ? 'Movimiento aprobado' : 'Movimiento rechazado',
                'description' => "Tipo: {$movement->type}",
                'timestamp' => $movement->processed_at->toIso8601String(),
            ])
            ->all();
    }

    private function winEvents(Carbon $since) : array
    {
        return WinEvent::with(['user:id,username,first_name,last_name', 'currency:id,symbol'])
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(self::MAX_ITEMS)
            ->get()
            ->map(function(WinEvent $event) {
                $prize = $event->prize_kind === WinEvent::PRIZE_MONEY && $event->amount_usd !== null
                    ? '$' . number_format((float) $event->amount_usd, 2)
                    : ($event->prize_label ?? 'Premio');

                return [
                    'id' => "win-{$event->id}",
                    'type' => 'bingo',
                    'title' => "🏆 {$event->resolveWinnerName()} ganó en {$event->game_name}",
                    'description' => $prize,
                    'timestamp' => $event->created_at->toIso8601String(),
                ];
            })
            ->all();
    }
}
