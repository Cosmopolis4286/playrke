<?php

namespace App\DTOs\Roles\Admin\Customer;

use App\DTOs\Roles\Admin\AdminContextDTO;

final class AdminCustomerContextDTO implements AdminContextDTO
{
    public function __construct(
        public readonly object $commerces,
        public readonly object $countries,
        public readonly object $currencies,
        public readonly object $games,
        public readonly object $timeZones
    ) {}

    public function toView(): array
    {
        return [
            'commerces' => $this->commerces,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
            'games' => $this->games,
            'timeZones' => $this->timeZones
        ];
    }
}
