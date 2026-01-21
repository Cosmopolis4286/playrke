<?php

namespace App\DTOs\Roles\Admin\Commerce;

use App\DTOs\ContextDTO;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminCommerceContextDTO implements ContextDTO
{
    /**
     * @param LengthAwarePaginator|Collection $commerces Paginación o colección de comercios
     * @param Collection $countries Colección de países
     * @param Collection $currencies Colección de monedas
     * @param Collection $games Colección de juegos
     * @param Collection $timeZones Colección de zonas horarias
     */
    public function __construct(
        public readonly LengthAwarePaginator|Collection $commerces,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
    ) {}

    public function toView(): array
    {
        return [
            'commerces' => $this->commerces,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
            'games' => $this->games,
            'timeZones' => $this->timeZones,
        ];
    }
}
