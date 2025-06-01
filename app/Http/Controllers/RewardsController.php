<?php

namespace App\Http\Controllers;

use App\Models\Rewards;
use App\Models\UserType;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardsController extends Controller
{
    use UserVal;

    public function index()
    {
        $userData = $this->getUserData();

        $creatorType = $userData['type'];
        $creatorID = $userData['id'];
        $creatorUser = $userData['userId'];

        if (in_array($creatorType, ['seller'])) {
            return redirect()->back();
        }

        $title = 'Premios';
        $name = 'rewards';

        $creator = ['type' => $creatorType, 'id' => encrypt($creatorID)];

        $rewards = Rewards::getAllRewardsByCreator($creatorUser);

        $optionsProductService = ['P' => 'Producto', 'S' => 'Servicio'];
        $optionsKit = ['N' => 'NO', 'Y' => 'SI'];

        $additionalData = [];

        return view('modules.all.rewards.index', array_merge(
            compact('title', 'name', 'creator', 'rewards', 'optionsProductService'),
            $additionalData
        ));
    }

    public function create(Request $request)
    {
        $accountType = $request->account_type;
        $dataValue = $request->data_value;

        DB::beginTransaction();

        try {
            if (Rewards::existsAccount($accountType, $dataValue)) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            Rewards::create([
                'account_type' => $accountType,
                'data_value' => $dataValue,
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
            Log::error('Ha ocurrido un error al registrar datos de Binance: ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Ocurrió un error',
                'type' => 'warning',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $accountType = $request->account_type;
        $dataValue = $request->data_value;

        DB::beginTransaction();

        try {
            if (Rewards::existsAccount($accountType, $dataValue, Auth::id())) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            $account = Rewards::findOrFail(decrypt($id));

            $account->update(['account_type' => $accountType, 'data_value' => $dataValue]);

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

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            Rewards::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro de cuenta Binance: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return Rewards::getById(decrypt($id), ['account_type', 'data_value']);
    }
}
