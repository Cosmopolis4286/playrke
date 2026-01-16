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

    public function create(ExchangeRateDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $this->repo($dto->creatorType)
                ->createFromDto($dto);
        });
    }

    public function update(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        DB::transaction(function () use ($dto) {
            $this->repo($dto->creatorType)
                ->updateFromDto($dto);
        });
    }

    public function delete(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        $this->repo($dto->creatorType)
            ->delete($dto->id);
    }

    public function toggleStatus(ExchangeRateDTO $dto): void
    {
        throw_if(!$dto->id, DomainException::class);

        $this->repo($dto->creatorType)
            ->toggleStatus($dto->id);
    }

    private function repo(string $type)
    {
        return match ($type) {
            'commerce' => $this->commerce,
            'customer' => $this->customer,
            default => throw new DomainException('Tipo inválido'),
        };
    }
}
