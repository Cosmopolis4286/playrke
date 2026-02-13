<?php

namespace App\Repositories\Games\Bingo\Card\CardLot;

use App\Models\Bingo\Cards\BingoCard;
use App\Repositories\Games\Bingo\Card\CardLot\Contracts\BingoCardRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los cartones de bingo, incluyendo consultas complejas y validaciones.
 *
 * @package App\Repositories
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
class BingoCardRepository extends BaseRepository implements BingoCardRepositoryContract
{
    public function __construct(BingoCard $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene todos los cartones de una sala específica
     */
    public function getCardsByRoom(string $roomId, array $filters = []): Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('card_status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->whereHas('roomUser', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['validated'])) {
            $query->where('is_validated', $filters['validated']);
        }

        return $query->with(['roomUser.user', 'seller', 'cardLot', 'currency'])
                    ->orderBy('serial_number')
                    ->get();
    }

    /**
     * Obtiene cartones por número de serie
     */
    public function findBySerialNumber(string $serialNumber): ?BingoCard
    {
        return $this->model->where('serial_number', $serialNumber)
                          ->with(['room', 'roomUser.user', 'seller', 'cardLot'])
                          ->first();
    }

    /**
     * Obtiene cartones por hash de verificación
     */
    public function findByHash(string $cardHash): ?BingoCard
    {
        return $this->model->where('card_hash', $cardHash)
                          ->with(['room', 'roomUser.user', 'seller', 'cardLot'])
                          ->first();
    }

    /**
     * Obtiene todos los cartones de un usuario en una sala
     */
    public function getCardsByRoomUser(string $roomUserId, ?string $status = null): Collection
    {
        $query = $this->model->where('room_user_id', $roomUserId);

        if ($status) {
            $query->where('card_status', $status);
        }

        return $query->with(['room', 'seller', 'cardLot', 'currency'])
                    ->orderBy('serial_number')
                    ->get();
    }

    /**
     * Obtiene cartones de un lote específico
     */
    public function getCardsByLot(string $cardLotId, ?string $status = null): Collection
    {
        $query = $this->model->where('card_lot_id', $cardLotId);

        if ($status) {
            $query->where('card_status', $status);
        }

        return $query->with(['room', 'roomUser.user', 'seller'])
                    ->orderBy('serial_number')
                    ->get();
    }

    /**
     * Obtiene cartones por vendedor
     */
    public function getCardsBySeller(string $sellerId, array $filters = []): Collection
    {
        $query = $this->model->where('seller_id', $sellerId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('card_status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['room', 'roomUser.user', 'cardLot'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene cartones ganadores de una sala
     */
    public function getWinnerCards(string $roomId, array $orderBy = []): Collection
    {
        $query = $this->model->where('room_id', $roomId)
                            ->where('card_status', 'winner');

        // Aplicar ordenamiento
        foreach ($orderBy as $campo => $direccion) {
            $query->orderBy($campo, $direccion);
        }

        // Ordenamiento por defecto
        if (empty($orderBy)) {
            $query->orderBy('validated_at', 'desc');
        }

        return $query->with(['roomUser.user', 'seller', 'cardLot', 'prizes'])
                    ->get();
    }

    /**
     * Valida un cartón y actualiza su estado
     */
    public function validateCard(string $id, array $validationData = []): bool
    {
        $datosActualizacion = [
            'is_validated' => true,
            'validated_at' => now(),
            'card_status' => 'winner'
        ];

        // Agregar datos adicionales de validación si existen
        $datosActualizacion = array_merge($datosActualizacion, $validationData);

        return $this->model->where('id', $id)->update($datosActualizacion) > 0;
    }

    /**
     * Cambia el estado de múltiples cartones
     */
    public function changeBulkStatus(array $cardIds, string $newStatus): int
    {
        $updateData = ['card_status' => $newStatus];

        // Si el estado es winner, marcar como validado
        if ($newStatus === 'winner') {
            $updateData['is_validated'] = true;
            $updateData['validated_at'] = now();
        }

        return $this->model->whereIn('id', $cardIds)
                          ->update($updateData);
    }

    /**
     * Obtiene estadísticas de cartones por sala
     */
    public function getStatsByRoom(string $roomId): array
    {
        $stats = $this->model->where('room_id', $roomId)
                            ->select('card_status', DB::raw('count(*) as total'))
                            ->groupBy('card_status')
                            ->pluck('total', 'card_status')
                            ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['active', 'inactive', 'winner', 'cancelled'];
        foreach ($validStatuses as $status) {
            if (!isset($stats[$status])) {
                $stats[$status] = 0;
            }
        }

        return $stats;
    }

    /**
     * Busca cartones por coincidencia de números
     */
    public function findByNumberPattern(array $numberPattern, ?string $roomId = null): Collection
    {
        $query = $this->model->newQuery();

        // Filtrar por sala si se especifica
        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        // Construir consulta WHERE para buscar números en las columnas del cartón
        $query->where(function ($q) use ($numberPattern) {
            foreach ($numberPattern as $number) {
                $q->orWhere(function ($subQ) use ($number) {
                    // Buscar en todas las posiciones del cartón (B1-B5, I1-I5, etc.)
                    $columns = ['b1', 'b2', 'b3', 'b4', 'b5',
                               'i1', 'i2', 'i3', 'i4', 'i5',
                               'n1', 'n2', 'n4', 'n5', // N3 es libre
                               'g1', 'g2', 'g3', 'g4', 'g5',
                               'o1', 'o2', 'o3', 'o4', 'o5'];

                    foreach ($columns as $column) {
                        $subQ->orWhere($column, $number);
                    }
                });
            }
        });

        return $query->with(['room', 'roomUser.user', 'seller'])
                    ->orderBy('serial_number')
                    ->get();
    }

    /**
     * Obtiene cartones creados en un rango de fechas
     */
    public function getCardsByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('card_status', $filters['status']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['room', 'roomUser.user', 'seller', 'cardLot'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Verifica la integridad de un cartón comparando con su hash
     */
    public function verifyCardIntegrity(string $id): bool
    {
        $card = $this->model->find($id);

        if (!$card) {
            return false;
        }

        // Construir array con los números del cartón
        $cardNumbers = [];
        $columns = ['b1', 'b2', 'b3', 'b4', 'b5',
                    'i1', 'i2', 'i3', 'i4', 'i5',
                    'n1', 'n2', 'n3', 'n4', 'n5', // Incluir N3 aunque pueda ser null
                    'g1', 'g2', 'g3', 'g4', 'g5',
                    'o1', 'o2', 'o3', 'o4', 'o5'];

        foreach ($columns as $column) {
            $cardNumbers[] = $card->$column ?? 'FREE';
        }

        // Generar hash con los datos actuales
        $dataForHash = [
            'serial_number' => $card->serial_number,
            'numbers' => $cardNumbers,
            'room_id' => $card->room_id,
            'created_at' => $card->created_at->format('Y-m-d H:i:s')
        ];

        $calculatedHash = hash('sha256', json_encode($dataForHash));

        return $calculatedHash === $card->card_hash;
    }
}
