<?php

namespace App\Services\Roles\Player\Dashboard;

use App\DTOs\Roles\Player\Dashboard\PlayerDashboardContextDTO;
use App\Models\Games\Bingo\Card\BingoCard;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

final class PlayerDashboardContextService
{
    public function dashboardContext(): PlayerDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        return new PlayerDashboardContextDTO(
            userName:       $user?->first_name ?? $user?->username ?? 'Jugador',
            stats:          $this->buildStats($user, $now),
            recentActivity: $this->buildRecentActivity($user),
            period:         $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats del jugador
    // -------------------------------------------------------------------------

    private function buildStats($user, Carbon $now): array
    {
        $userId       = (string) $user?->getKey();
        $startOfMonth = $now->copy()->startOfMonth();

        // Billetera principal activa
        $wallet = Wallet::with('currency')
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->first();

        $balance       = $wallet ? (float) $wallet->balance : 0.0;
        $currencySymbol = $wallet?->currency?->symbol ?? '';
        $currencyCode   = $wallet?->currency?->iso_code ?? '';

        // Salas activas disponibles
        $activeRooms = BingoRoom::active()->count();

        // Cartones del jugador este mes
        $cardsThisMonth = BingoCard::where('creator_room_user_id', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Total cartones del jugador
        $totalCards = BingoCard::where('creator_room_user_id', $userId)->count();

        return [
            'walletBalance' => [
                'label'       => 'Saldo disponible',
                'value'       => $currencySymbol . ' ' . number_format($balance, 2),
                'icon'        => 'fas fa-wallet',
                'variant'     => 'primary',
                'description' => $currencyCode ?: 'Sin billetera activa',
            ],
            'activeRooms' => [
                'label'   => 'Salas disponibles',
                'value'   => $activeRooms,
                'icon'    => 'fas fa-hat-wizard',
                'variant' => 'success',
            ],
            'cardsThisMonth' => [
                'label'       => 'Cartones este mes',
                'value'       => $cardsThisMonth,
                'icon'        => 'fas fa-ticket-alt',
                'variant'     => 'info',
                'description' => "{$totalCards} en total",
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente (últimos cartones comprados)
    // -------------------------------------------------------------------------

    private function buildRecentActivity($user): array
    {
        $userId = (string) $user?->getKey();

        return BingoCard::where('creator_room_user_id', $userId)
            ->with(['room.config', 'currency'])
            ->latest()
            ->limit(8)
            ->get(['id', 'room_id', 'serial_number', 'status', 'price', 'currency_id', 'created_at'])
            ->map(function (BingoCard $card) {
                $roomDate = $card->room?->config?->date?->format('d/m/Y') ?? 'N/D';
                $roomTime = $card->room?->config?->start_time?->format('H:i') ?? '';

                return [
                    'id'          => (string) $card->getKey(),
                    'type'        => 'bingo',
                    'title'       => "Cartón #{$card->serial_number}",
                    'description' => "Sala del {$roomDate}" . ($roomTime ? " a las {$roomTime}" : ''),
                    'timestamp'   => $card->created_at->toIso8601String(),
                    'actor'       => $card->serial_number,
                    'meta'        => [
                        'status'   => $card->status,
                        'price'    => $card->price ? number_format($card->price, 2) : null,
                        'currency' => $card->currency?->iso_code ?? '',
                    ],
                ];
            })
            ->all();
    }
}
