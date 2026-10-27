<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\GameParticipation;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Jugadores activos por ventana de tiempo y clientes recurrentes por
 * vendedor — hoy solo existía conteo total de entidades, no "activos en
 * ventana" (ver progreso/estrategia-producto-plan.md, Fase 3).
 *
 * "Recurrente" se define sobre bingo_card_payments (no bingo_creator_sales,
 * que es un agregado por sala/creador SIN comprador individual — ver
 * hallazgo documentado en el changelog del plan): un comprador (user_id o
 * external_player_id) que aparece más de una vez con payment_status=APPROVED
 * bajo el mismo seller_user_id.
 */
final class PlayerActivitySummaryService
{
    private const CACHE_KEY = 'superadmin:player-activity-summary';
    private const CACHE_TTL = 90; // mismo TTL que el resto de agregados del dashboard superadmin

    public function getSummary() : array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildSummary());
    }

    /**
     * Jugadores distintos con al menos una participación registrada
     * (game_participations, cross-juego) en cada ventana.
     */
    private function activePlayers() : array
    {
        return [
            'daily' => GameParticipation::where('created_at', '>=', now()->subDay())->distinct('user_id')->count('user_id'),
            'weekly' => GameParticipation::where('created_at', '>=', now()->subWeek())->distinct('user_id')->count('user_id'),
            'monthly' => GameParticipation::where('created_at', '>=', now()->subMonth())->distinct('user_id')->count('user_id'),
        ];
    }

    private function buildSummary() : array
    {
        return [
            'active_players' => $this->activePlayers(),
            'recurring_by_seller' => $this->recurringBySeller(),
            'external_player_conversion' => $this->externalPlayerConversion(),
        ];
    }

    /**
     * Funnel jugador externo (comprador sin cuenta) → registrado (ver
     * progreso/estrategia-producto-plan.md, Fase 5). converted_user_id no
     * nulo = ya se convirtió, sea por auto-vinculación al registrarse
     * (RegisteredUserController::linkExternalPlayerHistory()) o por acción
     * manual de un admin (ExternalPlayerAdminService::convertToUser()) —
     * esta métrica no distingue el origen, solo mide el resultado.
     */
    private function externalPlayerConversion() : array
    {
        $total = ExternalPlayer::count();
        $converted = ExternalPlayer::whereNotNull('converted_user_id')->count();

        return [
            'total_external_players' => $total,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Por vendedor: total de compradores distintos vs compradores que
     * repitieron (2+ pagos aprobados). No distingue jugador con cuenta de
     * jugador externo — ambos cuentan como "cliente" del vendedor, usando
     * COALESCE(user_id, external_player_id) como identidad del comprador.
     */
    private function recurringBySeller() : array
    {
        $rows = BingoCardPayment::query()
            ->where('payment_status', 'APPROVED')
            ->select([
                'seller_user_id',
                DB::raw('COALESCE(user_id, external_player_id) AS buyer_key'),
                DB::raw('COUNT(*) AS purchase_count'),
            ])
            ->whereNotNull(DB::raw('COALESCE(user_id, external_player_id)'))
            ->groupBy('seller_user_id', DB::raw('COALESCE(user_id, external_player_id)'))
            ->get();

        $bySeller = [];
        foreach ($rows as $row) {
            $sellerId = (string) $row->seller_user_id;
            $bySeller[$sellerId] ??= ['seller_user_id' => $sellerId, 'total_buyers' => 0, 'recurring_buyers' => 0];
            $bySeller[$sellerId]['total_buyers']++;
            if ($row->purchase_count > 1) {
                $bySeller[$sellerId]['recurring_buyers']++;
            }
        }

        if (empty($bySeller)) {
            return [];
        }

        $sellerNames = \App\Models\User::whereIn('id', array_keys($bySeller))
            ->get(['id', 'first_name', 'last_name', 'username'])
            ->keyBy('id');

        foreach ($bySeller as &$row) {
            $seller = $sellerNames->get($row['seller_user_id']);
            $fullName = $seller ? trim("{$seller->first_name} {$seller->last_name}") : '';
            $row['seller_name'] = $fullName !== '' ? $fullName : ($seller->username ?? 'Desconocido');
            $row['recurring_rate'] = $row['total_buyers'] > 0
                ? round(($row['recurring_buyers'] / $row['total_buyers']) * 100, 1)
                : 0.0;
        }

        return collect($bySeller)->sortByDesc('recurring_buyers')->values()->all();
    }
}
