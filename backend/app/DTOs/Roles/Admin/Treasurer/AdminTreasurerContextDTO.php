<?php

namespace App\DTOs\Roles\Admin\Treasurer;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class AdminTreasurerContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $treasurers,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
    ) {
    }

    public function toView() : array
    {
        return [
            'treasurers' => $this->treasurers,
            'treasurers_pagination' => $this->pagination,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
            'games' => $this->games,
            'timeZones' => $this->timeZones,
        ];
    }
}
