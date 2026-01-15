<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Countries\CountryRepository;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        // Obtener países activos con solo id y name
        $countries = app(CountryRepository::class)->getActive()->all(['id', 'name']);

        return Inertia::render('auth/Register', [
            'inertiaOnly' => true,
            'countries' => $countries,
            'registerUrl' => route('register'),
            'loginUrl' => route('login'),
        ]);
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
                'referral_code' => app(UserRepository::class)->generateUniqueReferralCode(),
                'referred_by' => $this->getReferredBy(),
            ]);

            // Obtener el valor de la moneda para el país del usuario
            $currency = Currency::getValueByCountry($user->country, 'id');

            // Crear un nuevo Wallet para el usuario
            Wallet::create([
                'user_id' => $user->id,
                'currency_id' => $currency,
                'balance' => 0.00,
                'status' => 'ACTIVE',
            ]);

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
}
