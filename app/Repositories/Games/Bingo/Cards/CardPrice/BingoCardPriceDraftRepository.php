<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards\CardPrice;

use App\Models\Games\Bingo\Cards\CardPrice\BingoCardPriceDraft;
use App\Repositories\Contracts\Games\Bingo\Cards\CardPrice\BingoCardPriceDraftRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Repositorio para la gestión de borradores de precios de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los borradores de precios, incluyendo manejo de JSON y consultas complejas.
 *
 * @package App\Repositories\Games\Bingo\Cards\CardPrice
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
class BingoCardPriceDraftRepository extends BaseRepository implements BingoCardPriceDraftRepositoryContract
{
    public function __construct(BingoCardPriceDraft $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene todos los borradores de una sala de bingo específica
     */
    public function getDraftsByBingoRoom(string $bingoRoomId, array $filters = []): Collection
    {
        $query = $this->model->where('bingo_room_id', $bingoRoomId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        return $query->with(['bingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene todos los borradores de una sala automática específica
     */
    public function getDraftsByAutomaticBingoRoom(string $automaticBingoRoomId, array $filters = []): Collection
    {
        $query = $this->model->where('automatic_bingo_room_id', $automaticBingoRoomId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        return $query->with(['automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene borradores por usuario creador
     */
    public function getDraftsByCreator(string $creatorUserId, array $filters = []): Collection
    {
        $query = $this->model->where('creator_user_id', $creatorUserId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['bingo_room_id'])) {
            $query->where('bingo_room_id', $filters['bingo_room_id']);
        }

        if (isset($filters['automatic_bingo_room_id'])) {
            $query->where('automatic_bingo_room_id', $filters['automatic_bingo_room_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene borradores por estado
     */
    public function getDraftsByStatus(string $status, array $filters = []): Collection
    {
        $query = $this->model->where('status', $status);

        // Aplicar filtros adicionales
        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        if (isset($filters['bingo_room_id'])) {
            $query->where('bingo_room_id', $filters['bingo_room_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Busca un borrador específico por sala y usuario
     */
    public function findDraftByRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): ?BingoCardPriceDraft
    {
        $query = $this->model->where('creator_user_id', $creatorUserId);

        if ($bingoRoomId) {
            $query->where('bingo_room_id', $bingoRoomId);
        } else {
            $query->whereNull('bingo_room_id');
        }

        if ($automaticBingoRoomId) {
            $query->where('automatic_bingo_room_id', $automaticBingoRoomId);
        } else {
            $query->whereNull('automatic_bingo_room_id');
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->first();
    }

    /**
     * Obtiene borradores que contienen una moneda específica
     */
    public function getDraftsByCurrency(string $currencyCode, array $filters = []): Collection
    {
        $query = $this->model->whereJsonContains('currencies', $currencyCode);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene borradores con precios en un rango específico para una moneda
     */
    public function getDraftsByPriceRange(string $currencyCode, float $minPrice, float $maxPrice, array $filters = []): Collection
    {
        $query = $this->model->whereJsonContains('currencies', $currencyCode)
                           ->whereRaw("JSON_EXTRACT(card_prices, '$.{$currencyCode}') BETWEEN ? AND ?", [$minPrice, $maxPrice]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Actualiza el estado de un borrador
     */
    public function updateDraftStatus(string $draftId, string $newStatus): bool
    {
        return $this->model->where('id', $draftId)
                          ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Actualiza múltiples borradores a estado ejecutado
     */
    public function markDraftsAsExecuted(array $draftIds): int
    {
        return $this->model->whereIn('id', $draftIds)
                          ->update(['status' => 'EXECUTED']);
    }

    /**
     * Crea o actualiza un borrador (upsert)
     */
    public function createOrUpdateDraft(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId, array $currencies, array $cardPrices): BingoCardPriceDraft
    {
        return $this->model->updateOrCreate(
            [
                'bingo_room_id' => $bingoRoomId,
                'automatic_bingo_room_id' => $automaticBingoRoomId,
                'creator_user_id' => $creatorUserId,
            ],
            [
                'currencies' => $currencies,
                'card_prices' => $cardPrices,
                'status' => 'ACTIVE',
            ]
        );
    }

    /**
     * Elimina borradores ejecutados antiguos
     */
    public function deleteOldExecutedDrafts(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('status', 'EXECUTED')
                          ->where('updated_at', '<', $cutoffDate)
                          ->delete();
    }

    /**
     * Obtiene estadísticas de borradores por usuario
     */
    public function getDraftStatsByCreator(string $creatorUserId): array
    {
        $stats = $this->model->where('creator_user_id', $creatorUserId)
                            ->selectRaw('
                                COUNT(*) as total_drafts,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_drafts,
                                SUM(CASE WHEN status = "EXECUTED" THEN 1 ELSE 0 END) as executed_drafts
                            ')
                            ->first();

        return [
            'total_drafts' => $stats->total_drafts ?? 0,
            'active_drafts' => $stats->active_drafts ?? 0,
            'executed_drafts' => $stats->executed_drafts ?? 0,
        ];
    }

    /**
     * Obtiene estadísticas generales de borradores
     */
    public function getGeneralDraftStats(): array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_drafts,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_drafts,
                                SUM(CASE WHEN status = "EXECUTED" THEN 1 ELSE 0 END) as executed_drafts,
                                COUNT(DISTINCT creator_user_id) as total_creators
                            ')
                            ->first();

        return [
            'total_drafts' => $stats->total_drafts ?? 0,
            'active_drafts' => $stats->active_drafts ?? 0,
            'executed_drafts' => $stats->executed_drafts ?? 0,
            'total_creators' => $stats->total_creators ?? 0,
        ];
    }

    /**
     * Obtiene borradores activos más recientes
     */
    public function getRecentActiveDrafts(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->where('status', 'ACTIVE');

        // Aplicar filtros
        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        if (isset($filters['bingo_room_id'])) {
            $query->where('bingo_room_id', $filters['bingo_room_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Cuenta borradores por estado
     */
    public function countDraftsByStatus(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        if (isset($filters['bingo_room_id'])) {
            $query->where('bingo_room_id', $filters['bingo_room_id']);
        }

        $counts = $query->select('status', DB::raw('count(*) as total'))
                       ->groupBy('status')
                       ->pluck('total', 'status')
                       ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['ACTIVE', 'EXECUTED'];
        foreach ($validStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Obtiene borradores creados en un rango de fechas
     */
    public function getDraftsByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        return $query->with(['bingoRoom', 'automaticBingoRoom', 'creatorUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene todas las monedas utilizadas en los borradores
     */
    public function getAllCurrenciesUsedInDrafts(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        $currencies = $query->pluck('currencies')
                           ->flatten()
                           ->unique()
                           ->values()
                           ->toArray();

        return $currencies;
    }

    /**
     * Verifica si existe un borrador activo para una sala y usuario
     */
    public function hasActiveDraftForRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): bool
    {
        $query = $this->model->where('creator_user_id', $creatorUserId)
                            ->where('status', 'ACTIVE');

        if ($bingoRoomId) {
            $query->where('bingo_room_id', $bingoRoomId);
        } else {
            $query->whereNull('bingo_room_id');
        }

        if ($automaticBingoRoomId) {
            $query->where('automatic_bingo_room_id', $automaticBingoRoomId);
        } else {
            $query->whereNull('automatic_bingo_room_id');
        }

        return $query->exists();
    }

    /**
     * Obtiene el precio de una moneda específica de un borrador
     */
    public function getPriceFromDraft(string $draftId, string $currencyCode): ?float
    {
        $draft = $this->model->find($draftId);

        if (!$draft || !$draft->card_prices) {
            return null;
        }

        $prices = is_string($draft->card_prices) ? json_decode($draft->card_prices, true) : $draft->card_prices;

        return isset($prices[$currencyCode]) ? floatval($prices[$currencyCode]) : null;
    }

    /**
     * Actualiza el precio de una moneda específica en un borrador
     */
    public function updatePriceInDraft(string $draftId, string $currencyCode, float $newPrice): bool
    {
        $draft = $this->model->find($draftId);

        if (!$draft) {
            return false;
        }

        $prices = is_string($draft->card_prices) ? json_decode($draft->card_prices, true) : $draft->card_prices;
        $currencies = is_string($draft->currencies) ? json_decode($draft->currencies, true) : $draft->currencies;

        // Actualizar precio
        $prices[$currencyCode] = $newPrice;

        // Agregar moneda si no existe
        if (!in_array($currencyCode, $currencies)) {
            $currencies[] = $currencyCode;
        }

        return $draft->update([
            'currencies' => $currencies,
            'card_prices' => $prices,
        ]);
    }
}