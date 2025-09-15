<?php

namespace App\Http\Controllers;

use App\Models\BinanceAccounts;
use App\Traits\Users\UserTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinanceController extends Controller
{
    use UserTrait;

    public function index()
    {
        $accounts = BinanceAccounts::where("user", Auth::id())->get();
        $accountTypes = BinanceAccounts::getAccountTypes();

        return view('all.directory.usdt.index', compact('accounts', 'accountTypes'));
    }

    public function create(Request $request)
    {
        $accountType = $request->account_type;
        $dataValue = $request->data_value;

        DB::beginTransaction();

        try {
            if (BinanceAccounts::existsAccount($accountType, $dataValue)) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            BinanceAccounts::create([
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
            if (BinanceAccounts::existsAccount($accountType, $dataValue, Auth::id())) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            $account = BinanceAccounts::findOrFail(decrypt($id));

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
            BinanceAccounts::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro de cuenta Binance: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return BinanceAccounts::getById(decrypt($id), ['account_type', 'data_value']);
    }
}
