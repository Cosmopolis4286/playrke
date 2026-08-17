<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use App\Services\KycService;
use App\Services\ProfileService;
use App\Services\TosService;
use App\Traits\Users\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Inertia\Inertia;

class ProfileController extends Controller
{
    use UserTrait;

    public function __construct(
        protected ProfileService $profileService,
        protected KycService     $kycService,
        protected TosService     $tosService,
    ) {}

    /** Retorna el usuario autenticado tipado como User (elimina advertencias del IDE). */
    private function authUser(): User
    {
        /** @var User $user */
        $user = Auth::user();
        return $user;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Página principal
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): \Inertia\Response
    {
        $user = User::with(['roles', 'wallets.currency', 'country'])
            ->findOrFail(Auth::id());

        $this->loadOptionalRelations($user);

        $wallet = $user->wallets->first();

        return Inertia::render('Profile/Index', [
            'user'        => $this->serializeUser($user, $wallet),
            'countries'   => Country::orderBy('name')
                                ->get(['id', 'name', 'iso_code_2'])
                                ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name, 'iso' => $c->iso_code_2]),
            'stats'       => $this->buildStats($user),
            'preferences' => $this->buildPreferences($user),
            'kyc'         => $this->kycService->statusForUser($user),
            'tos'         => [
                'needs_acceptance' => $this->tosService->needsAcceptance($user),
                'accepted_version' => $user->tos_accepted_version,
                'accepted_at'      => $user->tos_accepted_at?->toIso8601String(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /profile/data
    // ─────────────────────────────────────────────────────────────────────────

    public function getProfileData(): JsonResponse
    {
        $user = User::with(['roles', 'wallets.currency', 'country'])
            ->findOrFail(Auth::id());
        $wallet = $user->wallets->first();

        return response()->json(['success' => true, 'data' => $this->serializeUser($user, $wallet)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /profile/update
    // ─────────────────────────────────────────────────────────────────────────

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->authUser();

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'username'   => ['required', 'string', 'max:30', 'unique:users,username,' . $user->id],
            'email'      => ['required', 'email', 'max:320', 'unique:users,email,' . $user->id],
            'phone'      => ['nullable', 'string', 'max:25'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'country_id' => ['nullable', 'string', 'exists:countries,id'],
            'bio'        => ['nullable', 'string', 'max:500'],
        ], [
            'username.unique'   => 'Este nombre de usuario ya está en uso.',
            'email.unique'      => 'Este correo ya está registrado.',
            'birth_date.before' => 'La fecha de nacimiento debe ser en el pasado.',
        ]);

        $user->update([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'username'     => $request->username,
            'email'        => $request->email,
            'phone_number' => $request->phone,
            'birth_date'   => $request->birth_date,
            // country_id NO es nullable en la tabla users — si el cliente
            // omite el campo (validación lo permite), preservar el valor
            // actual en vez de sobreescribirlo con null y romper la query.
            'country_id'   => $request->country_id ?? $user->country_id,
            'bio'          => $request->bio,
        ]);

        $user->refresh()->load(['wallets.currency', 'country']);
        $wallet = $user->wallets->first();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data'    => $this->serializeUser($user, $wallet),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PATCH /profile/password
    // ─────────────────────────────────────────────────────────────────────────

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual es incorrecta.',
            'new_password.different'            => 'La nueva contraseña debe ser diferente a la actual.',
            'new_password.confirmed'            => 'Las contraseñas no coinciden.',
        ]);

        $this->profileService->changePassword($this->authUser()->id, $request->new_password);

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /profile/avatar
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120']]);

        try {
            $user    = $this->authUser();
            $manager = new ImageManager(new Driver());
            $image   = $manager->read($request->file('avatar')->getContent());
            $image->cover(400, 400);
            $encoded  = $image->toJpeg(80);
            $filename = time() . '_' . uniqid() . '.jpg';

            Storage::disk('public')->makeDirectory('avatars');
            Storage::disk('public')->put("avatars/{$filename}", $encoded);

            if ($user->profile_photo_url) {
                $old = ltrim(str_replace('/storage/', '', $user->profile_photo_url), '/');
                Storage::disk('public')->delete($old);
            }

            $path = "/storage/avatars/{$filename}";
            $user->update(['profile_photo_url' => $path]);

            return response()->json(['success' => true, 'message' => 'Avatar actualizado.', 'data' => ['avatar' => $path]]);
        } catch (\Throwable $e) {
            Log::error('Avatar upload: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar el avatar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /profile/avatar
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteAvatar(): JsonResponse
    {
        $user = $this->authUser();

        if ($user->profile_photo_url) {
            $old = ltrim(str_replace('/storage/', '', $user->profile_photo_url), '/');
            Storage::disk('public')->delete($old);
            $user->update(['profile_photo_url' => null]);
        }

        return response()->json(['success' => true, 'message' => 'Avatar eliminado.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /profile/preferences  |  PUT /profile/preferences
    // ─────────────────────────────────────────────────────────────────────────

    public function getPreferences(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->buildPreferences($this->authUser())]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'language'      => ['nullable', 'string', 'max:10'],
            'timezone'      => ['nullable', 'string', 'max:50'],
            'notifications' => ['nullable', 'array'],
            'privacy'       => ['nullable', 'array'],
            'game_settings' => ['nullable', 'array'],
        ]);

        $user    = $this->authUser();
        $current = $user->user_preferences ?? [];
        $patch   = $request->only(['language', 'timezone', 'notifications', 'privacy', 'game_settings']);

        // Merge por-clave: los sub-objetos (notifications/privacy/game_settings) se
        // fusionan a un nivel de profundidad en vez de reemplazarse por completo,
        // para que un patch parcial de un sub-objeto no borre el resto de sus claves.
        foreach (['notifications', 'privacy', 'game_settings'] as $key) {
            if (isset($patch[$key]) && is_array($patch[$key])) {
                $patch[$key] = array_merge($current[$key] ?? [], $patch[$key]);
            }
        }

        $prefs = array_merge($current, $patch);
        $user->update(['user_preferences' => $prefs]);

        return response()->json(['success' => true, 'message' => 'Preferencias guardadas.', 'data' => $prefs]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /profile/stats
    // ─────────────────────────────────────────────────────────────────────────

    public function getStats(): JsonResponse
    {
        $user = User::findOrFail(Auth::id());
        $this->loadOptionalRelations($user);

        return response()->json(['success' => true, 'data' => $this->buildStats($user)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sesiones
    // ─────────────────────────────────────────────────────────────────────────

    public function getSessions(Request $request): JsonResponse
    {
        $currentId = $request->session()->getId();

        $rows = DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get();

        $sessions = $rows->map(fn ($row) => [
            'id'            => $row->id,
            'is_current'    => $row->id === $currentId,
            'ip_address'    => $row->ip_address,
            'user_agent'    => $row->user_agent ?? '',
            'last_activity' => $row->last_activity,
            'device'        => $this->parseDevice($row->user_agent ?? ''),
        ]);

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    public function revokeSession(Request $request, string $id): JsonResponse
    {
        if ($id === $request->session()->getId()) {
            return response()->json(['success' => false, 'message' => 'No puedes cerrar la sesión actual.'], 422);
        }

        DB::table('sessions')->where('id', $id)->where('user_id', Auth::id())->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return response()->json(['success' => true, 'message' => 'Todas las otras sesiones han sido cerradas.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /** Carga nivel/logros sin tumbar la petición si el módulo aún no está disponible en el entorno. */
    private function loadOptionalRelations(User $user): void
    {
        foreach (['userLevel', 'achievements'] as $relation) {
            try {
                $user->load($relation);
            } catch (\Throwable $e) {
                Log::warning("Profile: no se pudo cargar la relación '{$relation}'.", [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    private function serializeUser(User $user, $wallet): array
    {
        return [
            'id'              => (string) $user->id,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'name'            => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'username'        => $user->username,
            'email'           => $user->email,
            'avatar'          => $user->profile_photo_url,
            'phone'           => $user->phone_number,
            'birth_date'      => $user->birth_date?->format('Y-m-d'),
            'country_id'      => $user->country_id ? (string) $user->country_id : null,
            'country_iso'     => $user->country?->iso_code_2,
            'bio'             => $user->bio,
            'is_verified'     => ! is_null($user->email_verified_at),
            'is_two_factor_enabled' => (bool) $user->is_two_factor_enabled,
            'kyc_level'       => (int) ($user->kyc_level ?? 0),
            'kyc_status'      => $user->kyc_status ?? 'NONE',
            'tos_accepted_at' => $user->tos_accepted_at?->toIso8601String(),
            'created_at'      => $user->created_at->toIso8601String(),
            'last_active'     => $user->last_login_at?->toIso8601String() ?? $user->updated_at->toIso8601String(),
            'roles'           => $user->roles->map(fn ($r) => [
                'id'          => (string) $r->id,
                'name'        => $r->name,
                'description' => $r->description ?? null,
            ]),
            'wallet' => $wallet ? [
                'balance'       => (float) $wallet->balance,
                'bonus_balance' => (float) $wallet->bonus_balance,
                'currency'      => $wallet->currency?->iso_code ?? 'USD',
                'symbol'        => $wallet->currency?->symbol   ?? '$',
            ] : null,
        ];
    }

    private function buildStats(User $user): array
    {
        $level = $user->relationLoaded('userLevel') ? $user->userLevel : null;
        return [
            'games_played'       => $user->games_played_count,
            'games_won'          => $user->games_won_count,
            'total_winnings'     => $user->total_winnings,
            'current_streak'     => $user->login_streak_days,
            'best_streak'        => $user->best_login_streak_days,
            'level'              => $level?->current_level    ?? 1,
            'experience_points'  => $level?->current_xp       ?? 0,
            'xp_to_next_level'   => $level?->xp_to_next_level ?? 1000,
            'level_progress'     => $level
                ? round(($level->current_xp / max(1, $level->xp_to_next_level)) * 100, 1)
                : 0,
            'achievements_count' => $user->relationLoaded('achievements') ? $user->achievements->count() : 0,
            'join_date'          => $user->created_at->format('Y-m-d'),
            'last_active'        => $user->last_login_at?->toIso8601String()
                                    ?? $user->updated_at->toIso8601String(),
        ];
    }

    private function buildPreferences(User $user): array
    {
        $p = $user->user_preferences ?? [];
        return [
            'language'      => $p['language']      ?? 'es',
            'timezone'      => $p['timezone']      ?? 'UTC',
            'notifications' => $p['notifications'] ?? ['email' => true,  'push' => true, 'sms' => false],
            'privacy'       => $p['privacy']       ?? ['profile_visible' => true, 'show_email' => false, 'show_phone' => false],
            'game_settings' => $p['game_settings'] ?? ['sound_enabled' => true, 'music_enabled' => true, 'auto_dauber' => false, 'card_animation' => true],
        ];
    }

    private function parseDevice(string $ua): string
    {
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))   return 'iOS';
        if (str_contains($ua, 'Android'))   return 'Android';
        if (str_contains($ua, 'Windows'))   return 'Windows';
        if (str_contains($ua, 'Macintosh')) return 'Mac';
        if (str_contains($ua, 'Linux'))     return 'Linux';
        return 'Desconocido';
    }
}
