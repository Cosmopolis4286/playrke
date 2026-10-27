<?php

namespace App\Http\Controllers\Directories\Crypto;

use App\Contracts\Directories\Crypto\BinanceAccountsRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Directories\Crypto\Binance\BinanceAccount;
use App\Traits\Users\UserTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinanceController extends Controller
{
    use UserTrait;

    public function __construct(
        private readonly BinanceAccountsRepositoryInterface $binanceAccounts,
    ) {
    }

    public function create(Request $request)
    {
        $accountType = $request->account_type;
        $dataValue = $request->data_value;

        DB::beginTransaction();

        try {
            if ($this->binanceAccounts->existsAccount($accountType, $dataValue)) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            $this->binanceAccounts->create([
                'account_type' => $accountType,
                'data_value' => $dataValue,
                'user_id' => Auth::id(),
                'status' => 'ACTIVE',
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro con éxito',
                'type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ha ocurrido un error al registrar datos de Binance: ' . $e->getMessage());

            return redirect()->back()->with([
                'message' => 'Ocurrió un error',
                'type' => 'warning',
            ]);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            $accountId = decrypt($request->id);
            $account = $this->binanceAccounts->find($accountId);

            if (! $account || (string) $account->user_id !== (string) Auth::id()) {
                abort(404);
            }

            $this->binanceAccounts->delete($accountId);
            DB::commit();

            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar registro de cuenta Binance: ' . $e->getMessage());

            return redirect()->back()->with('message', 'Ha ocurrido un error al eliminar el registro')->with('type', 'warning');
        }
    }

    public function getInfo($id)
    {
        $accountId = decrypt($id);
        $account = $this->binanceAccounts->find($accountId, ['id', 'user_id', 'account_type', 'data_value']);

        if (! $account || (string) $account->user_id !== (string) Auth::id()) {
            abort(404);
        }

        return $account->only(['account_type', 'data_value']);
    }

    public function index()
    {
        $accounts = $this->binanceAccounts->getByUserId(Auth::id());
        $accountTypes = BinanceAccount::getAccountTypes();

        return view('all.directory.usdt.index', compact('accounts', 'accountTypes'));
    }

    public function update(Request $request, $id)
    {
        $accountType = $request->account_type;
        $dataValue = $request->data_value;

        DB::beginTransaction();

        try {
            if ($this->binanceAccounts->existsAccount($accountType, $dataValue, Auth::id())) {
                return redirect()->back()->with([
                    'message' => 'Estos datos ya se encuentran registrados',
                    'type' => 'warning',
                ]);
            }

            $accountId = decrypt($id);
            $account = $this->binanceAccounts->find($accountId);

            if (! $account || (string) $account->user_id !== (string) Auth::id()) {
                abort(404);
            }

            $this->binanceAccounts->update($accountId, [
                'account_type' => $accountType,
                'data_value' => $dataValue,
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ha ocurrido un error al actulizar los datos de Binance: ' . $e->getMessage());

            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar',
                'type' => 'warning',
            ]);
        }
    }
}
