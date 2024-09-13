<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Coin;
use Illuminate\Support\Facades\Session;
use App\Models\Commerce;
use App\Models\Country;
use App\Models\Customer;
use App\Models\User;
use App\Traits\EncryptRKE;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        return view('modules/admin/commerce/index')->with([
            'title' => 'Comercializadoras',
            'name' => 'commerce',
            'wallet' => $this->Wallet(Auth::id()),
            'commerces' => $commerces,
            'countries' => $countries,
            'coins' => $coins
        ]);
    }

    public function customers()
    {
        $customers = Customer::with(['Country', 'User' => function ($query) {
            $query->select('id', 'email');
        }])->get();

        $countries = Country::getAllAvailableCountries();
        $coins = Coin::getByIdForStatus(['=', 1]);

        return view('modules/admin/customer/index')->with([
            'title' => 'Clientes',
            'name' => 'customer',
            'wallet' => $this->Wallet(Auth::id()),
            'customers' => $customers,
            'countries' => $countries,
            'coins' => $coins
        ]);
    }

    public function storeCommerce(Request $request)
    {
        $user = $this->user();
        if ($user->user_type == 2) {

            $newPassw = $this->valEncrypt();

            $newUser =  User::create([
                'username' => null,
                'email' => $request->email,
                'password' => Hash::make($newPassw),
                'country' => $request->country,
                'referral_code' => User::getUniqueReferralCode(),
                'referred_by' => null,
            ]);

            Commerce::create([
                'description' => $request->description,
                'com' => $request->valCom,
                'telefono' => $request->telefono,
                'country' => $request->country,
                'status' => 1,
                'user' => $newUser->id
            ]);

            Session::flash('message', 'La contraseña de la Comercializadora ' . $request['description']
                . ' es: <b>' . '<a class="copyInfo">' . $newPassw . '</a>' . '</b>');
            Session::flash('passwCommerce', $newPassw);

            return redirect()->back();
        }
    }

    public function promoteUserToCommerce(Request $request)
    { // Convierte un usuario ya registrado en Comercializadora

    }

    public function updateCommissionForAllCommerce(Request $request)
    {
        Commerce::where('status', 1)->update(['com' => $request->com]);
        return redirect()->back()->with('message', 'La comision ha sido actualizada');
    }

    public function updateCommerce(Request $request)
    {
        $commerce = Commerce::findOrFail($request->id);

        if ($commerce) {
            $commerce->description = $request->descriptionCommerce;
            $commerce->com = $request->comCommerce;
            $commerce->telefono = $request->telefonoCommerce;
            $commerce->country = $request->countryCommerce;
            $commerce->save();

            Session::flash('message', 'La informacion fue actualizada');
        }
        return redirect()->back();
    }

    public function deleteCommerce(Request $request)
    {
        $Commerce = Commerce::findOrFail($request->id);
        $Commerce->delete();

        return redirect()->back()->with('message', 'La Comercializadora <b>' . $Commerce->description . '</b> fue eliminad@');
    }
}
