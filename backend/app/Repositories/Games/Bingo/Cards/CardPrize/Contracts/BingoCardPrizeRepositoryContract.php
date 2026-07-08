<?php

namespace App\Repositories\Games\Bingo\Cards\CardPrize\Contracts;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de premios de cartones de bingo
 *
 * Define los métodos disponibles para gestionar premios de cartones de bingo,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Card\CardPrize
 * @author Sistema de Gestión de Premios de Bingo
 * @version 1.0
 */
interface BingoCardPrizeRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los premios de una sala específica
     *
     * @param string $roomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['prize_status' => 'waiting']
     * @return Collection<BingoCardPrize> Premios de la sala
     *
     * @example
     * $prizes = $repository->getPrizesByRoom('123', ['prize_status' => 'waiting']);
     */
    public function getPrizesByRoom(string $roomId, array $filters = []): Collection;

    /**
     * Obtiene premios por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios del creador
     *
     * @example
     * $prizes = $repository->getPrizesByCreator('456', ['status' => true]);
     */
    public function getPrizesByCreator(string $creatorUserId, array $filters = []): Collection;

    /**
     * Obtiene premios por estado
     *
     * @param string $status Estado del premio ('waiting', 'claimed', 'cancelled', 'suspended')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios con el estado especificado
     *
     * @example
     * $waitingPrizes = $repository->getPrizesByStatus('waiting');
     */
    public function getPrizesByStatus(string $status, array $filters = []): Collection;

    /**
     * Obtiene premios por tipo
     *
     * @param string $prizeType Tipo de premio ('full_house', 'single_line', 'serial')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios del tipo especificado
     *
     * @example
     * $fullHousePrizes = $repository->getPrizesByType('full_house');
     */
    public function getPrizesByType(string $prizeType, array $filters = []): Collection;

    /**
     * Busca un premio por cartón ganador
     *
     * @param string $cardId ID del cartón ganador
     * @param array $filters Filtros adicionales
     * @return BingoCardPrize|null Premio encontrado o null
     *
     * @example
     * $prize = $repository->findByWinningCard('789');
     */
    public function findByWinningCard(string $cardId, array $filters = []): ?BingoCardPrize;

    /**
     * Busca un premio por código de verificación
     *
     * @param string $verificationCode Código de verificación
     * @return BingoCardPrize|null Premio encontrado o null
     *
     * @example
     * $prize = $repository->findByVerificationCode('ABC12345');
     */
    public function findByVerificationCode(string $verificationCode): ?BingoCardPrize;

    /**
     * Obtiene premios activos
     *
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios activos
     *
     * @example
     * $activePrizes = $repository->getActivePrizes(['room_id' => '123']);
     */
    public function getActivePrizes(array $filters = []): Collection;

    /**
     * Obtiene premios por ronda de juego
     *
     * @param string $gameRound Ronda del juego
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios de la ronda
     *
     * @example
     * $roundPrizes = $repository->getPrizesByGameRound('R001', ['room_id' => '123']);
     */
    public function getPrizesByGameRound(string $gameRound, array $filters = []): Collection;

    /**
     * Obtiene premios por lote de cartones
     *
     * @param string $cardLotId ID del lote de cartones
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios del lote
     *
     * @example
     * $lotPrizes = $repository->getPrizesByCardLot('lot123');
     */
    public function getPrizesByCardLot(string $cardLotId, array $filters = []): Collection;

    /**
     * Actualiza el estado de un premio
     *
     * @param string $prizeId ID del premio
     * @param string $newStatus Nuevo estado
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePrizeStatus('123', 'claimed');
     */
    public function updatePrizeStatus(string $prizeId, string $newStatus): bool;

    /**
     * Marca un premio como reclamado
     *
     * @param string $prizeId ID del premio
     * @return bool True si se marcó correctamente
     *
     * @example
     * $claimed = $repository->markPrizeAsClaimed('123');
     */
    public function markPrizeAsClaimed(string $prizeId): bool;

    /**
     * Activa o desactiva un premio
     *
     * @param string $prizeId ID del premio
     * @param bool $isActive Estado de activación
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->togglePrizeActivation('123', true);
     */
    public function togglePrizeActivation(string $prizeId, bool $isActive): bool;

    /**
     * Verifica si existe un premio para un cartón en una ronda específica
     *
     * @param string $cardId ID del cartón
     * @param string $gameRound Ronda del juego
     * @return bool True si existe el premio
     *
     * @example
     * $exists = $repository->existsPrizeForCardInRound('card123', 'R001');
     */
    public function existsPrizeForCardInRound(string $cardId, string $gameRound): bool;

    /**
     * Obtiene estadísticas de premios por sala
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas de la sala
     *
     * @example
     * $stats = $repository->getPrizeStatsByRoom('123');
     * // ['total_prizes' => 50, 'claimed_prizes' => 30, 'waiting_prizes' => 20]
     */
    public function getPrizeStatsByRoom(string $roomId): array;

    /**
     * Obtiene estadísticas de premios por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @return array Estadísticas del creador
     *
     * @example
     * $stats = $repository->getPrizeStatsByCreator('456');
     */
    public function getPrizeStatsByCreator(string $creatorUserId): array;

    /**
     * Cuenta premios por tipo
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por tipo
     *
     * @example
     * $counts = $repository->countPrizesByType();
     * // ['full_house' => 25, 'single_line' => 15, 'serial' => 10]
     */
    public function countPrizesByType(array $filters = []): array;

    /**
     * Cuenta premios por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countPrizesByStatus();
     * // ['waiting' => 20, 'claimed' => 25, 'cancelled' => 3, 'suspended' => 2]
     */
    public function countPrizesByStatus(array $filters = []): array;

    /**
     * Obtiene premios con mayor valor
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios con mayor valor
     *
     * @example
     * $topPrizes = $repository->getHighestValuePrizes(10);
     */
    public function getHighestValuePrizes(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene premios recientes
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios más recientes
     *
     * @example
     * $recentPrizes = $repository->getRecentPrizes(20);
     */
    public function getRecentPrizes(int $limit = 20, array $filters = []): Collection;

    /**
     * Obtiene premios por rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios en el rango
     *
     * @example
     * $prizes = $repository->getPrizesByDateRange('2024-01-01', '2024-01-31');
     */
    public function getPrizesByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene premios por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrize> Premios en la moneda especificada
     *
     * @example
     * $usdPrizes = $repository->getPrizesByCurrency('usd_id');
     */
    public function getPrizesByCurrency(string $currencyId, array $filters = []): Collection;

    /**
     * Obtiene el valor total de premios
     *
     * @param array $filters Filtros adicionales
     * @param string $currency Moneda para el cálculo ('usd' o 'local')
     * @return float Valor total
     *
     * @example
     * $totalValue = $repository->getTotalPrizesValue(['room_id' => '123'], 'usd');
     */
    public function getTotalPrizesValue(array $filters = [], string $currency = 'usd'): float;

    /**
     * Obtiene ganadores únicos
     *
     * @param array $filters Filtros adicionales
     * @return Collection IDs únicos de ganadores
     *
     * @example
     * $winners = $repository->getUniqueWinners(['room_id' => '123']);
     */
    public function getUniqueWinners(array $filters = []): Collection;

    /**
     * Obtiene premios pendientes de pago por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @return Collection<BingoCardPrize> Premios pendientes
     *
     * @example
     * $pending = $repository->getPendingPaymentsByCreator('456');
     */
    public function getPendingPaymentsByCreator(string $creatorUserId): Collection;

    /**
     * Genera código de verificación único para un premio
     *
     * @param string $prizeId ID del premio
     * @return string|null Código generado o null si falló
     *
     * @example
     * $code = $repository->generateVerificationCode('123');
     */
    public function generateVerificationCode(string $prizeId): ?string;

    /**
     * Actualiza múltiples premios en lote
     *
     * @param array $prizeIds IDs de los premios
     * @param array $updateData Datos a actualizar
     * @return int Número de premios actualizados
     *
     * @example
     * $updated = $repository->bulkUpdatePrizes(['1', '2'], ['status' => false]);
     */
    public function bulkUpdatePrizes(array $prizeIds, array $updateData): int;

    /**
     * Elimina premios cancelados antiguos
     *
     * @param int $daysOld Días de antigüedad
     * @return int Número de premios eliminados
     *
     * @example
     * $deleted = $repository->deleteOldCancelledPrizes(90);
     */
    public function deleteOldCancelledPrizes(int $daysOld = 90): int;
}