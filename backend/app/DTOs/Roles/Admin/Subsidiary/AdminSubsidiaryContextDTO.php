<?php

namespace App\DTOs\Roles\Admin\Subsidiary;

use App\DTOs\ContextDTO;
use Illuminate\Support\Collection;

final class AdminSubsidiaryContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array $subsidiaries,
        public readonly ?array $pagination,
        public readonly Collection $commerces,
        public readonly Collection $countries,
        public readonly Collection $currencies,
    ) {
    }

    public function toView() : array
    {
        return [
            'subsidiaries' => $this->subsidiaries,
            'subsidiaries_pagination' => $this->pagination,
            'commerces' => $this->commerces,
            'countries' => $this->countries,
            'currencies' => $this->currencies,
        ];
    }
}
