<?php

namespace App\DTOs\Roles\Admin\Customer;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class AdminCustomerContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $customers,
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
            'customers' => $this->customers,
            'customers_pagination' => $this->pagination,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
            'games' => $this->games,
            'timeZones' => $this->timeZones,
        ];
    }
}
