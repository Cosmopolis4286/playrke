<?php

namespace App\Repositories\Bingo\Draw;

use App\Models\Bingo\Draw\BingoDrawCode;
use App\Repositories\BaseRepository;
use App\Repositories\Bingo\Draw\Contracts\BingoDrawCodeRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para gestionar códigos de sorteo de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los códigos de sorteo (número y símbolo) asociados a las salas de bingo.
 *
 * @package App\Repositories\Bingo\Draw
 * @author Sistema PlayRKE
 * @version 1.0
 */
class BingoDrawCodeRepository extends BaseRepository implements BingoDrawCodeRepositoryContract
{
    /**
     * Constructor del repositorio
     *
     * @param BingoDrawCode $model
     */
    public function __construct(BingoDrawCode $model)
    {
        $this->model = $model;
    }

    /**
     * Obtener código por sala
     *
     * @param string $roomId
     * @return BingoDrawCode|null
     */
    public function findByRoom(string $roomId): ?BingoDrawCode
    {
        return $this->model->where('room', $roomId)->first();
    }

    /**
     * Obtener códigos por número específico
     *
     * @param string $num
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumber(string $num): Collection
    {
        return $this->model->where('num', $num)->get();
    }

    /**
     * Obtener códigos por símbolo específico
     *
     * @param string $sym
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbol(string $sym): Collection
    {
        return $this->model->where('sym', $sym)->get();
    }

    /**
     * Obtener código específico por número y símbolo
     *
     * @param string $num
     * @param string $sym
     * @return BingoDrawCode|null
     */
    public function findByNumberAndSymbol(string $num, string $sym): ?BingoDrawCode
    {
        return $this->model->where('num', $num)
            ->where('sym', $sym)
            ->first();
    }

    /**
     * Verificar si existe un código para una sala
     *
     * @param string $roomId
     * @return bool
     */
    public function existsByRoom(string $roomId): bool
    {
        return $this->model->where('room', $roomId)->exists();
    }

    /**
     * Obtener todos los números únicos
     *
     * @return Collection
     */
    public function getUniqueNumbers(): Collection
    {
        return $this->model->distinct()->pluck('num');
    }

    /**
     * Obtener todos los símbolos únicos
     *
     * @return Collection
     */
    public function getUniqueSymbols(): Collection
    {
        return $this->model->distinct()->pluck('sym');
    }

    /**
     * Obtener códigos agrupados por número
     *
     * @return Collection
     */
    public function getGroupedByNumber(): Collection
    {
        return $this->model->selectRaw('num, COUNT(*) as count')
            ->groupBy('num')
            ->orderBy('num')
            ->get();
    }

    /**
     * Obtener códigos agrupados por símbolo
     *
     * @return Collection
     */
    public function getGroupedBySymbol(): Collection
    {
        return $this->model->selectRaw('sym, COUNT(*) as count')
            ->groupBy('sym')
            ->orderBy('sym')
            ->get();
    }

    /**
     * Contar códigos por sala
     *
     * @param string $roomId
     * @return int
     */
    public function countByRoom(string $roomId): int
    {
        return $this->model->where('room', $roomId)->count();
    }

    /**
     * Contar códigos por número
     *
     * @param string $num
     * @return int
     */
    public function countByNumber(string $num): int
    {
        return $this->model->where('num', $num)->count();
    }

    /**
     * Contar códigos por símbolo
     *
     * @param string $sym
     * @return int
     */
    public function countBySymbol(string $sym): int
    {
        return $this->model->where('sym', $sym)->count();
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
    public function getCodeStats(): array
    {
        $totalCodes = $this->model->count();
        $totalRooms = $this->model->distinct('room')->count();
        $uniqueNumbers = $this->getUniqueNumbers()->count();
        $uniqueSymbols = $this->getUniqueSymbols()->count();
        $codesPerRoom = $totalRooms > 0 ? $totalCodes / $totalRooms : 0;

        return [
            'total_codes' => $totalCodes,
            'total_rooms' => $totalRooms,
            'unique_numbers' => $uniqueNumbers,
            'unique_symbols' => $uniqueSymbols,
            'codes_per_room' => round($codesPerRoom, 2)
        ];
    }

    /**
     * Buscar códigos por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoDrawCode>
     */
    public function searchCodes(string $searchTerm): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('num', 'like', "%{$searchTerm}%")
                ->orWhere('sym', 'like', "%{$searchTerm}%")
                ->orWhere('room', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Obtener códigos duplicados (mismo número y símbolo)
     *
     * @return Collection<int, BingoDrawCode>
     */
    public function getDuplicateCodes(): Collection
    {
        return $this->model->select('num', 'sym', DB::raw('COUNT(*) as count'))
            ->groupBy('num', 'sym')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Validar integridad de código
     *
     * @param string $codeId
     * @return bool
     */
    public function validateCodeIntegrity(string $codeId): bool
    {
        $code = $this->find($codeId);

        if (!$code) {
            return false;
        }

        // Validar que tenga número y símbolo
        if (empty($code->num) || empty($code->sym)) {
            return false;
        }

        // Validar formato básico
        return $this->validateCodeFormat($code->num, $code->sym);
    }

    /**
     * Crear código para sala
     *
     * @param string $roomId
     * @param string $num
     * @param string $sym
     * @return BingoDrawCode
     */
    public function createCodeForRoom(string $roomId, string $num, string $sym): BingoDrawCode
    {
        return $this->create([
            'room' => $roomId,
            'num' => $num,
            'sym' => $sym
        ]);
    }

    /**
     * Actualizar código de sala
     *
     * @param string $roomId
     * @param string $newNum
     * @param string $newSym
     * @return bool
     */
    public function updateRoomCode(string $roomId, string $newNum, string $newSym): bool
    {
        return $this->model->where('room', $roomId)
            ->update([
                'num' => $newNum,
                'sym' => $newSym
            ]) > 0;
    }

    /**
     * Eliminar código de sala
     *
     * @param string $roomId
     * @return bool
     */
    public function deleteRoomCode(string $roomId): bool
    {
        return $this->model->where('room', $roomId)->delete() > 0;
    }

    /**
     * Obtener códigos por patrón de número
     *
     * @param string $pattern Patrón regex o LIKE
     * @return Collection<int, BingoDrawCode>
     */
    public function getByNumberPattern(string $pattern): Collection
    {
        return $this->model->where('num', 'like', $pattern)->get();
    }

    /**
     * Obtener códigos por patrón de símbolo
     *
     * @param string $pattern Patrón regex o LIKE
     * @return Collection<int, BingoDrawCode>
     */
    public function getBySymbolPattern(string $pattern): Collection
    {
        return $this->model->where('sym', 'like', $pattern)->get();
    }

    /**
     * Generar código único para sala
     *
     * @param string $roomId
     * @return array{num: string, sym: string}
     */
    public function generateUniqueCode(string $roomId): array
    {
        // Generar número aleatorio de 4 dígitos
        do {
            $num = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $sym = chr(rand(65, 90)); // A-Z
        } while ($this->isCodeInUse($num, $sym, $roomId));

        return [
            'num' => $num,
            'sym' => $sym
        ];
    }

    /**
     * Verificar si un código está en uso
     *
     * @param string $num
     * @param string $sym
     * @param string|null $excludeRoomId
     * @return bool
     */
    public function isCodeInUse(string $num, string $sym, ?string $excludeRoomId = null): bool
    {
        $query = $this->model->where('num', $num)->where('sym', $sym);

        if ($excludeRoomId) {
            $query->where('room', '!=', $excludeRoomId);
        }

        return $query->exists();
    }

    /**
     * Obtener historial de códigos por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoDrawCode>
     */
    public function getRoomCodeHistory(string $roomId): Collection
    {
        return $this->model->where('room', $roomId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Exportar códigos en formato específico
     *
     * @param string $format 'json'|'csv'|'array'
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
     * Limpiar códigos huérfanos (sin sala asociada)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedCodes(): int
    {
        return $this->model->whereNull('room')
            ->orWhere('room', '')
            ->delete();
    }

    /**
     * Obtener códigos más utilizados
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostUsedCodes(int $limit = 10): Collection
    {
        return $this->model->selectRaw('num, sym, COUNT(*) as usage_count')
            ->groupBy('num', 'sym')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Validar formato de código
     *
     * @param string $num
     * @param string $sym
     * @return bool
     */
    public function validateCodeFormat(string $num, string $sym): bool
    {
        // Validar que el número sea numérico y tenga máximo 4 dígitos
        if (!is_numeric($num) || strlen($num) > 4) {
            return false;
        }

        // Validar que el símbolo sea una letra mayúscula
        if (!preg_match('/^[A-Z]$/', $sym)) {
            return false;
        }

        return true;
    }
}
