<?php

namespace App\Services\Games\Bingo\Room\Automatic;

use App\Models\BingoRoomAutomatic;
use App\Services\Games\Bingo\Cards\CartonPriceService;
use App\Services\Games\Bingo\Rooms\RoomConfigService;
use App\Services\Games\Bingo\Round\RoundsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para generar salas de bingo automáticas.
 * Totalmente desacoplado para testeo y mantenibilidad.
 */
final class AutoGenerateBingoRoomsService
{
    public function __construct(
        protected RoomConfigService $roomConfigService,
        protected CartonPriceService $cartonPriceService,
        protected RoundsService $roundsService
    ) {}

    /**
     * Crea todas las salas automáticas activas.
     *
     * @param array|string|null $creator Filtro opcional por creador.
     * @return bool
     */
    public function createBingoRooms(?array $creator = null): bool
    {
        $automaticRooms = $this->getAutomaticRooms($creator);

        if ($automaticRooms->isEmpty()) {
            Log::info('No hay configuraciones activas para salas automáticas.');
            return false;
        }

        return $this->processRoomsTransaction($automaticRooms);
    }

    /**
     * Obtiene las salas automáticas activas, filtrando por creador si se proporciona.
     *
     * @param array|string|null $creator
     * @return \Illuminate\Support\Collection
     */
    protected function getAutomaticRooms(?array $creator = null): Collection
    {
        $query = BingoRoomAutomatic::query()->where('status', 1);

        if ($creator) {
            $query->where('creator_type', $creator['type'])
                ->where('creator_id', $creator['id']);
        }

        return $query->get();
    }

    /**
     * Procesa la creación de varias salas en una transacción.
     *
     * @param \Illuminate\Support\Collection $rooms
     * @return bool
     */
    protected function processRoomsTransaction($rooms): bool
    {
        DB::beginTransaction();
        try {
            foreach ($rooms as $room) {
                $this->processRoom($room);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear salas automáticas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Procesa la creación de una sola sala, incluyendo configuración,
     * precios de cartones y rondas si aplica.
     *
     * @param \App\Models\BingoRoomAutomatic $room
     */
    protected function processRoom(BingoRoomAutomatic $room): void
    {
        // Crear o actualizar la sala principal
        $currentRoom = $this->roomConfigService->createOrUpdateRoom($room);

        if (!$currentRoom) {
            return;
        }

        // Asociar precios de cartones
        $this->cartonPriceService->associateCtnPrices($room->id, $currentRoom->id);

        // Asociar rondas si la sala tiene más de una y porcentaje definido
        if ($room->rounds > 1 && $room->rounds_pct) {
            $this->roundsService->associateRounds($room->id, $currentRoom->id);
        }
    }
}
