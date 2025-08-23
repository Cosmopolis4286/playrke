<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Games;
use App\Models\OtherTreasurers;
use App\Models\TimeZone;
use App\Models\Treasurer;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Users\UserValTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    use UserValTrait, EncryptTrait;

    /**
     * Obtiene la vista de comercializadoras con datos relacionados.
     *
     * @return \Illuminate\View\View
     */
    public function commerces()
    {
        $coins = Coin::getByIdForStatus(['=', 1]);
        $commerces = Commerce::with(['Country:id,name', 'User:id,email'])->get();
        $countries = Country::getAllAvailableCountries();
        $games = Games::getAvailableGames();
        $timeZones = TimeZone::where('status', 1)->get();

        return view('modules.admin.commerce.index', [
            'title' => 'Comercializadoras',
            'pageData' => [
                'coins' => $coins,
                'commerces' => $commerces,
                'countries' => $countries,
                'games' => $games,
                'moduleFile' => 'views/modules/admin/commerce/form/commerceForm.ts',
                'timeZones' => $timeZones,
            ]
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
            'others' => false,
            'name' => 'admin-treasurer',
            'treasurers' => $treasurers,
            'countries' => $countries,
            'timeZones' => $timeZones,
            'coins' => $coins,
            'games' => Games::getAvailableGames()
        ]);
    }

    public function otherTreasurers()
    {
        $otherTreasurers = OtherTreasurers::with([
            'User:id,user_type,country,username',
            'User.Country:id,name'
        ])->get();

        $coins = Coin::getByIdForStatus(['=', 1]);
        $games = Games::getAvailableGames();

        return view('modules/admin/treasurer/other-treasurers')->with([
            'title' => 'Otros Tesoreros',
            'others' => true,
            'name' => 'admin-other-treasurers',
            'otherTreasurers' => $otherTreasurers,
            'coins' => $coins,
            'games' => $games
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

    public function getUserInfo($userRole, $exclude = '')
    {
        $creatorUserToExclude = [];

        // Obtener lista de usuarios a excluir si se proporciona un modelo
        if (!empty($exclude)) {
            $model = app("App\\Models\\$exclude");

            if (method_exists($model, 'getAllCreatorUser')) {
                $creatorUserToExclude = $model::getAllCreatorUser();
            }
        }

        // Si es admin, retorna arreglo vacío
        if ($userRole === 'admin') {
            return [];
        }

        // Si es comercio, obtiene la lista de comercios activos
        if ($userRole === 'commerce') {
            $query = Commerce::where('status', 1);

            if (!empty($creatorUserToExclude)) {
                $query->whereNotIn('user', $creatorUserToExclude);
            }

            return $query->get(['name', 'user']);
        }

        // Por defecto retorna vacío si no cumple ninguna condición
        return [];
    }
}
