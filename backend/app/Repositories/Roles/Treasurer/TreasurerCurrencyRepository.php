<?php

namespace App\Repositories\Roles\Treasurer;

use App\Contracts\Roles\Treasurer\TreasurerCurrencyRepositoryInterface;
use App\Models\Pivots\Roles\Treasurer\TreasurerCurrency;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de países y monedas de tesoreros
 */
class TreasurerCurrencyRepository extends BaseRepository implements TreasurerCurrencyRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param TreasurerCurrency $model
     */
    public function __construct(TreasurerCurrency $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene monedas disponibles para un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array
     */
    public function getAvailableCurrenciesIds($treasurerId): array
    {
        return $this->model->select('currency_id')
            ->where('treasurer_id', $treasurerId)
            ->where('status', 'ACTIVE')
            ->distinct()->pluck('currency_id')->toArray();
    }

    /**
     * Obtiene currency_id con su level mínimo para un tesorero (solo ACTIVE).
     * Retorna array indexado: currency_id => level.
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array<string, int>
     */
    public function getAvailableCurrenciesWithLevel($treasurerId): array
    {
        return $this->model
            ->select('currency_id', \Illuminate\Support\Facades\DB::raw('MIN(level) as level'))
            ->where('treasurer_id', $treasurerId)
            ->where('status', 'ACTIVE')
            ->groupBy('currency_id')
            ->get()
            ->pluck('level', 'currency_id')
            ->map(fn($level) => (int) $level)
            ->all();
    }

    /**
     * Obtiene los códigos ISO de las monedas disponibles para un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return array Lista de códigos ISO
     */
    public function getAvailableCurrenciesIsoCodes($treasurerId): array
    {
        return $this->model
            ->join('currencies', 'currencies.id', '=', 'treasurers_currencies.currency_id')
            ->where('treasurers_currencies.treasurer_id', $treasurerId)
            ->where('treasurers_currencies.status', 'ACTIVE')
            ->distinct()
            ->pluck('currencies.iso_code')
            ->toArray();
    }

    /**
     * Obtiene registros por tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return Collection
     */
    public function getByTreasurer($treasurerId): Collection
    {
        return $this->model->where('treasurer_id', $treasurerId)->get();
    }

    /**
     * Obtiene registros por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)->get();
    }

    /**
     * Obtiene registros por nivel
     *
     * @param int $level Nivel de asignación
     * @return Collection
     */
    public function getByLevel(int $level): Collection
    {
        return $this->model->where('level', $level)->get();
    }

    /**
     * Obtiene registros activos
     *
     * @return Collection
     */
    public function getActiveRecords(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene registros inactivos
     *
     * @return Collection
     */
    public function getInactiveRecords(): Collection
    {
        return $this->model->where('status', 'INACTIVE')->get();
    }

    /**
     * Actualiza estado de un registro
     *
     * @param mixed $id ID del registro
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Actualiza nivel de un registro
     *
     * @param mixed $id ID del registro
     * @param int $level Nuevo nivel
     * @return bool
     */
    public function updateLevel($id, int $level): bool
    {
        return $this->model->where('id', $id)
            ->update(['level' => $level]) > 0;
    }

    /**
     * Activa registros de un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return int Número de registros actualizados
     */
    public function activateByTreasurer($treasurerId): int
    {
        return $this->model->where('treasurer_id', $treasurerId)
            ->update(['status' => 'ACTIVE']);
    }

    /**
     * Desactiva registros de un tesorero
     *
     * @param mixed $treasurerId ID del tesorero
     * @return int Número de registros actualizados
     */
    public function deactivateByTreasurer($treasurerId): int
    {
        return $this->model->where('treasurer_id', $treasurerId)
            ->update(['status' => 'INACTIVE']);
    }
}
