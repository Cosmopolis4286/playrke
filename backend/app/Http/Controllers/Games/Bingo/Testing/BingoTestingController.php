<?php

namespace App\Http\Controllers\Games\Bingo\Testing;

use App\DTOs\Games\Bingo\Card\BingoCardGenerateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\Bingo\Card\BingoCardGenerateRequest;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Services\Games\Bingo\Card\BingoCardGenerateService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del juego de Bingo.
 *
 * Responsabilidades:
 * - Orquestar requests HTTP
 * - Delegar lógica de negocio al servicio
 * - Unificar respuestas JSON
 *
 * NO contiene lógica de dominio ni mensajes de error.
 */
final class BingoTestingController extends Controller
{
    /**
     * @param BingoRoomService $service
     */
    public function __construct(
        private readonly BingoCardGenerateService $generateService,
    ) {
        $this->authorizeResource(BingoRoom::class, 'room');
    }

    /**
     * Generar cartones de bingo para un jugador (rol seller).
     *
     * @param BingoCardGenerateRequest $request
     * @return JsonResponse
     */
    public function generateCard(BingoCardGenerateRequest $request): JsonResponse
    {
        return $this->handleServiceJson(
            fn() => $this->generateService->generate(
                $request->toDTO(BingoCardGenerateDTO::class)
            )
        );
    }

/**
     * Generar cartones de bingo para un jugador (rol seller).
     *
     * @param BingoCardGenerateRequest $request
     * @return JsonResponse
     */
    public function regenerateDraw(Request $request)
    {
        // FUNCION PARA REGENERAR SORTEO - USAR SOLO PARA TESTES

        $roomEncrypted = $request->room;
        $room = BingoRoom::getValueByEncrypted($roomEncrypted, 'id');
        $keyword = $request->keyword;

        if (!$room) {
            return response()->json([
                'type' => 'error',
                'message' => 'Sala no encontrada'
            ]);
        }

        if (BingoDraw::where('room', $room)->doesntExist()) {
            $this->handleDraw($room);

            return response()->json([
                'type' => 'success',
                'message' => 'Proceso efectuado con éxito'
            ]);
        }

        if ($keyword !== 'RK3_T357E*2K25') {
            return response()->json([
                'type' => 'warning',
                'message' => 'Ha ocurrido un error en la solicitud'
            ]);
        }

        date_default_timezone_set(config('app.timezone'));

        // Resetear estados de las rondas
        BingoRound::where('room', $room)->update(['status' => 'ACTIVE']);

        // Obtener símbolos
        $symbols = BingoDrawCode::where('room', $room)
            ->pluck('sym', 'num');

        if ($symbols->isEmpty()) {
            $this->encryptDraw($room);
            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
        }

        $roundsCount = BingoRoomConfig::getValueByRoom($room, 'rounds');
        $currentTimestamp = now()->addMinutes(1)->timestamp;

        $chipsUsed = [];
        $updates = [];

        for ($round = 1; $round <= $roundsCount; $round++) {

            $draws = BingoDraw::where('room', $room)->where('round', $round)->get();

            // Resetear ficha ganadora anterior
            BingoDraw::where('room', $room)
                ->where('round', $round)
                ->where('status', 3)
                ->update(['status' => 0]);

            $timeIncrement = 0;

            foreach ($draws as $index => $draw) {
                do {
                    $rnd = mt_rand(1, 75);
                } while (isset($chipsUsed[$rnd]));

                $chipsUsed[$rnd] = true;

                $numEncoded = collect(str_split($rnd))
                    ->map(fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '')
                    ->implode('');

                $updates[] = [
                    'id' => $draw->id,
                    'chip' => $rnd,
                    'code' => $numEncoded,
                    'time' => $currentTimestamp + ($index * $timeIncrement),
                    'round' => $round,
                    'updated_at' => now()
                ];

                $timeIncrement = 5;
            }
        }

        DB::beginTransaction();

        try {
            // Resetear ganadores y eliminar premios
            BingoRoomUser::where('room', $room)->where('winner', '1')->update(['winner' => '0']);
            BingoPrizeCtnsPayment::where('room', $room)->delete();
            BingoPrizeCtns::where('room', $room)->delete();

            // Actualizar sorteos
            foreach ($updates as $update) {
                BingoDraw::where('id', $update['id'])->update([
                    'chip' => $update['chip'],
                    'code' => $update['code'],
                    'time' => $update['time'],
                    'round' => $update['round'],
                    'updated_at' => $update['updated_at'],
                ]);
            }

            // Ejecutar ficha ganadora
            // $this->registerWinningChips($room);

            // Actualizar estado de la sala
            BingoRoom::where('id', $room)->update([
                'status' => 3,
                'paid_win' => 0,
                'full_house_prize' => BingoRoom::getValueById($room, 'prize_pool'),
                'serial_prize' => 0,
                'close_room' => null
            ]);

            DB::commit();

            return response()->json([
                'type' => 'success',
                'message' => 'Proceso efectuado con éxito'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al regenerar sorteo [$room]: " . $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'Ha ocurrido un error'
            ]);
        }
    }
}
