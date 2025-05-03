<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use Illuminate\Support\Facades\Session;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Games;
use App\Models\TimeZone;
use App\Models\User;
use App\Traits\EncryptRKE;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    use UserVal;
    use EncryptRKE;

    public function commerces()
    {
        $commerces = Commerce::with(['Country', 'User' => function ($query) {
            $query->select('id', 'email');
        }])->get();

        $countries = Country::getAllAvailableCountries();
        $coins = Coin::getByIdForStatus(['=', 1]);
        $timeZones = TimeZone::where('status', 1)->get();

        return view('modules/admin/commerce/index')->with([
            'title' => 'Comercializadoras',
            'name' => 'admin-commerce',
            'commerces' => $commerces,
            'countries' => $countries,
            'timeZones' => $timeZones,
            'coins' => $coins,
            'games' => Games::where('status', 1)->get()
        ]);
    }

    public function customers()
    {
        $customers = Customer::with(['Country', 'User' => function ($query) {
            $query->select('id', 'email');
        }])->get();

        $countries = Country::getAllAvailableCountries();
        $coins = Coin::getByIdForStatus(['=', 1]);
        $timeZones = TimeZone::where('status', 1)->get();

        return view('modules/admin/customer/index')->with([
            'title' => 'Clientes',
            'name' => 'admin-customer',
            'customers' => $customers,
            'countries' => $countries,
            'timeZones' => $timeZones,
            'coins' => $coins
        ]);
    }

    public function promoteUserToCommerce(Request $request)
    { // Convierte un usuario ya registrado en Comercializadora
        $codeRKE = $request->code_rke;
        $existsCodeRKE = Commerce::existsValue('code_rke', $codeRKE);

        if ($existsCodeRKE) {
            return redirect()->back()->with('message', 'El código RKE ya existe')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            Commerce::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'coin' => $request->coin,
                'country' => $request->country,
                'status' => 1,
                'user' => $request->user_id,
                'code_rke' => $this->replaceLettersWithNumbers($codeRKE),
                'code_uuid' => $this->getCodeUUID('Commerce')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al promover usuario a tipo comercializadora: ' . $e->getMessage());
        }
    }
}
