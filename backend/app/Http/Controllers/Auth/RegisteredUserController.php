<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\SettingRepositoryInterface;
use App\Events\Admin\UserRegistered as UserRegisteredBroadcast;
use App\Http\Controllers\Controller;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\CountryRepository;
use App\Repositories\UserRepository;
use App\Services\User\UserLevelService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
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
     * Responde JSON (no redirect) — el formulario real (Auth/Register.vue vía
     * useRegisterForm) hace un POST por axios y espera JSON, mismo patrón que
     * AuthenticatedSessionController::store() (login). Un RedirectResponse
     * aquí rompía el registro en silencio: axios seguía el redirect y
     * recibía HTML donde esperaba JSON — bug crítico bloqueante corregido
     * como parte de progreso/estrategia-producto-plan.md, Fase 5.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'country' => ['required', 'numeric', 'exists:countries,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'cf-turnstile-response' => ['required', new \App\Rules\CloudflareTurnstile($request->ip())],
        ]);

        $user = DB::transaction(function () use ($request) {
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

            $user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'country_id' => $request->country,
                'phone_number' => $request->phone,
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

            return $user;
        });

        // XP al referente y vinculación de ExternalPlayer FUERA de la
        // transacción del registro: si cualquiera de los dos fallara, no
        // debe revertir el registro del usuario ya creado — mismo criterio
        // que GameCompletionService (evaluar logros en try/catch sin
        // revertir el resultado ya guardado).
        $referredByUserId = $user->referred_by_user_id;
        if ($referredByUserId) {
            try {
                $referrer = User::find($referredByUserId);
                if ($referrer) {
                    $xp = app(UserLevelService::class)->calculateXpReward('referral_signup');
                    app(UserLevelService::class)->addXpToUser($referrer, $xp, "Referido registrado: {$user->username}");
                }
            } catch (\Throwable $e) {
                Log::warning('[RegisteredUserController] No se pudo otorgar XP de referido: ' . $e->getMessage(), [
                    'referrer_id' => $referredByUserId,
                    'new_user_id' => (string) $user->id,
                ]);
            }
        }

        if ($request->filled('phone')) {
            $this->linkExternalPlayerHistory($user, $request->phone, $request->country);
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

        return response()->json(['success' => true]);
    }

    /**
     * Vincula automáticamente el historial de ExternalPlayer (comprador sin
     * cuenta, captado por un vendedor — ver
     * App\Models\Roles\Player\External\ExternalPlayer) al usuario recién
     * registrado, cuando el teléfono+país coincide con uno o más registros
     * sin convertir todavía. Un mismo jugador puede tener varias filas
     * ExternalPlayer (una por sala/vendedor donde jugó como invitado) — se
     * vinculan todas las que compartan teléfono+país, replicando lo que
     * hace ExternalPlayerAdminService::convertToUser() pero disparado por
     * el propio jugador en vez de un admin, y sin generar contraseña
     * temporal (el usuario ya se registró con la suya).
     */
    private function linkExternalPlayerHistory(User $user, string $phone, string $countryId): void
    {
        try {
            $matches = ExternalPlayer::where('phone', $phone)
                ->where('country_id', $countryId)
                ->whereNull('converted_user_id')
                ->get();

            if ($matches->isEmpty()) {
                return;
            }

            foreach ($matches as $externalPlayer) {
                $externalPlayer->update(['converted_user_id' => $user->id]);
            }

            // Incentivo de conversión del funnel jugador externo → registrado
            // (progreso/estrategia-producto-plan.md, Fase 5): XP al propio
            // usuario nuevo, no dinero — evita afectar la economía real.
            $xp = app(UserLevelService::class)->calculateXpReward('external_player_conversion');
            app(UserLevelService::class)->addXpToUser($user, $xp, 'Historial de invitado vinculado al registrarte');

            Log::info('[RegisteredUserController] Historial de jugador externo vinculado al registrarse.', [
                'user_id' => (string) $user->id,
                'external_player_ids' => $matches->pluck('id')->map(fn ($id) => (string) $id)->all(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[RegisteredUserController] No se pudo vincular historial de jugador externo: ' . $e->getMessage(), [
                'user_id' => (string) $user->id,
            ]);
        }
    }
}
