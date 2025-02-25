<?php

namespace App\Http\Controllers;

use App\Models\BinanceAccounts;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinanceController extends Controller
{
    use UserVal;

    public function index()
    {
        $wallet = $this->wallet();

        if ($wallet->coin != 2) return redirect()->back();

        $accounts = BinanceAccounts::with('Bank')->where("user", Auth::id())->get();
        $accountTypes = BinanceAccounts::getAccountTypes();

        return view('all.directory.usdt.index', compact('wallet', 'accounts', 'accountTypes'));
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            if (BinanceAccounts::existsAccount($pixKey, $pixKeyVal)) {
                return redirect()->back()->with([
                    'message' => 'Esta Chave Pix ya se encuentra registrada',
                    'type' => 'warning',
                ]);
            }

            BinanceAccounts::create([
                'name' => $request->name,
                'bank' => $bankID,
                'pix_key' => $request->pix_key,
                'pix_key_value' => $request->pix_key_value,
                'user' => Auth::id()
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Pix registrado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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

            $bankID = $this->insertNewBank($request->arr_bank);

            $pixKey = $request->pix_key;
            $pixKeyVal = $request->pix_key_value;

            if (BinanceAccounts::existsAccount($pixKey, $pixKeyVal, Auth::id())) {
                return redirect()->back()->with([
                    'message' => 'Esta Chave Pix ya se encuentra registrada',
                    'type' => 'warning',
                ]);
            }

            $pix = BinanceAccounts::findOrFail(decrypt($id));

            $pix->update([
                'name' => $request->name,
                'bank' => $bankID,
                'pix_key' => $pixKey,
                'pix_key_value' => $pixKeyVal
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Pix actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar el Pix. Inténtalo de nuevo.',
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
            return redirect()->back()->with('message', 'Esta Chave Pix ha sido eliminada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar Pix: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return BinanceAccounts::getById(decrypt($id), ['name', 'bank', 'pix_key', 'pix_key_value']);
    }
}
