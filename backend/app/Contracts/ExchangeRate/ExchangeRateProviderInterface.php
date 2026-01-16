<?php

namespace App\Contracts\ExchangeRate;

use App\DTOs\ExchangeRate\ExchangeRateDTO;

interface ExchangeRateProviderInterface
{
    /**
     * Indica si el provider soporta el tipo solicitado.
     */
    public function supports(string $type): bool;

    /**
     * Obtiene las tasas de cambio crudas desde la fuente.
     *
     * @return array<int, ExchangeRateDTO>
     */
    public function fetch(): array;
}
