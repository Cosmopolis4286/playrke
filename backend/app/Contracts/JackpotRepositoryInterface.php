<?php

namespace App\Contracts;

use App\Models\Jackpot;

interface JackpotRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca el pozo actual de un tipo de jackpot, si ya existe.
     */
    public function findByType(string $jackpotTypeId): ?Jackpot;

    /**
     * Obtiene el pozo de un tipo de jackpot, creándolo con saldo 0 si es el
     * primer aporte que recibe.
     */
    public function firstOrCreateByType(string $jackpotTypeId): Jackpot;

    /**
     * Incrementa el saldo de un jackpot con un UPDATE atómico a nivel SQL
     * (igual que WalletRepository::incrementBalance) — sin lock explícito de
     * aplicación.
     */
    public function incrementAmount(string $jackpotId, float $amount): bool;
}
