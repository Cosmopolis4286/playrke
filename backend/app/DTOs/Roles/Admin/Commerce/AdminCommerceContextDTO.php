<?php

namespace App\DTOs\Roles\Admin\Commerce;

use App\DTOs\ContextDTO;

use Illuminate\Support\Collection;

final class AdminCommerceContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $commerces,
        public readonly ?array $pagination,
        public readonly Collection $countries,
        public readonly Collection $currencies,
        public readonly Collection $games,
        public readonly Collection $timeZones,
        public readonly ?string $defaultCommerceId = null,
    ) {}

    public function toView(): array
    {
        return [
            'commerces'             => $this->commerces,
            'commerces_pagination'  => $this->pagination,
            'countries'             => $this->countries,
            'currencies'            => $this->currencies,
            'games'                 => $this->games,
            'timeZones'             => $this->timeZones,
            'default_commerce_id'   => $this->defaultCommerceId,
        ];
    }
}
