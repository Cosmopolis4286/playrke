<?php

namespace App\Contracts;

use App\Models\JackpotMovement;
use Illuminate\Database\Eloquent\Collection;

interface JackpotMovementRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Registra un nuevo movimiento de jackpot.
     *
     * @param array{
     *   jackpot_id:      string,
     *   room_id?:        string|null,
     *   type:            string,
     *   amount:          float,
     *   balance_before:  float,
     *   balance_after:   float,
     *   description?:    string|null,
     *   idempotency_key?: string|null,
     *   metadata?:       array|null,
     * } $data
     */
    public function record(array $data): JackpotMovement;

    /**
     * Últimos N movimientos de un jackpot (más recientes primero).
     */
    public function getByJackpot(string $jackpotId, int $limit = 20): Collection;
}
