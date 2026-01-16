<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ExchangeRateUpdater
{

    public function __construct(
        protected ExchangeRateRepositoryInterface $exchange
    ) {}

    public function updateRates(?int $userId = null, bool $checkUpdate = false): bool
    {
        DB::transaction(function () use ($userId, $checkUpdate) {

            $creator = $userId
                ? User::getCreatorUser($userId, false, true)
                : null;

            if (!$creator) {
                // $this->persist($dto, $checkUpdate);
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

    private function updateByCreator(
        string $type,
        int|string $creatorId,
        bool $checkUpdate
    ): void {

            // if ((string) $dto->creatorId !== (string) $creatorId) {
            //     continue;
            // }

            // $this->persist($dto, $checkUpdate);

    }

    private function persist($dto, bool $checkUpdate): void
    {
        // 👉 delega al Crud / Repository
        // $this->crud->upsertFromDto($dto, $checkUpdate);
    }
}
