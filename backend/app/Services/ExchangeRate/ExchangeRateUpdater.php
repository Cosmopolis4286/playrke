<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use DomainException;

final class ExchangeRateUpdater
{
    /**
     * @param iterable<ExchangeRateProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers
    ) {}

    public function updateRates(?int $userId = null, bool $checkUpdate = false): bool
    {
        DB::transaction(function () use ($userId, $checkUpdate) {

            $creator = $userId
                ? User::getCreatorUser($userId, false, true)
                : null;

            if (!$creator) {
                $this->updateAllProviders($checkUpdate);
                return;
            }

            $this->updateByCreator(
                $creator['type'],
                $creator['id'],
                $checkUpdate
            );
        });

        return true;
    }

    private function updateAllProviders(bool $checkUpdate): void
    {
        foreach ($this->providers as $provider) {
            foreach ($provider->fetch() as $dto) {
                $this->persist($dto, $checkUpdate);
            }
        }
    }

    private function updateByCreator(
        string $type,
        int|string $creatorId,
        bool $checkUpdate
    ): void {
        $provider = $this->resolveProvider($type);

        foreach ($provider->fetch() as $dto) {
            if ((string) $dto->creatorId !== (string) $creatorId) {
                continue;
            }

            $this->persist($dto, $checkUpdate);
        }
    }

    private function resolveProvider(string $type): ExchangeRateProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }

        throw new DomainException("No existe provider para el tipo [$type]");
    }

    private function persist($dto, bool $checkUpdate): void
    {
        // 👉 delega al Crud / Repository
        // $this->crud->upsertFromDto($dto, $checkUpdate);
    }
}
