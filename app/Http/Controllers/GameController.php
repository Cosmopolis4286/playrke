<?php

namespace App\Http\Controllers;

use App\Models\CustomerGames;
use App\Http\Controllers\Controller;
use App\Models\Games;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    public function handleCustomerGames(Request $request, $id = null)
    {
        DB::beginTransaction();

        try {
            // Verificar si existe un registro con el ID proporcionado
            $customerGame = CustomerGames::find($id);

            // Obtener ID del juego
            $gameID = Games::getValueByName($request->game_id);

            if ($customerGame) {
                // Actualizar el registro existente
                $customerGame->update([
                    'customer_game_id' => $request->customer_game_id,
                    'status' => $request->status
                ]);
            } else {
                // Crear un nuevo registro
                CustomerGames::create([
                    'customer' => decrypt($request->customer),
                    'game' => $gameID,
                    'customer_game_id' => $request->customer_game_id,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Proceso efectuado con éxito')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear o actualizar registro en CustomerGames: " . $e->getMessage());
            return redirect()->back()->with('message', 'Hubo un problema al procesar el registro: ' . $e->getMessage())->with('type', 'warning');
        }
    }
}
