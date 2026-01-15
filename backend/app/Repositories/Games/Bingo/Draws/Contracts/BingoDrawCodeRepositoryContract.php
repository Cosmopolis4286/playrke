<?php

namespace App\Repositories\Games\Bingo\Draws\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Bingo\Draws\BingoDrawCode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de códigos de sorteo de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los códigos de sorteo (número y símbolo) asociados a las salas de bingo.
 *
 * @package App\Repositories\Games\Bingo\Draws\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoDrawCodeRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener código por sala
     *
     * @param string $roomId
     * @return BingoDrawCode|null
     */
    public function findByRoom(string $roomId): ?BingoDrawCode;

    /**
     * Obtener códigos por número específico
     *
     * @param string $num
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumber(string $num): Collection;

    /**
     * Obtener códigos por símbolo específico
     *
     * @param string $sym
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbol(string $sym): Collection;

    /**
     * Obtener código específico por número y símbolo
     *
     * @param string $num
     * @param string $sym
     * @return BingoDrawCode|null
     */
    public function findByNumberAndSymbol(string $num, string $sym): ?BingoDrawCode;

    /**
     * Verificar si existe un código para una sala
     *
     * @param string $roomId
     * @return bool
     */
    public function existsByRoom(string $roomId): bool;

    /**
     * Obtener todos los números únicos
     *
     * @return Collection
     */
    public function getUniqueNumbers(): Collection;

    /**
     * Obtener todos los símbolos únicos
     *
     * @return Collection
     */
    public function getUniqueSymbols(): Collection;

    /**
     * Obtener códigos agrupados por número
     *
     * @return Collection
     */
    public function getGroupedByNumber(): Collection;

    /**
     * Obtener códigos agrupados por símbolo
     *
     * @return Collection
     */
    public function getGroupedBySymbol(): Collection;

    /**
     * Contar códigos por sala
     *
     * @param string $roomId
     * @return int
     */
    public function countByRoom(string $roomId): int;

    /**
     * Contar códigos por número
     *
     * @param string $num
     * @return int
     */
    public function countByNumber(string $num): int;

    /**
     * Contar códigos por símbolo
     *
     * @param string $sym
     * @return int
     */
    public function countBySymbol(string $sym): int;

    /**
     * Obtener estadísticas de códigos
     *
     * @return array{
     *     total_codes: int,
     *     total_rooms: int,
     *     unique_numbers: int,
     *     unique_symbols: int,
     *     codes_per_room: float
     * }
     */
    public function getCodeStats(): array;

    /**
     * Buscar códigos por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoDrawCode>
     */
    public function searchCodes(string $searchTerm): Collection;

    /**
     * Obtener códigos duplicados (mismo número y símbolo)
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getDuplicateCodes(): Collection;

    /**
     * Validar integridad de código
     *
     * @param string $codeId
     * @return bool
     */
    public function validateCodeIntegrity(string $codeId): bool;

    /**
     * Crear código para sala
     *
     * @param string $roomId
     * @param string $num
     * @param string $sym
     * @return BingoDrawCode
     */
    public function createCodeForRoom(string $roomId, string $num, string $sym): BingoDrawCode;

    /**
     * Actualizar código de sala
     *
     * @param string $roomId
     * @param string $newNum
     * @param string $newSym
     * @return bool
     */
    public function updateRoomCode(string $roomId, string $newNum, string $newSym): bool;

    /**
     * Eliminar código de sala
     *
     * @param string $roomId
     * @return bool
     */
    public function deleteRoomCode(string $roomId): bool;

    /**
     * Obtener códigos por patrón de número
     *
     * @param string $pattern Patrón regex o LIKE
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumberPattern(string $pattern): Collection;

    /**
     * Obtener códigos por patrón de símbolo
     *
     * @param string $pattern Patrón regex o LIKE
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbolPattern(string $pattern): Collection;

    /**
     * Generar código único para sala
     *
     * @param string $roomId
     * @return array{num: string, sym: string}
     */
    public function generateUniqueCode(string $roomId): array;

    /**
     * Verificar si un código está en uso
     *
     * @param string $num
     * @param string $sym
     * @param string|null $excludeRoomId
     * @return bool
     */
    public function isCodeInUse(string $num, string $sym, ?string $excludeRoomId = null): bool;

    /**
     * Obtener historial de códigos por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoDrawCode>
     */
    public function getRoomCodeHistory(string $roomId): Collection;

    /**
     * Exportar códigos en formato específico
     *
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportCodes(string $format = 'array');

    /**
     * Limpiar códigos huérfanos (sin sala asociada)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedCodes(): int;

    /**
     * Obtener códigos más utilizados
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostUsedCodes(int $limit = 10): Collection;

    /**
     * Validar formato de código
     *
     * @param string $num
     * @param string $sym
     * @return bool
     */
    public function validateCodeFormat(string $num, string $sym): bool;
}