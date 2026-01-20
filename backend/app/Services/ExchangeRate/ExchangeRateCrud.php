<?php

namespace App\Services\ExchangeRate;

use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerExchangeRateRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;
use DomainException;
use Illuminate\Support\Facades\DB;

class ExchangeRateCrud
{
    public function __construct(
        protected CommerceExchangeRateRepositoryInterface $commerce,
        protected CustomerExchangeRateRepositoryInterface $customer,
    ) {}

    public function create(ExchangeRateDTO $dto, string $context): void
    {
        DB::transaction(function () use ($dto, $context) {
            $this->repo($context)->createFromDto($dto);
        });
    }

    public function update(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        DB::transaction(function () use ($dto) {
            $this->repo($context)->updateFromDto($dto);
        });
    }

    public function delete(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        $this->repo($context)->delete($dto->id);
    }

    public function toggleStatus(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        $this->repo($context)->toggleStatus($dto->id);
    }

    private function repo(string $context)
    {
        return match ($context) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default => throw new DomainException("Rol/Contexto inválido: {$context}"),
        };
    }
}
