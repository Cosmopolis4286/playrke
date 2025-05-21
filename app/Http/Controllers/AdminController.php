<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Games;
use App\Models\TimeZone;
use App\Models\Treasurer;
use App\Traits\EncryptRKE;
use App\Traits\UserVal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    use UserVal;
    use EncryptRKE;

    public function commerces()
    {
        $commerces = Commerce::with(['Country:id,name', 'User:id,email'])->get();

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
        $customers = Customer::with(['Country:id,name', 'User:id,email'])->get();

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

    public function treasurers()
    {
        $treasurers = Treasurer::with(['Country:id,name', 'User:id,email'])->get();

        $countries = Country::getAllAvailableCountries();
        $coins = Coin::getByIdForStatus(['=', 1]);
        $timeZones = TimeZone::where('status', 1)->get();

        return view('modules/admin/treasurer/index')->with([
            'title' => 'Tesoreros',
            'name' => 'admin-treasurer',
            'treasurers' => $treasurers,
            'countries' => $countries,
            'timeZones' => $timeZones,
            'coins' => $coins,
            'games' => Games::where('status', 1)->get()
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

    public function getUserInfo($userRole)
    {
        if ($userRole === 'admin') {
            $data = [];
        } else if ($userRole === 'commerce') {
            $data = Commerce::getActiveCommerces(['name', 'user']);
        }

        return $data;
    }
}
