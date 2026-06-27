<?php

namespace App\Services\Roles\Player\Dashboard;

use App\DTOs\Roles\Player\Dashboard\PlayerDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
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
        $currency       = $wallet?->currency;
        $currencySymbol = $currency?->symbol ?? '';
        $currencyCode   = $currency?->iso_code ?? '';
        $formattedBalance = $currency
            ? $currency->formatAmount($balance)
            : number_format($balance, 2, ',', '.');

        // Salas activas disponibles
        $activeRooms = BingoRoom::active()->count();

        // Cartones del jugador este mes
        $cardsThisMonth = BingoCard::where('creator_user_id', $userId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Total cartones del jugador
        $totalCards = BingoCard::where('creator_user_id', $userId)->count();

        // Partidas jugadas (salas en las que participó como jugador)
        $gamesPlayed = BingoRoomUser::where('user_id', $userId)
            ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
            ->count();

        // Partidas ganadas
        $gamesWon = BingoRoomUser::where('user_id', $userId)
            ->whereIn('role', [BingoRoomUser::ROLE_PLAYER, BingoRoomUser::ROLE_PLAYER_SELLER])
            ->where('winner', 'Y')
            ->count();

        return [
            'walletBalance' => [
                'label'       => 'Saldo disponible',
                'value'       => trim($currencySymbol . ' ' . $formattedBalance),
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
            'gamesPlayed' => [
                'label'       => 'Partidas jugadas',
                'value'       => $gamesPlayed,
                'icon'        => 'fas fa-history',
                'variant'     => 'warning',
                'description' => "{$gamesWon} victorias",
                'href'        => '/bingo/my-games',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente (últimos cartones comprados)
    // -------------------------------------------------------------------------

    private function buildRecentActivity($user): array
    {
        $userId = (string) $user?->getKey();

        return BingoCard::where('creator_user_id', $userId)
            ->with(['room.config', 'room.cardPrices.currency', 'currency'])
            ->latest()
            ->limit(8)
            ->get(['id', 'room_id', 'serial_number', 'status', 'currency_id', 'created_at'])
            ->map(function (BingoCard $card) {
                $roomDate = $card->room?->config?->date?->format('d/m/Y') ?? 'N/D';
                $roomTime = $card->room?->config?->start_time?->format('H:i') ?? '';
                
                // Obtener precio desde la sala (card_prices)
                $cardPrice = $card->room?->cardPrices?->first();
                $price = $cardPrice?->card_price;
                $cardCurrency = $cardPrice?->currency ?? $card->currency;
                $priceCurrency = $cardCurrency?->iso_code ?? '';

                return [
                    'id'          => (string) $card->getKey(),
                    'type'        => 'bingo',
                    'title'       => "Cartón #{$card->serial_number}",
                    'description' => "Sala del {$roomDate}" . ($roomTime ? " a las {$roomTime}" : ''),
                    'timestamp'   => $card->created_at->toIso8601String(),
                    'actor'       => $card->serial_number,
                    'meta'        => [
                        'status'   => $card->status,
                        'price'    => $price ? ($cardCurrency?->formatAmount((float) $price) ?? number_format((float) $price, 2, ',', '.')) : null,
                        'currency' => $priceCurrency,
                    ],
                ];
            })
            ->all();
    }
}
