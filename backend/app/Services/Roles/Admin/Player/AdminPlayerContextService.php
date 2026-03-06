<?php

namespace App\Services\Roles\Admin\Player;

use App\DTOs\Roles\Admin\Player\AdminPlayerContextDTO;
use App\Models\User;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminPlayerContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\CountryRepositoryInterface  $countries,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
    ) {}

    public function playerContext(): AdminPlayerContextDTO
    {
        $data = $this->resolvePlayers();

        return new AdminPlayerContextDTO(
            players:    $data['players'],
            pagination: $data['pagination'],
            countries:  $this->countries->getActive(['id', 'name', 'iso_code_2']),
            timeZones:  $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
        );
    }

    public function resolvePlayers(): array
    {
        $labels  = ['Nombre', 'Username', 'Email', 'País', 'Teléfono', 'Status'];
        $rowKeys = ['full_name', 'username', 'email', 'country.name', 'phone_number', 'status_badge'];

        // Eloquent para que hashCollectionIds() pueda llamar getKey()
        $source = User::withWhereHas('roles', fn($q) => $q->where('name', 'player'))
            ->with('country:id,name')
            ->paginate(15);

        $collection = $source->getCollection()->map(function (User $user) {
            $user->full_name    = trim("{$user->first_name} {$user->last_name}");
            $user->status_badge = $this->statusBadge($user->status ?? '');
            return $user;
        });

        $pagination = [
            'total'        => $source->total(),
            'per_page'     => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page'    => $source->lastPage(),
            'from'         => $source->firstItem(),
            'to'           => $source->lastItem(),
        ];

        $players = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['players' => $players, 'pagination' => $pagination];
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'ACTIVE'   => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'DELETED'  => '<span class="badge badge-dark">Eliminado</span>',
            default    => '<span class="badge badge-warning">Desconocido</span>',
        };
    }
}
