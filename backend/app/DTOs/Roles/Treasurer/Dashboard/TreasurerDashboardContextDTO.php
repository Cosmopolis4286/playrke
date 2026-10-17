<?php

namespace App\DTOs\Roles\Treasurer\Dashboard;

use App\DTOs\ContextDTO;

final class TreasurerDashboardContextDTO implements ContextDTO
{
    public function __construct(
        /** Nombre del usuario autenticado */
        public readonly string $userName,
        /** Stats principales: movimientos, deuda de comercios, tasas, premios */
        public readonly array $stats,
        /** Tasas oficiales activas (mismo formato que /superadmin/exchange-rate-summary) */
        public readonly array $exchangeRates,
        /** Deuda pendiente de comercios con la plataforma, por deudor y moneda (top 5) */
        public readonly array $receivables,
        /** Período activo (e.g. "Octubre 2026") */
        public readonly string $period,
    ) {}

    public function toView(): array
    {
        return [
            'userName'      => $this->userName,
            'stats'         => $this->stats,
            'exchangeRates' => $this->exchangeRates,
            'receivables'   => $this->receivables,
            'period'        => $this->period,
        ];
    }
}
