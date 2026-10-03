<?php

namespace App\Repositories\Roles\Admin;

use App\Contracts\Roles\Admin\AdminExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use App\Models\ExchangeRate;
use App\Repositories\ExchangeRate\RoleExchangeRateRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Repository para manejar operaciones de tasas de cambio de administradores
 */
class AdminExchangeRateRepository extends RoleExchangeRateRepository implements AdminExchangeRateRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param ExchangeRate $model
     */
    public function __construct(ExchangeRate $model)
    {
        $this->model = $model;
    }

    /**
     * Implementación específica para admin: no usa foreign key de rol
     */
    public function createOrRestoreByCurrency(int $roleEntityId, ExchangeRateDTO $dto): Model
    {
        $data = [
            'currency_id' => $dto->currencyId,
            'amount'      => $dto->amount, // Use amount field as per migration
        ];

        $model = $this->model
            ->withTrashed()
            ->where('currency_id', $dto->currencyId)
            ->first();

        if ($model) {
            $model->trashed() && $model->restore();
            $model->update($data);
            return $model;
        }

        return $this->model->create($data);
    }

    /**
     * Obtiene todas las tasas de cambio
     *
     * @return Collection
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->model->with('currency:id,iso_code,name,description,type,status,country_id')
            ->with('currency.country:id,name,iso_code_2,iso_code_3')
            ->orderBy('currency_id')
            ->get();
    }

    /**
     * Obtiene tasas de cambio por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getExchangeRatesByCurrency($currencyId): Collection
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->get(['currency_id', 'amount', 'updated_at']);
    }

    /**
     * Obtiene valor específico de tasa por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getCurrencyValue($currencyId, string $field = 'amount')
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentRatesBatch(array $currencyIds): array
    {
        if (empty($currencyIds)) {
            return [];
        }

        // Una sola query trae TODAS las filas candidatas (ya ordenadas por
        // updated_at desc); nos quedamos con la primera ocurrencia por
        // currency_id en PHP — equivalente a "la más reciente por moneda"
        // sin necesitar una subquery de agregación.
        $rows = $this->model
            ->whereIn('currency_id', $currencyIds)
            ->orderByDesc('updated_at')
            ->get(['currency_id', 'amount']);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) $row->currency_id;
            if (!isset($result[$key])) {
                $result[$key] = (float) $row->amount;
            }
        }

        return $result;
    }

    /**
     * Crea o versiona una tasa de cambio: marca la fila ACTIVE vigente (si
     * existe) como OLD y crea una fila ACTIVE nueva — mismo criterio de
     * versionado que ExchangeRateCrudService::update() en el flujo manual,
     * para que el histórico/gráfico de tendencia (Historical.vue,
     * ExchangeRateSummaryService::buildHistory()) refleje también las
     * actualizaciones automáticas del cronjob, no solo las manuales.
     *
     * Antes hacía updateOrCreate(['currency_id' => $currencyId], $data) sin
     * filtrar por status: con varias filas OLD + 1 ACTIVE para la misma
     * moneda (el caso normal tras cualquier edición), Eloquent tomaba la
     * PRIMERA fila que matcheara currency_id — típicamente la más antigua,
     * ya histórica — y la sobreescribía in-place. La tasa realmente vigente
     * nunca cambiaba pese a que el método devolvía true.
     *
     * $userId por parámetro, no Auth::id() (ver comentario de la interfaz)
     * — este método lo invoca ExchangeRateUpdaterService tanto desde el
     * cronjob automático (sin sesión HTTP, Auth::id() era null) como desde
     * el flujo manual, y en ambos casos ya recibe el $userId real que debe
     * usar.
     *
     * @param mixed $currencyId ID de la moneda
     * @param float $amount Valor de la tasa
     * @param float $percentage Porcentaje
     * @param int $userId Usuario real que originó la actualización
     * @param string $source ExchangeRate::SOURCE_USER o SOURCE_SYSTEM
     * @return bool
     */
    public function updateOrCreateExchangeRate($currencyId, float $amount, float $percentage, int $userId, string $source = 'user'): bool
    {
        try {
            DB::transaction(function () use ($currencyId, $amount, $percentage, $userId, $source) {
                $current = $this->model
                    ->where('currency_id', $currencyId)
                    ->where('status', 'ACTIVE')
                    ->first();

                // No-op si el valor no cambió — evita una fila OLD nueva
                // por cada tick del cronjob cuando el proveedor externo
                // devuelve el mismo valor que ya está vigente (caso normal:
                // el BCV/mercado no cambia cada hora).
                if ($current && (float) $current->amount === $amount) {
                    $current->update(['user_id' => $userId, 'source' => $source]);
                    return;
                }

                $current?->update(['status' => 'OLD']);

                $this->model->create([
                    'currency_id' => $currencyId,
                    'amount'      => $amount,
                    'percentage'  => $percentage,
                    'user_id'     => $userId,
                    'source'      => $source,
                    'status'      => 'ACTIVE',
                ]);
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene la tasa más reciente por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return ExchangeRate|null
     */
    public function getLatestRate($currencyId): ?ExchangeRate
    {
        return $this->model->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Obtiene el historial completo de tasas por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getHistoryByCurrency($currencyId): Collection
    {
        // user_name: mismo criterio que displayName() (username si existe,
        // si no nombre completo) — antes construía el nombre directo con
        // first_name+last_name sin username, inconsistente con
        // getAvailableExchangeRates(). Mostraba "Play RKE" (nombre real del
        // usuario admin@playrke.com) en el histórico mientras el listado
        // principal mostraba "admin" (su username) para la misma fila.
        return $this->model
            ->with(['currency', 'user:id,username,first_name,last_name'])
            ->where('currency_id', $currencyId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (ExchangeRate $rate) {
                $rate->currency_name = $rate->currency?->name ?? '-';
                $rate->user_name = $this->displayName($rate);
                return $rate;
            });
    }

    /**
     * Obtiene las tasas de cambio disponibles para administradores
     *
     * @return Collection
     */
    public function getAvailableExchangeRates(): Collection
    {
        // with('user'): Index.vue (ExchangeRateTable) muestra "Registrado
        // por" / origen (manual vs. automático, ver ExchangeRate::source) —
        // antes esta query no cargaba la relación, forzando un N+1 lazy
        // load por fila apenas el frontend intentaba mostrar ese dato.
        return $this->model
            ->with('user:id,username,first_name,last_name')
            ->where('status', '<>', 'OLD')
            ->get()
            ->map(function (ExchangeRate $rate) {
                $rate->user_name = $this->displayName($rate);
                return $rate;
            });
    }

    /**
     * Nombre a mostrar de quién registró/actualizó la tasa — mismo criterio
     * que ExchangeRateSummaryService::displayName() (username si existe, si
     * no nombre completo), duplicado acá porque ese servicio vive en el
     * namespace de dashboards de SuperAdmin y esta clase no debería
     * depender de él solo por este helper.
     */
    private function displayName(ExchangeRate $rate): string
    {
        if (!$rate->user) {
            return '—';
        }

        if ($rate->user->username) {
            return $rate->user->username;
        }

        $fullName = trim("{$rate->user->first_name} {$rate->user->last_name}");

        return $fullName !== '' ? $fullName : '—';
    }

    /**
     * Obtiene los códigos ISO de las monedas disponibles
     *
     * @return array Lista de códigos ISO
     */
    public function getAvailableCurrenciesIsoCodes(): array
    {
        return $this->model
            ->join('currencies', 'currencies.id', '=', 'exchange_rates.currency_id')
            ->where('currencies.status', 'ACTIVE')
            ->distinct()
            ->pluck('currencies.iso_code')
            ->toArray();
    }
}
