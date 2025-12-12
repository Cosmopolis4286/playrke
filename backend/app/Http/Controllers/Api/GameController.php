<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\Games\Shared\Game;
use App\Models\Users\Roles\Customer\CustomerGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    public function handleCustomerGame(Request $request, $id = null)
    {
        DB::beginTransaction();

        try {
            // Verificar si existe un registro con el ID proporcionado
            $customerGame = CustomerGame::find($id);

            // Obtener ID del juego
            $gameId = Game::getValueByName($request->game_id);

            if ($customerGame) {
                // Actualizar el registro existente
                $customerGame->update([
                    'customer_game_id' => $request->customer_game_id,
                    'product' => $request->customer_game_product,
                    'status' => $request->status
                ]);
            } else {
                // Crear un nuevo registro
                CustomerGame::create([
                    'customer' => decrypt($request->customer),
                    'game' => $gameId,
                    'customer_game_id' => $request->customer_game_id,
                    'product' => $request->customer_game_product
                ]);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Proceso exitoso')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear o actualizar registro en CustomerGame: " . $e->getMessage());
            return redirect()->back()->with('message', 'Ha ocurrido un error')->with('type', 'warning');
        }
    }
}
