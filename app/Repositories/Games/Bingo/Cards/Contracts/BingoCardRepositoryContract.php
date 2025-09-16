<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards;

use App\Models\Games\Bingo\Cards\BingoCard;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de cartones de bingo
 *
 * Define los métodos disponibles para gestionar cartones de bingo,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Cards
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
interface BingoCardRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene todos los cartones de una sala específica
     *
     * @param string $roomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['status' => 'active', 'user_id' => '...']
     * @return Collection<BingoCard> Cartones de la sala
     *
     * @example
     * $cards = $repository->getCardsByRoom('123', ['status' => 'active']);
     */
    public function getCardsByRoom(string $roomId, array $filters = []): Collection;

    /**
     * Obtiene cartones por número de serie
     *
     * @param string $serialNumber Número de serie del cartón
     * @return BingoCard|null Cartón encontrado o null
     *
     * @example
     * $card = $repository->findBySerialNumber('BNG-2024-001');
     */
    public function findBySerialNumber(string $serialNumber): ?BingoCard;

    /**
     * Obtiene cartones por hash de verificación
     *
     * @param string $cardHash Hash único del cartón
     * @return BingoCard|null Cartón encontrado o null
     *
     * @example
     * $card = $repository->findByHash('a1b2c3d4...');
     */
    public function findByHash(string $cardHash): ?BingoCard;

    /**
     * Obtiene todos los cartones de un usuario en una sala
     *
     * @param string $roomUserId ID del usuario en la sala
     * @param string|null $status Estado específico del cartón
     * @return Collection<BingoCard> Cartones del usuario
     *
     * @example
     * $cards = $repository->getCardsByRoomUser('456', 'active');
     */
    public function getCardsByRoomUser(string $roomUserId, ?string $status = null): Collection;

    /**
     * Obtiene cartones de un lote específico
     *
     * @param string $cardLotId ID del lote de cartones
     * @param string|null $status Estado específico
     * @return Collection<BingoCard> Cartones del lote
     *
     * @example
     * $cards = $repository->getCardsByLot('789');
     */
    public function getCardsByLot(string $cardLotId, ?string $status = null): Collection;

    /**
     * Obtiene cartones por vendedor
     *
     * @param string $sellerId ID del vendedor
     * @param array $filters Filtros adicionales ['room_id' => '...', 'status' => '...']
     * @return Collection<BingoCard> Cartones del vendedor
     *
     * @example
     * $cards = $repository->getCardsBySeller('321', ['status' => 'active']);
     */
    public function getCardsBySeller(string $sellerId, array $filters = []): Collection;

    /**
     * Obtiene cartones ganadores de una sala
     *
     * @param string $roomId ID de la sala
     * @param array $orderBy Ordenamiento ['campo' => 'direccion']
     * @return Collection<BingoCard> Cartones ganadores
     *
     * @example
     * $winners = $repository->getWinnerCards('123', ['validated_at' => 'desc']);
     */
    public function getWinnerCards(string $roomId, array $orderBy = []): Collection;

    /**
     * Valida un cartón y actualiza su estado
     *
     * @param string $id ID del cartón
     * @param array $validationData Datos adicionales de validación
     * @return bool True si se validó correctamente
     *
     * @example
     * $validated = $repository->validateCard('123', ['validated_by' => 'admin']);
     */
    public function validateCard(string $id, array $validationData = []): bool;

    /**
     * Cambia el estado de múltiples cartones
     *
     * @param array $cardIds IDs de los cartones
     * @param string $newStatus Nuevo estado ('active', 'inactive', 'winner', 'cancelled')
     * @return int Número de cartones actualizados
     *
     * @example
     * $updated = $repository->changeBulkStatus(['1', '2'], 'inactive');
     */
    public function changeBulkStatus(array $cardIds, string $newStatus): int;

    /**
     * Obtiene estadísticas de cartones por sala
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas agrupadas por estado
     *
     * @example
     * $stats = $repository->getStatsByRoom('123');
     * // ['active' => 50, 'winner' => 5, 'inactive' => 2]
     */
    public function getStatsByRoom(string $roomId): array;

    /**
     * Busca cartones por coincidencia de números
     *
     * @param array $numberPattern Array de números a buscar
     * @param string $roomId ID de la sala (opcional)
     * @return Collection<BingoCard> Cartones que contienen los números
     *
     * @example
     * $cards = $repository->findByNumberPattern([15, 32, 47, 68, 75], '123');
     */
    public function findByNumberPattern(array $numberPattern, ?string $roomId = null): Collection;

    /**
     * Obtiene cartones creados en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCard> Cartones en el rango
     *
     * @example
     * $cards = $repository->getCardsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getCardsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Verifica la integridad de un cartón comparando con su hash
     *
     * @param string $id ID del cartón
     * @return bool True si la integridad es correcta
     *
     * @example
     * $isValid = $repository->verifyCardIntegrity('123');
     */
    public function verifyCardIntegrity(string $id): bool;
}