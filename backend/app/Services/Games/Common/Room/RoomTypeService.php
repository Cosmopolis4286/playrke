<?php

namespace App\Services\Games\Common\Room;

use App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface;
use App\Models\Games\Common\Room\RoomType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RoomTypeService
{
    public function __construct(
        private readonly RoomTypeRepositoryInterface $roomTypes,
    ) {
    }

    public function create(array $data) : RoomType
    {
        $code = strtoupper($data['code']);

        if ($this->roomTypes->existsByLabel($data['label'])) {
            throw new InvalidArgumentException("Ya existe un tipo de sala con la etiqueta '{$data['label']}'.");
        }

        return $this->roomTypes->createType($code, $data['label'], $data['status'] ?? 'ACTIVE');
    }

    public function delete(string $id) : bool
    {
        // bingo_rooms_config.room_type_id y bingo_rooms_automatic.room_type_id
        // tienen cascadeOnDelete(): borrar un RoomType en uso borraría en
        // cascada todas las salas (normales y automáticas) que lo
        // referencian. Se valida el uso aquí para evitar esa pérdida de
        // datos accidental — desactivar (toggleStatus) es la vía segura para
        // retirar un tipo de sala sin eliminarlo.
        $inUse = DB::table('bingo_rooms_config')->where('room_type_id', $id)->exists()
            || DB::table('bingo_rooms_automatic')->where('room_type_id', $id)->exists();

        if ($inUse) {
            throw new InvalidArgumentException('No se puede eliminar: hay salas de Bingo usando este tipo de sala. Desactívalo en su lugar.');
        }

        return $this->roomTypes->deleteType($id);
    }

    public function find(string $id) : RoomType
    {
        /** @var RoomType */
        return $this->roomTypes->findOrFail($id);
    }

    public function list() : Collection
    {
        return $this->roomTypes->all();
    }

    public function toggleStatus(string $id) : RoomType
    {
        /** @var RoomType $roomType */
        $roomType = $this->roomTypes->findOrFail($id);

        $roomType->isActive()
            ? $this->roomTypes->deactivateType($id)
            : $this->roomTypes->activateType($id);

        return $this->roomTypes->findOrFail($id);
    }

    public function update(string $id, array $data) : RoomType
    {
        /** @var RoomType $roomType */
        $roomType = $this->roomTypes->findOrFail($id);

        if ($this->roomTypes->existsByLabel($data['label'], $id)) {
            throw new InvalidArgumentException("Ya existe un tipo de sala con la etiqueta '{$data['label']}'.");
        }

        $this->roomTypes->updateLabel($id, $data['label']);

        if (isset($data['status']) && $data['status'] !== $roomType->status) {
            $this->roomTypes->updateStatus($id, $data['status']);
        }

        return $this->roomTypes->findOrFail($id);
    }
}
