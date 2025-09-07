<?php

namespace App\Http\Controllers;

use App\Models\Rewards;
use App\Traits\Users\UserValTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardsController extends Controller
{
    use UserValTrait;

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
        $name = 'rewards';

        $creator = ['type' => $creatorType, 'id' => encrypt($creatorId)];

        $rewards = Rewards::getAllRewardsByCreator($creatorUser);

        $rewardTypes = Rewards::REWARD_TYPE;
        $optionsKit = ['N' => 'NO', 'Y' => 'SI'];

        $additionalData = [];

        return view('modules.all.rewards.index', array_merge(
            compact('title', 'name', 'creator', 'rewards', 'rewardTypes'),
            $additionalData
        ));
    }

    private function generateRewardCode()
    {
        return '1';
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {

            $rewardCode = $this->generateRewardCode();

            Rewards::create([
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
            Log::error('Error al registrar premio (Rewards): ' . $e->getMessage());
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

            $reward = Rewards::findOrFail(decrypt($id));

            $reward->update([
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

            $reward = Rewards::findOrFail($id);

            $status = $reward->status == 3 ? 1 : (int)!$reward->status;

            $reward->update(['status' => $status]);

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
            Rewards::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro de Rewards: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return Rewards::getById(decrypt($id), ['code', 'reward_type', 'name', 'num_items', 'price', 'description']);
    }
}
