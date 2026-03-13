<?php

namespace App\Contracts;

use App\Models\WalletMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WalletMovementRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Registra un nuevo movimiento de billetera.
     *
     * @param array{
     *   wallet_id:      string,
     *   user_id:        string,
     *   type:           string,
     *   amount:         float,
     *   balance_before: float,
     *   balance_after:  float,
     *   description?:   string|null,
     *   reference_id?:  string|null,
     * } $data
     */
    public function record(array $data): WalletMovement;

    /**
     * Devuelve los movimientos de una billetera paginados (más recientes primero).
     */
    public function getByWallet(string $walletId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Devuelve todos los movimientos de un usuario paginados.
     */
    public function getByUser(string $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Últimos N movimientos de una billetera (sin paginar, para resumen).
     */
    public function getLatestByWallet(string $walletId, int $limit = 10): Collection;
}
