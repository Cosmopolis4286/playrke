<?php

namespace App\DTOs\Roles\Admin\Player;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class AdminPlayerContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $players,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $timeZones,
    ) {
    }

    public function toView() : array
    {
        return [
            'players' => $this->players,
            'players_pagination' => $this->pagination,
            'countries' => $this->countries,
            'timeZones' => $this->timeZones,
        ];
    }
}
