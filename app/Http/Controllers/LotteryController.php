<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Commerce;
use App\Models\Customer;
use App\Models\Lottery;
use App\Models\User;
use App\Models\UserType;
use App\Traits\Filters;
use App\Traits\UserVal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LotteryController extends Controller
{
    use UserVal, Filters;

    public function index($userToken = false)
    {
        if ($userToken) {
            $userType = 'customer';
            $userToken = true;
            $user = Customer::getValueByToken($userToken, 'user');
        } else {
            $user = Auth::id();
            $userTypeID = User::getValueById($user, 'user_type');
            $userType = UserType::getValueById($userTypeID, 'prefix');
        }

        if (!in_array($userType, ['admin', 'commerce', 'customer'])) {
            return redirect()->back();
        }

        $lotteries = Lottery::all();

        $schedules = Lottery::SCHEDULES;
        $numbers = Lottery::NUMBERS;

        $viewData = [
            'layout' => !$userToken ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Loterias',
            'userToken' => $userToken,
            'name' => 'lottery',
            'lotteries' => $lotteries,
            'schedules' => $schedules,
            'numbers' => $numbers
        ];

        $viewData = ['route' => 'modules.game.lottery.index', 'data' => $viewData];

        return $this->handleViewData($viewData, $userType);
    }

    public function create(Request $request) /// Registar 
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        $codeRKE = $request->code_rke;
        $existsCodeRKE = Commerce::existsValue('code_rke', $codeRKE);

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            Lottery::create([
                'name' => $request->name,
                // 'code' => $request->code,
                'numbers' => $request->numbers,
                'schedules' => $request->schedules,
                // 'logo' => $request->logo
            ]);

            DB::commit();

            Session::flash('message', 'Registro exitoso');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error general: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function getInfo($id) // Obtener Info 
    {
        $id = decrypt($id);

        $lottery = Lottery::where('id', $id)->first();

        $schedules = $lottery->schedules;

        $data = [
            'name' => $lottery->name,
            'numbers' => $lottery->numbers,
            'schedules' => $schedules
        ];

        return response()->json($data);
    }

    public function update(Request $request, $id) // Editar 
    {
        if ($this->user(['user_type'])->user_type != 2) {
            return redirect()->back()->withErrors(['error' => 'No tienes permiso para realizar esta acción.']);
        }

        DB::beginTransaction();

        try {
        } catch (\Exception $e) {
            DB::rollBack();
            // Captura cualquier excepción lanzada durante el proceso de actualización
            Session::flash('error', 'Hubo un problema al actualizar la información del vendedor: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function delete($id)
    {
        Lottery::where('id', decrypt($id))->update(['status' => 3]);
        Session::flash('message', 'El resgistro fue eliminado');
        return redirect()->back();
    }

    public function changeStatus($id)
    {
        $id = decrypt($id);
        $lotteryStatus = (int)!Lottery::getValueById($id, 'status');
        Lottery::where('id', $id)->update(['status' => $lotteryStatus]);

        Session::flash('message', 'Status actualizado');
        return redirect()->back()->with('type', 'success');
    }
}
