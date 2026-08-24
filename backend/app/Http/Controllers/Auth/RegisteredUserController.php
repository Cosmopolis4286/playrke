<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\SettingRepositoryInterface;
use App\Events\Admin\UserRegistered as UserRegisteredBroadcast;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\CountryRepository;
use App\Repositories\UserRepository;
use App\Services\User\UserLevelService;
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
     * Inyecta el repositorio en el constructor
     */
    public function __construct(
        protected SettingRepositoryInterface $settings,
        protected CurrencyRepositoryInterface $currencies,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response|RedirectResponse
    {
        // Obtener países activos con solo id y name
        $countries = app(CountryRepository::class)->getActive()->all(['id', 'name']);
        $register = $this->settings->getParameterStatus('REGISTER');

        if (!$register) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/Register', [
            'inertiaOnly' => true,
            'countries' => $countries,
            'register' => (bool) $register,
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
            $userRepository = app(UserRepository::class);

            // Código de referido: cookie 'referral' (seteada por
            // ReferralController::link() al visitar /referrals/r/{code}) o
            // campo 'referral_code' enviado directo en el form. Un código
            // inválido/inexistente no bloquea el registro — solo no vincula
            // referente.
            $referredByUserId = null;
            $referralCode = $request->cookie('referral') ?? $request->input('referral_code');
            if ($referralCode) {
                $referrer = $userRepository->findByReferralCode($referralCode, ['id']);
                $referredByUserId = $referrer?->id;
            }

            // Código para crear el usuario y otras operaciones
            $user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'country_id' => $request->country,
                'password' => Hash::make($request->password),
                'referral_code' => $userRepository->generateUniqueReferralCode(),
                'referred_by_user_id' => $referredByUserId,
                'status' => 'ACTIVE',
            ]);

            $user->assignRole('player');

            // Obtener el valor de la moneda para el país del usuario
            $currency = $this->currencies->getValueByCountry($user->country_id, 'id');

            // Crear un nuevo Wallet para el usuario
            Wallet::create([
                'user_id' => $user->id,
                'currency_id' => $currency,
                'balance' => 0.00,
                'status' => 'ACTIVE',
            ]);

            DB::commit();

            // XP al referente FUERA de la transacción del registro: si el
            // otorgamiento de XP fallara, no debe revertir el registro del
            // nuevo usuario — mismo criterio que GameCompletionService, que
            // envuelve la evaluación de logros en try/catch para no revertir
            // resultado ya guardado.
            if ($referredByUserId) {
                try {
                    $referrer = User::find($referredByUserId);
                    if ($referrer) {
                        $xp = app(UserLevelService::class)->calculateXpReward('referral_signup');
                        app(UserLevelService::class)->addXpToUser($referrer, $xp, "Referido registrado: {$user->username}");
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RegisteredUserController] No se pudo otorgar XP de referido: ' . $e->getMessage(), [
                        'referrer_id' => $referredByUserId,
                        'new_user_id' => (string) $user->id,
                    ]);
                }
            }

            event(new Registered($user));

            broadcast(new UserRegisteredBroadcast(
                userId: (string) $user->id,
                username: $user->username,
                email: $user->email,
                roleName: 'player',
                createdAt: $user->created_at->toISOString(),
            ));

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
