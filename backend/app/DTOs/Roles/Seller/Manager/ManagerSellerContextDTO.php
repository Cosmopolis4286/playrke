<?php

namespace App\DTOs\Roles\Seller\Manager;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class ManagerSellerContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $sellers,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
        public readonly string $roleUser,
    ) {
    }

    public function toView() : array
    {
        return [
            'sellers' => $this->sellers,
            'sellers_pagination' => $this->pagination,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
            'games' => $this->games,
            'timeZones' => $this->timeZones,
            'roleUser' => $this->roleUser,
        ];
    }
}
