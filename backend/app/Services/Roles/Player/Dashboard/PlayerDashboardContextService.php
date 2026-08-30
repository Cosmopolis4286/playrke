<?php

namespace App\Services\Roles\Player\Dashboard;

use App\DTOs\Roles\Player\Dashboard\PlayerDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Games\Trivia\TriviaParticipant;
use App\Models\GameParticipation;
use App\Models\KycDocument;
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
        $userId = (string) $user?->getKey();

        return new PlayerDashboardContextDTO(
            userName:       $user?->first_name ?? $user?->username ?? 'Jugador',
            stats:          $this->buildStats($user, $now),
            recentActivity: $this->buildRecentActivity($user),
            prizesWon:      $this->buildPrizesWon($userId),
            triviaActivity: $this->buildTriviaActivity($userId),
            kycStatus:      $this->buildKycStatus($userId),
            period:         $period,
            showCrossGameBridge: $this->buildShowCrossGameBridge($userId),
        );
    }

    // -------------------------------------------------------------------------
    // Puente cross-game Bingo → Trivia (Fase 8) — misma condición que
    // CrossGameBridgeService::qualifiesForFirstTriviaBonus(), pero evaluada
    // fuera del flujo de otorgamiento de XP, solo para decidir si se muestra
    // el banner de invitación en el dashboard.
    // -------------------------------------------------------------------------

    private function buildShowCrossGameBridge(string $userId): bool
    {
        $alreadyPlayedTrivia = GameParticipation::where('user_id', $userId)
            ->where('game', GameParticipation::GAME_TRIVIA)
            ->exists();

        if ($alreadyPlayedTrivia) {
            return false;
        }

        return GameParticipation::where('user_id', $userId)
            ->where('game', GameParticipation::GAME_BINGO)
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Stats del jugador
    // -------------------------------------------------------------------------

    private function buildStats($user, Carbon $now): array
    {
        $userId       = (string) $user?->getKey();
        $startOfMonth = $now->copy()->startOfMonth();

        // Todas las billeteras activas del jugador (multi-moneda) — antes
        // solo se tomaba ->first(), ignorando saldo en otras monedas si el
        // jugador tuviera más de una wallet activa.
        $activeWallets = Wallet::with('currency')
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->get();

        $primaryWallet  = $activeWallets->first();
        $currency       = $primaryWallet?->currency;
        $currencySymbol = $currency?->symbol ?? '';
        $currencyCode   = $currency?->iso_code ?? '';
        $balance        = $primaryWallet ? (float) $primaryWallet->balance : 0.0;
        $bonusBalance   = $primaryWallet ? (float) $primaryWallet->bonus_balance : 0.0;
        $formattedBalance = $currency
            ? $currency->formatAmount($balance)
            : number_format($balance, 2, ',', '.');
        $formattedBonus = $currency
            ? $currency->formatAmount($bonusBalance)
            : number_format($bonusBalance, 2, ',', '.');

        // Salas disponibles filtradas por la(s) moneda(s) del jugador — antes
        // era un conteo global sin relación con lo que el jugador realmente
        // puede jugar.
        $playerCurrencyIds = $activeWallets->pluck('currency_id')->map(fn ($id) => (string) $id)->unique();
        $activeRooms = $playerCurrencyIds->isEmpty()
            ? BingoRoom::active()->count()
            : BingoRoom::active()
                ->whereHas('config', function ($q) use ($playerCurrencyIds) {
                    foreach ($playerCurrencyIds as $currencyId) {
                        $q->orWhereJsonContains('allowed_currencies', $currencyId);
                    }
                })
                ->count();

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
            'bonusBalance' => [
                'label'       => 'Saldo de bono',
                'value'       => trim($currencySymbol . ' ' . $formattedBonus),
                'icon'        => 'fas fa-gift',
                'variant'     => $bonusBalance > 0 ? 'success' : 'secondary',
                'description' => 'Solo usable dentro de la app',
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
    // Premios ganados con detalle (monto, patrón, sala) — antes "partidas
    // ganadas" solo mostraba el conteo, sin el detalle real del premio que
    // ya existe en BingoCardPrize.
    // -------------------------------------------------------------------------

    private function buildPrizesWon(string $userId): array
    {
        return BingoCardPrize::byCreator($userId)
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'room_id', 'prize_type', 'winning_pattern', 'usd_amount', 'local_amount', 'prize_status', 'claimed_at', 'created_at'])
            ->map(fn (BingoCardPrize $prize) => [
                'id'           => (string) $prize->getKey(),
                'room_name'    => $prize->room?->uuid_code ?? ('…' . substr((string) $prize->room_id, -6)),
                'prize_type'   => $prize->prize_type,
                'prize_label'  => BingoCardPrize::PRIZE_TYPES[$prize->prize_type] ?? $prize->prize_type,
                'amount_usd'   => (float) $prize->usd_amount,
                'status'       => $prize->prize_status,
                'status_label' => BingoCardPrize::PRIZE_STATUSES[$prize->prize_status] ?? $prize->prize_status,
                'created_at'   => $prize->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Actividad reciente en Trivia — trivia_participants vive en la conexión
    // 'pgsql_trivia' (Postgres, separada), sin belongsTo(User) directo.
    // -------------------------------------------------------------------------

    private function buildTriviaActivity(string $userId): array
    {
        return TriviaParticipant::query()
            ->where('user_id', $userId)
            ->latest('registered_at')
            ->limit(10)
            ->get(['id', 'room_id', 'status', 'final_rank', 'prize_won', 'correct_answers', 'wrong_answers', 'registered_at'])
            ->map(fn (TriviaParticipant $p) => [
                'id'              => (string) $p->getKey(),
                'room_id'         => (string) $p->room_id,
                'status'          => $p->status,
                'final_rank'      => $p->final_rank,
                'prize_won'       => (float) $p->prize_won,
                'correct_answers' => $p->correct_answers,
                'wrong_answers'   => $p->wrong_answers,
                'registered_at'   => $p->registered_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Estado del documento KYC propio
    // -------------------------------------------------------------------------

    private function buildKycStatus(string $userId): ?array
    {
        $doc = KycDocument::where('user_id', $userId)->latest('created_at')->first(['id', 'status', 'rejection_reason', 'reviewed_at']);

        if (!$doc) {
            return null;
        }

        return [
            'status'           => $doc->status,
            'rejection_reason' => $doc->rejection_reason,
            'reviewed_at'      => $doc->reviewed_at?->toIso8601String(),
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
