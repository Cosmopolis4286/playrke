<?php

namespace App\Services\ExchangeRate;
/**
 * Servicio de dominio para gestión de tasas de cambio.
 *
 * Responsabilidades:
 * - Orquestar creación, actualización y eliminación
 * - Delegar persistencia a repositorios
 * - Garantizar transacciones atómicas
 *
 * NO contiene lógica HTTP ni decoding de payloads.
 */
class ExchangeRateService
{
    public function __construct(
        protected ExchangeRateContext $context,
        protected ExchangeRateCrudService $crud,
        protected ExchangeRateUpdater $updater,
    ) {}
}
