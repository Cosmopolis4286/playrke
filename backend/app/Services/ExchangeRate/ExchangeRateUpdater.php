<?php

namespace App\Services\ExchangeRate;

use App\Contracts\ExchangeRateProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExchangeRateUpdater
{
    public function __construct(
        protected ExchangeRateProviderInterface $provider
    ) {}

    public function updateRates(?int $userId = null, bool $checkUpdate = false): bool
    {
        $data = $this->provider->fetch();

        DB::transaction(function () use ($userId, $data, $checkUpdate) {

            $creator = $userId
                ? User::getCreatorUser($userId, false, true)
                : null;

            if (!$creator) {
                $this->updateAll('commerce', $data, $checkUpdate);
                $this->updateAll('customer', $data, $checkUpdate);
                return;
            }

            $this->updateByIds(
                $creator['type'],
                [$creator['id']],
                $data,
                $checkUpdate
            );
        });

        return true;
    }
}
