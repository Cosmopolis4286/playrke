<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface WalletMovementRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Devuelve todos los movimientos de un usuario paginados.
     */
    public function getByUser(string $userId, int $perPage = 20) : LengthAwarePaginator;

    /**
     * Devuelve los movimientos de una billetera paginados (más recientes primero).
     */
    public function getByWallet(string $walletId, int $perPage = 20) : LengthAwarePaginator;

    /**
     * Listado paginado y filtrable para el panel admin, con las relaciones
     * (wallet.currency, user, processedBy) ya cargadas para el DTO de salida.
     *
     * @param array{type?: ?string, status?: ?string, search?: ?string, date_from?: ?string, date_to?: ?string} $filters
     */
    public function getForAdminPanel(array $filters, int $perPage = 20) : LengthAwarePaginator;

    /**
     * Mismo filtrado que getForAdminPanel() sin paginar — export CSV y stats.
     *
     * @param array{type?: ?string, status?: ?string, search?: ?string, date_from?: ?string, date_to?: ?string} $filters
     */
    public function queryForAdminPanel(array $filters) : Builder;

    /**
     * Serie diaria de aprobados/rechazados/tiempo de resolución media, para
     * el gráfico de tendencia histórica del panel admin. Mismo filtrado que
     * getForAdminPanel()/queryForAdminPanel(). Rellena huecos con 0/null
     * para los días sin actividad, para que el gráfico nunca reciba fechas
     * faltantes.
     *
     * @param array{type?: ?string, status?: ?string, search?: ?string, date_from?: ?string, date_to?: ?string} $filters
     * @return array<int, array{date: string, approved: int, rejected: int, avg_resolution_minutes: float|null}>
     */
    public function historicalTrend(array $filters, int $days = 30) : array;

    /**
     * Últimos N movimientos de una billetera (sin paginar, para resumen).
     */
    public function getLatestByWallet(string $walletId, int $limit = 10) : Collection;

    /**
     * Carga un único movimiento con las mismas relaciones que getForAdminPanel,
     * para el detalle/aprobación/rechazo del panel admin.
     */
    public function loadForAdminPanel(WalletMovement $movement) : WalletMovement;

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
    public function record(array $data) : WalletMovement;
}
