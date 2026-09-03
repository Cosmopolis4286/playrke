<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Services;

use App\Domains\Marketplace\Crypto\Contracts\CryptoGateway;
use App\Domains\Marketplace\Crypto\Models\DepositAddress;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

/**
 * Asigna y resuelve direcciones de depósito por usuario+moneda+red.
 * Idempotente: un usuario nunca recibe dos direcciones activas para la
 * misma moneda+red — ver unique(user_id, currency_id, network) en la
 * migración. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 10-11.
 */
final class DepositAddressService
{
    public function __construct(
        private readonly CryptoGateway $gateway,
    ) {
    }

    /**
     * Devuelve la dirección activa del usuario para esta moneda, o crea una
     * nueva vía CryptoGateway::deriveAddress() si no existe todavía.
     */
    public function getOrCreate(string $userId, string $currencyId): DepositAddress
    {
        $existing = DepositAddress::where('user_id', $userId)
            ->where('currency_id', $currencyId)
            ->where('network', $this->gateway->network())
            ->active()
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($userId, $currencyId) {
            // Re-verificar bajo transacción: dos requests concurrentes del
            // mismo usuario no deben generar dos direcciones — el unique
            // constraint de la migración es la defensa real; este segundo
            // SELECT solo evita gastar un índice de derivación de más en el
            // caso común.
            $existing = DepositAddress::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->where('network', $this->gateway->network())
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $derived = $this->gateway->deriveAddress($userId);

            return DepositAddress::create([
                'user_id'          => $userId,
                'currency_id'      => $currencyId,
                'network'          => $this->gateway->network(),
                'derivation_index' => $derived['derivation_index'],
                'address'          => $derived['address'],
                'status'           => DepositAddress::STATUS_ACTIVE,
            ]);
        });
    }

    /**
     * Resuelve la moneda por iso_code y delega en getOrCreate() — helper
     * para controllers que reciben "USDT" en vez de un currency_id.
     */
    public function getOrCreateByCurrencyCode(string $userId, string $isoCode): DepositAddress
    {
        $currency = Currency::where('iso_code', strtoupper($isoCode))->firstOrFail();

        return $this->getOrCreate($userId, (string) $currency->id);
    }
}
