<?php

namespace App\Domains\Games\Bingo\Repositories\Draw;

use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Repositories\Draw\Contracts\BingoDrawCodeRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para gestionar códigos de sorteo de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los códigos de sorteo (número y símbolo) asociados a las salas de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
class BingoDrawCodeRepository extends BaseRepository implements BingoDrawCodeRepositoryContract
{
    /**
     * Constructor del repositorio
     */
    public function __construct(BingoDrawCode $model)
    {
        $this->model = $model;
    }

    /**
     * Limpiar códigos huérfanos (sin sala asociada)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedCodes() : int
    {
        return $this->model->whereNull('room_id')
            ->orWhere('room_id', '')
            ->delete();
    }

    /**
     * Contar códigos por número
     */
    public function countByNumber(string $num) : int
    {
        return $this->model->where('num', $num)->count();
    }

    /**
     * Contar códigos por sala
     */
    public function countByRoom(string $roomId) : int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Contar códigos por símbolo
     */
    public function countBySymbol(string $sym) : int
    {
        return $this->model->where('sym', $sym)->count();
    }

    /**
     * Crear código para sala
     */
    public function createCodeForRoom(string $roomId, string $num, string $sym) : BingoDrawCode
    {
        return $this->create([
            'room_id' => $roomId,
            'num' => $num,
            'sym' => $sym,
        ]);
    }

    /**
     * Eliminar código de sala
     */
    public function deleteRoomCode(string $roomId) : bool
    {
        return $this->model->where('room_id', $roomId)->delete() > 0;
    }

    /**
     * Verificar si existe un código para una sala
     */
    public function existsByRoom(string $roomId) : bool
    {
        return $this->model->where('room_id', $roomId)->exists();
    }

    /**
     * Exportar códigos en formato específico
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportCodes(string $format = 'array')
    {
        $codes = $this->getAll();

        switch ($format) {
            case 'json':
                return $codes->toJson();
            case 'csv':
                // Implementar exportación CSV si es necesario
                return $codes->toArray();
            case 'array':
            default:
                return $codes->toArray();
        }
    }

    /**
     * Obtener código específico por número y símbolo
     */
    public function findByNumberAndSymbol(string $num, string $sym) : ?BingoDrawCode
    {
        return $this->model->where('num', $num)
            ->where('sym', $sym)
            ->first();
    }

    /**
     * Obtener código por sala
     */
    public function findByRoom(string $roomId) : ?BingoDrawCode
    {
        return $this->model->where('room_id', $roomId)->first();
    }

    /**
     * Generar código único para sala
     *
     * @return array{num: string, sym: string}
     */
    public function generateUniqueCode(string $roomId) : array
    {
        // Generar número aleatorio de 4 dígitos
        do {
            $num = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $sym = chr(rand(65, 90)); // A-Z
        } while ($this->isCodeInUse($num, $sym, $roomId));

        return [
            'num' => $num,
            'sym' => $sym,
        ];
    }

    /**
     * Obtener códigos por número específico
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumber(string $num) : Collection
    {
        return $this->model->where('num', $num)->get();
    }

    /**
     * Obtener códigos por patrón de número
     *
     * @param string $pattern Patrón regex o LIKE
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumberPattern(string $pattern) : Collection
    {
        return $this->model->where('num', 'like', $pattern)->get();
    }

    /**
     * Obtener códigos por símbolo específico
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbol(string $sym) : Collection
    {
        return $this->model->where('sym', $sym)->get();
    }

    /**
     * Obtener códigos por patrón de símbolo
     *
     * @param string $pattern Patrón regex o LIKE
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbolPattern(string $pattern) : Collection
    {
        return $this->model->where('sym', 'like', $pattern)->get();
    }

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
    public function getCodeStats() : array
    {
        $totalCodes = $this->model->count();
        $totalRooms = $this->model->distinct('room_id')->count('room_id');
        $uniqueNumbers = $this->getUniqueNumbers()->count();
        $uniqueSymbols = $this->getUniqueSymbols()->count();
        $codesPerRoom = $totalRooms > 0 ? $totalCodes / $totalRooms : 0;

        return [
            'total_codes' => $totalCodes,
            'total_rooms' => $totalRooms,
            'unique_numbers' => $uniqueNumbers,
            'unique_symbols' => $uniqueSymbols,
            'codes_per_room' => round($codesPerRoom, 2),
        ];
    }

    /**
     * Obtener códigos duplicados (mismo número y símbolo)
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getDuplicateCodes() : Collection
    {
        return $this->model->select('num', 'sym', DB::raw('COUNT(*) as count'))
            ->groupBy('num', 'sym')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Obtener códigos agrupados por número
     */
    public function getGroupedByNumber() : Collection
    {
        return $this->model->selectRaw('num, COUNT(*) as count')
            ->groupBy('num')
            ->orderBy('num')
            ->get();
    }

    /**
     * Obtener códigos agrupados por símbolo
     */
    public function getGroupedBySymbol() : Collection
    {
        return $this->model->selectRaw('sym, COUNT(*) as count')
            ->groupBy('sym')
            ->orderBy('sym')
            ->get();
    }

    /**
     * Obtener códigos más utilizados
     */
    public function getMostUsedCodes(int $limit = 10) : Collection
    {
        return $this->model->selectRaw('num, sym, COUNT(*) as usage_count')
            ->groupBy('num', 'sym')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener historial de códigos por sala
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getRoomCodeHistory(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Obtener todos los números únicos
     */
    public function getUniqueNumbers() : Collection
    {
        return $this->model->distinct()->pluck('num');
    }

    /**
     * Obtener todos los símbolos únicos
     */
    public function getUniqueSymbols() : Collection
    {
        return $this->model->distinct()->pluck('sym');
    }

    /**
     * Verificar si un código está en uso
     */
    public function isCodeInUse(string $num, string $sym, ?string $excludeRoomId = null) : bool
    {
        $query = $this->model->where('num', $num)->where('sym', $sym);

        if ($excludeRoomId) {
            $query->where('room_id', '!=', $excludeRoomId);
        }

        return $query->exists();
    }

    /**
     * Buscar códigos por término de búsqueda
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function searchCodes(string $searchTerm) : Collection
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('num', 'like', "%{$searchTerm}%")
                ->orWhere('sym', 'like', "%{$searchTerm}%")
                ->orWhere('room_id', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Actualizar código de sala
     */
    public function updateRoomCode(string $roomId, string $newNum, string $newSym) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->update([
                'num' => $newNum,
                'sym' => $newSym,
            ]) > 0;
    }

    /**
     * Validar formato de código
     */
    public function validateCodeFormat(string $num, string $sym) : bool
    {
        // Validar que el número sea numérico y tenga máximo 4 dígitos
        if (! is_numeric($num) || strlen($num) > 4) {
            return false;
        }

        // Validar que el símbolo sea una letra mayúscula
        return ! (! preg_match('/^[A-Z]$/', $sym));
    }

    /**
     * Validar integridad de código
     */
    public function validateCodeIntegrity(string $codeId) : bool
    {
        $code = $this->find($codeId);

        if (! $code) {
            return false;
        }

        // Validar que tenga número y símbolo
        if (empty($code->num) || empty($code->sym)) {
            return false;
        }

        // Validar formato básico
        return $this->validateCodeFormat($code->num, $code->sym);
    }
}
