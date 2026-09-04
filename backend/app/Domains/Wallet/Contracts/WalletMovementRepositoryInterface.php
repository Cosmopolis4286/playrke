<?php

namespace App\Domains\Wallet\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
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
     *   idempotency_key?: string|null,
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

    /**
     * Listado paginado y filtrable para el panel admin, con las relaciones
     * (wallet.currency, user, processedBy) ya cargadas para el DTO de salida.
     *
     * @param array{type?: ?string, status?: ?string, search?: ?string} $filters
     */
    public function getForAdminPanel(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Carga un único movimiento con las mismas relaciones que getForAdminPanel,
     * para el detalle/aprobación/rechazo del panel admin.
     */
    public function loadForAdminPanel(WalletMovement $movement): WalletMovement;
}
