<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\OtherTreasurers;

use App\Traits\Users\UserValTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtherTreasurersController extends Controller
{
    use UserValTrait;

    public function getInfo($id) // Obtener Info 
    {
        $id = decrypt($id);

        $otherTreasurer = OtherTreasurers::with(['User:id,username,country', 'User.Country:id,name'])
            ->select('creator_user_id', 'allowed_games', 'allowed_currencies')->where('id', $id)->first();

        $data = [
            'username' => $otherTreasurer->User->username,
            'country' => $otherTreasurer->User->Country->name,
            'other_currencies' => $otherTreasurer->allowedCurrencies,
            'allowed_games' => $otherTreasurer->allowed_games
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id) // Editar Comercializadora
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        DB::beginTransaction();

        try {

            // Decryptar el ID y encontrar el tesorero
            $otherTreasurer = OtherTreasurers::findOrFail(decrypt($id));

            if ($otherTreasurer) {

                $data = [];

                // Verificar cambios
                if ($request->allowed_games && ($request->allowed_games !== $otherTreasurer->allowed_games)) {
                    $data['allowed_games'] = $request->allowed_games;
                }

                if ($request->other_currencies !== $otherTreasurer->allowed_currencies) {
                    $data['allowed_currencies'] = $request->other_currencies;
                }

                // Actualizar si hay datos para cambiar
                if (!empty($data)) {
                    $otherTreasurer->update($data);
                    DB::commit();
                    Session::flash('message', 'La información fue actualizada');
                } else {
                    Session::flash('message', 'No hubo cambios');
                    Session::flash('type', 'info');
                }
            } else {
                // Manejar caso donde no se encuentra el vendedor
                Session::flash('message', 'No se encontró el vendedor');
                Session::flash('type', 'warning');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar la informacion del (other) Tesorero: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function delete($id)
    {
        OtherTreasurers::where('id', decrypt($id))->update(['status' => 3]);
        Session::flash('message', 'El Tesorero fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        $otherTreasurerStatus = (int)!OtherTreasurers::getValueById($id, 'status');
        OtherTreasurers::where('id', $id)->update(['status' => $otherTreasurerStatus]);

        Session::flash('message', 'Status actualizado');
        return redirect()->back()->with('type', 'success');
    }
}
