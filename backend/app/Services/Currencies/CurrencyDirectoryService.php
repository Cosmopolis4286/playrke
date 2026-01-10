<?php

namespace App\Services\Currencies;

use App\Models\Currencies\Currency;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Payments\Methods\Banks\PagoMovil;
use App\Models\Payments\Methods\Banks\PixKey;
use App\Models\Users\Roles\Seller\Seller;

class CurrencyDirectoryService
{
    public function sync(int $authUserId, array $currencyIds): void
    {
        $creator = User::getCreatorUser($authUserId, false, true);

        if (!in_array($creator['type'], ['commerce', 'admin', 'seller', 'customer'])) {
            return;
        }

        $directoryOwnerId = $this->resolveDirectoryOwnerId($creator);

        if (!$directoryOwnerId) {
            return;
        }

        $currencyModels = Currency::getCurrenciesById($currencyIds, ['id', 'iso_code']);

        $withoutDirectories = collect($currencyModels)
            ->filter(
                fn($currency) =>
                $this->hasNoDirectory($directoryOwnerId, $currency->iso_code)
            )
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($creator, $withoutDirectories) {

            $commerceId = Commerce::getValueByUserId($creator['userId'], 'id');

            $latestIds = CommerceExchangeRate::selectRaw('MAX(id) as id')
                ->where('commerce', $commerceId)
                ->groupBy('currency')
                ->pluck('id');

            CommerceExchangeRate::where('commerce', $commerceId)
                ->whereIn('id', $latestIds)
                ->where('exchange_rate', 0)
                ->whereIn('status', ['ACTIVE', 'DIRECTORY'])
                ->update(['status' => 'INACTIVE']);

            if (!empty($withoutDirectories)) {
                CommerceExchangeRate::where('commerce', $commerceId)
                    ->whereIn('id', $latestIds)
                    ->where('exchange_rate', '>', 0)
                    ->whereIn('currency', $withoutDirectories)
                    ->where('status', '!=', 'DIRECTORY')
                    ->update(['status' => 'DIRECTORY']);
            }
        });
    }

    /**
     * Decide a qué usuario se le validan los directorios según el rol
     */
    private function resolveDirectoryOwnerId(array $creator): ?int
    {
        return match ($creator['type']) {
            'commerce' => $creator['userId'],
            'admin'       => $creator['userId'],
            'seller'      => Seller::getValueByUserId($creator['userId'], 'user_id'),
            'customer'    => Customer::getValueByUserId($creator['userId'], 'user_id'),
            default       => null,
        };
    }

    /**
     * Valida existencia de directorios bancarios por moneda
     */
    private function hasNoDirectory(int $userId, string $iso): bool
    {
        return match ($iso) {
            'VES' => !PagoMovil::where('user_id', $userId)->exists(),
            'BRL' => !PixKey::where('user_id', $userId)->exists(),
            'COP' => true,
            default => throw new \InvalidArgumentException("Moneda no soportada: {$iso}")
        };
    }
}
