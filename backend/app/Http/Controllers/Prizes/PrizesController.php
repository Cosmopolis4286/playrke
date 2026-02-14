<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Traits\Users\UserTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrizesController extends Controller
{
    use UserTrait;

    public function index()
    {
        $userData = $this->getUserData();

        $creatorType = $userData['type'];
        $creatorId = $userData['id'];
        $creatorUser = $userData['userId'];

        if (in_array($creatorType, ['seller'])) {
            return redirect()->back();
        }

        $title = 'Premios';
        $name = 'prizes';

        $creator = ['type' => $creatorType, 'id' => encrypt($creatorId)];

        $prizes = Prize::getAllPrizesByCreator($creatorUser);

        $rewardTypes = Prize::REWARD_TYPE;
        $optionsKit = ['N' => 'NO', 'Y' => 'SI'];

        $additionalData = [];

        return view('modules.all.prizes.index', array_merge(
            compact('title', 'name', 'creator', 'prizes', 'rewardTypes'),
            $additionalData
        ));
    }

    private function generatePrizeCode()
    {
        return '1';
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {

            $rewardCode = $this->generatePrizeCode();

            Prize::create([
                'code' => $rewardCode,
                'reward_type' => $request->reward_type,
                'name' => $request->name,
                'num_items' => $request->num_items,
                'price' => $request->price,
                'description' => $request->description,
                'user' => Auth::id(),
                'status' => 1
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar premio (Prizes): ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Ocurrió un error',
                'type' => 'warning',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $prize = Prize::findOrFail(decrypt($id));

            $prize->update([
                'reward_type' => $request->reward_type,
                'name' => $request->name,
                'num_items' => $request->num_items,
                'price' => $request->price,
                'description' => $request->description
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ha ocurrido un error al actulizar los datos de Binance: ' . $e->getMessage());

            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar',
                'type' => 'warning',
            ]);
        }
    }

    public function changeStatus($id)
    {
        DB::beginTransaction();

        try {

            $id = decrypt($id);

            $prize = Prize::findOrFail($id);

            $status = $prize->status == 3 ? 1 : (int)!$prize->status;

            $prize->update(['status' => $status]);

            DB::commit();

            return redirect()->back()->with([
                'type' => 'success',
                'message' => 'Status actualizado'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with([
                'type' => 'error',
                'message' => 'Error al actualizar el estado'
            ]);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            Prize::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro de Prizes: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return Prize::getById(decrypt($id), ['code', 'reward_type', 'name', 'num_items', 'price', 'description']);
    }
}
