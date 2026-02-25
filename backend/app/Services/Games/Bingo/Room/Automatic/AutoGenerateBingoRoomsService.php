<?php

namespace App\Services\Games\Bingo\Room\Automatic;

use App\Models\Games\Bingo\Card\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\BingoRoomAutomatic;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Models\Games\Bingo\Round\BingoRound;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Genera salas de bingo reales a partir de las plantillas automáticas activas.
 *
 * Flujo:
 *  1. Obtiene las plantillas ACTIVE cuyo campo `days` incluye el día actual.
 *  2. Filtra las que tienen una hora programada dentro de la ventana de tiempo
 *     (próximas N minutos desde ahora).
 *  3. Por cada plantilla, crea un BingoRoom con su BingoRoomConfig, precios de
 *     cartón (BingoCardPrice) y rondas (BingoRound), usando los datos de las
 *     relaciones cardPriceAutomatic y roundAutomatic.
 */
final class AutoGenerateBingoRoomsService
{
    /**
     * Ventana de tiempo (en minutos) para considerar que una sala debe generarse ahora.
     * El scheduler corre cada hora, así que 60 min es la ventana lógica.
     */
    private const TIME_WINDOW_MINUTES = 60;

    /**
     * Crea las salas de bingo reales a partir de las plantillas automáticas.
     *
     * @param array|null $creator  Filtro opcional: ['type' => ..., 'id' => ...]
     * @return bool
     */
    public function createBingoRooms(?array $creator = null): bool
    {
        $templates = $this->getActiveTemplates($creator);

        if ($templates->isEmpty()) {
            Log::info('[AutoGenerateBingoRooms] No hay plantillas activas para hoy.');
            return false;
        }

        return $this->processTemplates($templates);
    }

    /**
     * Obtiene plantillas ACTIVE para el día de hoy cuya hora cae en la
     * ventana de tiempo actual.
     */
    private function getActiveTemplates(?array $creator): Collection
    {
        $now       = Carbon::now('America/Caracas');
        $dayName   = strtolower($now->englishDayOfWeek); // 'monday', 'tuesday', ...
        $windowEnd = $now->copy()->addMinutes(self::TIME_WINDOW_MINUTES);

        $query = BingoRoomAutomatic::query()
            ->with(['cardPriceAutomatic', 'roundAutomatic'])
            ->where('status', BingoRoomAutomatic::STATUS_ACTIVE)
            ->whereJsonContains('days', $dayName)
            ->whereTime('time', '>=', $now->format('H:i:s'))
            ->whereTime('time', '<=', $windowEnd->format('H:i:s'));

        if ($creator) {
            $query->where('creator_type', $creator['type'])
                  ->where('creator_id', $creator['id']);
        }

        return $query->get();
    }

    /**
     * Procesa todas las plantillas dentro de una transacción.
     */
    private function processTemplates(Collection $templates): bool
    {
        DB::beginTransaction();
        try {
            foreach ($templates as $template) {
                $this->generateRoomFromTemplate($template);
            }
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[AutoGenerateBingoRooms] Error al generar salas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Genera un BingoRoom real a partir de una plantilla automática.
     */
    private function generateRoomFromTemplate(BingoRoomAutomatic $template): void
    {
        // 1. Crear la sala principal
        $room = BingoRoom::create([
            'status_id'       => 'DRAFT',
            'creator_user_id' => $template->creator_user_id,
            'rke_commission'  => $template->rke_commission,
            'room_commission' => $template->room_commission,
        ]);

        // 2. Crear configuración de sala
        $scheduledDate = Carbon::now('America/Caracas')->format('Y-m-d');
        $scheduledTime = $template->time ? $template->time->format('H:i:s') : null;
        $unixDate      = $scheduledTime
            ? Carbon::createFromFormat('Y-m-d H:i:s', "$scheduledDate $scheduledTime", 'America/Caracas')->timestamp
            : null;

        BingoRoomConfig::create([
            'room_id'                 => $room->id,
            'room_type_id'            => $template->room_type_id,
            'creator_user_id'         => $template->creator_user_id,
            'game_mode_id'            => $template->game_mode,
            'in_live'                 => $template->in_live ?? 'N',
            'date'                    => $scheduledDate,
            'time'                    => $scheduledTime,
            'unix_date'               => $unixDate,
            'rke_commission'          => $template->rke_commission,
            'room_commission'         => $template->room_commission,
            'allowed_currencies'      => $template->allowed_currencies,
            'prize_pool_type_id'      => $template->prize_pool_type,
            'amount_added_prize_pool' => $template->amount_added_prize_pool,
            'time_chip'               => $template->time_chip,
            'rounds'                  => $template->rounds,
            'rounds_pct'              => $template->rounds_pct,
            'serials_pct'             => $template->serials_pct,
            'num_serials'             => $template->num_serials,
            'jackpot_pct'             => $template->jackpot_pct,
            'plays_pct'               => $template->plays_pct,
        ]);

        // 3. Crear precios de cartones por moneda
        $this->createCardPrices($room, $template);

        // 4. Crear rondas
        $this->createRounds($room, $template);

        Log::info('[AutoGenerateBingoRooms] Sala generada.', [
            'room_id'     => $room->id,
            'template_id' => $template->id,
        ]);
    }

    /**
     * Crea los registros de precio de cartón a partir del automatic draft.
     */
    private function createCardPrices(BingoRoom $room, BingoRoomAutomatic $template): void
    {
        $draft = $template->cardPriceAutomatic;

        if (!$draft || empty($draft->card_prices)) {
            return;
        }

        foreach ($draft->card_prices as $currencyId => $price) {
            if ($price === null || $price === '') {
                continue;
            }
            BingoCardPrice::create([
                'room_id'         => $room->id,
                'creator_user_id' => $template->creator_user_id,
                'currency_id'     => $currencyId,
                'card_price'      => $price,
            ]);
        }
    }

    /**
     * Crea los registros de ronda a partir del automatic draft.
     */
    private function createRounds(BingoRoom $room, BingoRoomAutomatic $template): void
    {
        $draft = $template->roundAutomatic;

        if (!$draft) {
            return;
        }

        $totalRounds  = $template->rounds ?? 1;
        $prizeTypes   = $draft->prize_types   ?? [];
        $patterns     = $draft->bingo_patterns ?? [];
        $prizeValues  = $draft->prize_values   ?? [];

        for ($i = 0; $i < $totalRounds; $i++) {
            BingoRound::create([
                'room_id'          => $room->id,
                'round'            => $i + 1,
                'bingo_pattern_id' => $patterns[$i] ?? null,
                'prize_type'       => $prizeTypes[$i] ?? null,
                'prize_value'      => $prizeValues[$i] ?? null,
                'status'           => 'PENDING',
            ]);
        }
    }
}
