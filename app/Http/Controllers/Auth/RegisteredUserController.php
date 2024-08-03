<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Coin;
use App\Models\Country;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $countries = Country::all(['id', 'name']);
        return view('auth.register', compact("countries"));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'country' => ['required', 'numeric', 'exists:countries,id'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'politicas' => ['accepted']
            ]);
        } catch (ValidationException $e) {
            // Obtener todos los errores de validación
            $errors = $e->errors();

            // O puedes redirigir de vuelta con errores
            return back()->withErrors($errors)->withInput();
        }

        DB::beginTransaction();

        try {
            // Código para crear el usuario y otras operaciones
            $user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'country' => $request->country,
                'password' => Hash::make($request->password),
                'referral_code' => User::getUniqueReferralCode(),
                'referred_by' => $this->getReferredBy(),
            ]);

            // Obtener el valor de la moneda para el país del usuario
            $coin = Coin::getCoinValueByCountry($user->country, 'id');

            // Crear un nuevo Wallet para el usuario
            $wallet = new Wallet;
            $wallet->coin = $coin;
            $wallet->user = $user->id;
            $wallet->save();

            DB::commit();

            event(new Registered($user));

            Auth::login($user);

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            // Lanzar nuevamente la excepción para manejarla a nivel superior si es necesario
            throw $e;
        }
    }

    private function getReferredBy()
    {
        $referralCode = Cookie::get('referral');
        if ($referralCode) {
            return User::where('referral_code', $referralCode)->value('id');
        }
        return null;
    }
}
