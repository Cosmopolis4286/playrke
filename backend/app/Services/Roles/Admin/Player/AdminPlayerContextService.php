<?php

namespace App\Services\Roles\Admin\Player;

use App\DTOs\Roles\Admin\Player\AdminPlayerContextDTO;
use App\Models\User;
use App\Traits\HashedTableDataTrait;
use Illuminate\Support\LazyCollection;

final class AdminPlayerContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
    ) {
    }

    /**
     * Query base con filtros aplicados — compartida entre resolvePlayers()
     * (paginado, para la tabla) y exportForCsv() (cursor completo, para
     * CSV). Mismo patrón que AdminUserContextService::buildFilteredQuery().
     */
    public function buildFilteredQuery(array $filters = []) : \Illuminate\Database\Eloquent\Builder
    {
        $query = User::withWhereHas('roles', fn ($q) => $q->where('name', 'player'))
            ->with('country:id,name');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function($q) use ($term) {
                $q->where('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('username', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%")
                    ->orWhere('phone_number', 'LIKE', "%{$term}%");
            });
        }

        if (! empty($filters['country_name'])) {
            $countryName = $filters['country_name'];
            $query->whereHas('country', fn ($q) => $q->where('name', $countryName));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Cursor sobre TODOS los jugadores que coincidan con los filtros (sin
     * paginar) — mismo patrón que AdminUserService::exportForCsv(), evita
     * materializar el catálogo completo en memoria como haría ->get().
     *
     * @return LazyCollection<int, User>
     */
    public function exportForCsv(array $filters = []) : LazyCollection
    {
        return $this->buildFilteredQuery($filters)->cursor();
    }

    public function playerContext() : AdminPlayerContextDTO
    {
        $data = $this->resolvePlayers();

        return new AdminPlayerContextDTO(
            players: $data['players'],
            pagination: $data['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
        );
    }

    /**
     * $filters acepta: search (nombre/username/email/teléfono), country_name,
     * status. Antes se ignoraban por completo — la tabla del frontend
     * "filtraba" solo la página de 15 registros ya cargada, dando resultados
     * incompletos/engañosos frente al total real del sistema (mismo problema
     * que tenía AdminUserContextService antes de corregirse).
     */
    public function resolvePlayers(array $filters = [], int $page = 1, int $perPage = 15) : array
    {
        $labels = ['Nombre', 'Username', 'Email', 'País', 'Teléfono', 'Status'];
        $rowKeys = ['full_name', 'username', 'email', 'country.name', 'phone_number', 'status_badge'];

        // Eloquent para que hashCollectionIds() pueda llamar getKey()
        $source = $this->buildFilteredQuery($filters)->paginate($perPage, ['*'], 'page', $page);

        $collection = $source->getCollection()->map(function(User $user) {
            $user->full_name = trim("{$user->first_name} {$user->last_name}");
            $user->status_badge = $this->statusBadge($user->status ?? '');

            return $user;
        });

        $pagination = [
            'total' => $source->total(),
            'per_page' => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page' => $source->lastPage(),
            'from' => $source->firstItem(),
            'to' => $source->lastItem(),
        ];

        $players = $this->processCollectionTableData($collection, $labels, $rowKeys);

        // real_id: necesario para el botón "Ver ficha" (reusa
        // AdminUserController::show(), que resuelve por PK real, no por el
        // hash de fila que usa este listado) — mismo motivo que
        // AdminUserContextService::resolveUsers() expone real_id.
        $playersList = $collection->values();
        $players['rows'] = collect($players['rows'])->map(function(array $row, int $index) use ($playersList) {
            $row['real_id'] = $playersList->get($index)?->id;

            return $row;
        })->all();

        return ['players' => $players, 'pagination' => $pagination];
    }

    private function statusBadge(string $status) : string
    {
        return match ($status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'DELETED' => '<span class="badge badge-dark">Eliminado</span>',
            'SUSPENDED' => '<span class="badge badge-danger">Suspendido</span>',
            'PENDING_VERIFICATION' => '<span class="badge badge-warning">Pendiente</span>',
            default => '<span class="badge badge-warning">Desconocido</span>',
        };
    }
}
