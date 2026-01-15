<?php

namespace App\Repositories\Shared\Room;

use App\Models\Shared\Room\Room;
use App\Repositories\BaseRepository;
use App\Repositories\Shared\Room\Contracts\RoomRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de salas de juego.
 *
 * Implementa las operaciones específicas para el modelo Room,
 * incluyendo gestión de tipos de sala, validaciones y estadísticas.
 */
class RoomRepository extends BaseRepository implements RoomRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param Room $model Instancia del modelo Room
     */
    public function __construct(Room $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivateRooms(): Collection
    {
        return $this->getByType('PV');
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicRooms(): Collection
    {
        return $this->getByType('PB');
    }

    /**
     * {@inheritDoc}
     */
    public function isValidRoomType(string $type): bool
    {
        return array_key_exists($type, Room::TYPES);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomTypes(?string $excludeType = null): array
    {
        return Room::getRoomTypes($excludeType);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomTypeDescription(string $type): ?string
    {
        return Room::TYPES[$type] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function createRoom(string $type, array $additionalData = []): Room
    {
        if (!$this->isValidRoomType($type)) {
            throw new \InvalidArgumentException("Tipo de sala inválido: {$type}");
        }

        $roomData = array_merge(['type' => $type], $additionalData);

        return $this->model->create($roomData);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRoomType(string $roomId, string $type): bool
    {
        if (!$this->isValidRoomType($type)) {
            throw new \InvalidArgumentException("Tipo de sala inválido: {$type}");
        }

        return $this->model->where('id', $roomId)
            ->update(['type' => $type]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomTypeStatistics(): array
    {
        $total = $this->model->count();
        $stats = [];

        foreach (Room::TYPES as $code => $description) {
            $count = $this->countByType($code);
            $stats[$code] = [
                'code' => $code,
                'description' => $description,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0
            ];
        }

        $stats['total'] = $total;
        $stats['most_common'] = $this->getMostCommonRoomType();
        $stats['least_common'] = $this->getLeastCommonRoomType();

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function countByType(string $type): int
    {
        return $this->model->where('type', $type)->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByType(string $direction = 'asc'): Collection
    {
        return $this->model->orderBy('type', $direction)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByMultipleTypes(array $types): Collection
    {
        // Validar que todos los tipos sean válidos
        foreach ($types as $type) {
            if (!$this->isValidRoomType($type)) {
                throw new \InvalidArgumentException("Tipo de sala inválido: {$type}");
            }
        }

        return $this->model->whereIn('type', $types)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsRoomOfType(string $type): bool
    {
        return $this->model->where('type', $type)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(?string $filterType = null): array
    {
        $query = $this->model->select('id', 'type');

        if ($filterType && $this->isValidRoomType($filterType)) {
            $query->where('type', $filterType);
        }

        $rooms = $query->get();
        $result = [];

        foreach ($rooms as $room) {
            $typeDescription = $this->getRoomTypeDescription($room->type);
            $result[$room->id] = "Sala {$room->id} ({$typeDescription})";
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function validateRoomData(array $data): array
    {
        $errors = [];

        // Validar tipo de sala
        if (isset($data['type'])) {
            if (!$this->isValidRoomType($data['type'])) {
                $validTypes = implode(', ', array_keys(Room::TYPES));
                $errors['type'] = "El tipo de sala debe ser uno de: {$validTypes}";
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getMostCommonRoomType(): ?string
    {
        $result = $this->model->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->first();

        return $result?->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getLeastCommonRoomType(): ?string
    {
        $result = $this->model->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'asc')
            ->first();

        return $result?->type;
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkRooms(array $roomsData): Collection
    {
        $rooms = collect();

        foreach ($roomsData as $roomData) {
            // Validar datos antes de crear
            $errors = $this->validateRoomData($roomData);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Datos de sala inválidos: ' . json_encode($errors));
            }

            $type = $roomData['type'];
            unset($roomData['type']);

            $room = $this->createRoom($type, $roomData);
            $rooms->push($room);
        }

        return $rooms;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoomTypeDistribution(): array
    {
        $total = $this->model->count();
        $distribution = [];

        if ($total === 0) {
            return $distribution;
        }

        foreach (Room::TYPES as $code => $description) {
            $count = $this->countByType($code);
            $distribution[$code] = round(($count / $total) * 100, 2);
        }

        return $distribution;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateRoomType(array $roomIds, string $newType): int
    {
        if (!$this->isValidRoomType($newType)) {
            throw new \InvalidArgumentException("Tipo de sala inválido: {$newType}");
        }

        return $this->model->whereIn('id', $roomIds)
            ->update(['type' => $newType]);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupedByType(): array
    {
        $rooms = $this->model->get();
        $grouped = [];

        foreach (Room::TYPES as $code => $description) {
            $grouped[$code] = $rooms->where('type', $code);
        }

        return $grouped;
    }
}