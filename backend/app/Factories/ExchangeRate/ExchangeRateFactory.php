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
     * @param string $exchangeRateId
     * @return ExchangeRateDTO
     *
     * @throws InvalidArgumentException Si falta moneda o amount (valor de la tasa de cambio).
     */
    public function fromRequest(array $data, ?string $exchangeRateId = null): ExchangeRateDTO
    {
        if (empty($data['currency'])) {
            throw new InvalidArgumentException('El campo "currency" es obligatorio.');
        }

        if (!isset($data['amount'])) {
            throw new InvalidArgumentException('El campo "amount" es obligatorio.');
        }

        $currencyId = $this->currencyRepository->getValueByISOCode($data['currency'], 'id');

        if ($currencyId === null) {
            throw new InvalidArgumentException('Moneda no encontrada para el código ISO proporcionado.');
        }

        return new ExchangeRateDTO(
            $data['id'] ?? $exchangeRateId,
            $currencyId,
            (float) $data['amount'],
            $data['status'] ?? 'ACTIVE',
            $data['tokenId'] ?? null,
        );
    }
}
