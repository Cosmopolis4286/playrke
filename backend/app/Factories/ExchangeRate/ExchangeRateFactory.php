<?php

namespace App\Factories\ExchangeRate;

use App\Contracts\CurrencyRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use InvalidArgumentException;

final class ExchangeRateFactory
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepository
    ) {}

    /**
     * Crea un ExchangeRateDTO a partir de un arreglo de datos (request).
     *
     * @param array<string, mixed> $data Datos de entrada, típicamente request validated.
     * @return ExchangeRateDTO
     *
     * @throws InvalidArgumentException Si falta moneda o exchange_rate.
     */
    public function fromRequest(array $data): ExchangeRateDTO
    {
        if (empty($data['currency'])) {
            throw new InvalidArgumentException('El campo "currency" es obligatorio.');
        }

        if (!isset($data['exchange_rate'])) {
            throw new InvalidArgumentException('El campo "exchange_rate" es obligatorio.');
        }

        $currencyId = $this->currencyRepository->getValueByISOCode($data['currency'], 'id');

        if ($currencyId === null) {
            throw new InvalidArgumentException('Moneda no encontrada para el código ISO proporcionado.');
        }

        return new ExchangeRateDTO(
            $data['id'] ?? null,
            $currencyId,
            (float) $data['exchange_rate'],
            $data['percentage'] ?? 0.0,
            $data['status'] ?? 'ACTIVE',
            $data['tokenId'] ?? null,
        );
    }
}
