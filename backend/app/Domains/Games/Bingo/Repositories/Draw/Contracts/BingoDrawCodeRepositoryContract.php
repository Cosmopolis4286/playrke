<?php

namespace App\Domains\Games\Bingo\Repositories\Draw\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de códigos de sorteo de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los códigos de sorteo (número y símbolo) asociados a las salas de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoDrawCodeRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Limpiar códigos huérfanos (sin sala asociada)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedCodes() : int;

    /**
     * Contar códigos por número
     */
    public function countByNumber(string $num) : int;

    /**
     * Contar códigos por sala
     */
    public function countByRoom(string $roomId) : int;

    /**
     * Contar códigos por símbolo
     */
    public function countBySymbol(string $sym) : int;

    /**
     * Crear código para sala
     */
    public function createCodeForRoom(string $roomId, string $num, string $sym) : BingoDrawCode;

    /**
     * Eliminar código de sala
     */
    public function deleteRoomCode(string $roomId) : bool;

    /**
     * Verificar si existe un código para una sala
     */
    public function existsByRoom(string $roomId) : bool;

    /**
     * Exportar códigos en formato específico
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportCodes(string $format = 'array');

    /**
     * Obtener código específico por número y símbolo
     */
    public function findByNumberAndSymbol(string $num, string $sym) : ?BingoDrawCode;

    /**
     * Obtener código por sala
     */
    public function findByRoom(string $roomId) : ?BingoDrawCode;

    /**
     * Generar código único para sala
     *
     * @return array{num: string, sym: string}
     */
    public function generateUniqueCode(string $roomId) : array;

    /**
     * Obtener códigos por número específico
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumber(string $num) : Collection;

    /**
     * Obtener códigos por patrón de número
     *
     * @param string $pattern Patrón regex o LIKE
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumberPattern(string $pattern) : Collection;

    /**
     * Obtener códigos por símbolo específico
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbol(string $sym) : Collection;

    /**
     * Obtener códigos por patrón de símbolo
     *
     * @param string $pattern Patrón regex o LIKE
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbolPattern(string $pattern) : Collection;

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
    public function getCodeStats() : array;

    /**
     * Obtener códigos duplicados (mismo número y símbolo)
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getDuplicateCodes() : Collection;

    /**
     * Obtener códigos agrupados por número
     */
    public function getGroupedByNumber() : Collection;

    /**
     * Obtener códigos agrupados por símbolo
     */
    public function getGroupedBySymbol() : Collection;

    /**
     * Obtener códigos más utilizados
     */
    public function getMostUsedCodes(int $limit = 10) : Collection;

    /**
     * Obtener historial de códigos por sala
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getRoomCodeHistory(string $roomId) : Collection;

    /**
     * Obtener todos los números únicos
     */
    public function getUniqueNumbers() : Collection;

    /**
     * Obtener todos los símbolos únicos
     */
    public function getUniqueSymbols() : Collection;

    /**
     * Verificar si un código está en uso
     */
    public function isCodeInUse(string $num, string $sym, ?string $excludeRoomId = null) : bool;

    /**
     * Buscar códigos por término de búsqueda
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function searchCodes(string $searchTerm) : Collection;

    /**
     * Actualizar código de sala
     */
    public function updateRoomCode(string $roomId, string $newNum, string $newSym) : bool;

    /**
     * Validar formato de código
     */
    public function validateCodeFormat(string $num, string $sym) : bool;

    /**
     * Validar integridad de código
     */
    public function validateCodeIntegrity(string $codeId) : bool;
}
