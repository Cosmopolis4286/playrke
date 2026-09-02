<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    /**
     * Datos de referidos del usuario autenticado, para el tab "Referidos"
     * del perfil (mismo patrón que /profile/achievements/unlocked).
     */
    public function summary(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $referrals = $user->referrals()
            ->select(['id', 'username', 'first_name', 'last_name', 'created_at'])
            ->latest('created_at')
            ->get()
            ->map(fn (User $referred) => [
                'id'         => (string) $referred->id,
                'username'   => $referred->username,
                'full_name'  => trim("{$referred->first_name} {$referred->last_name}"),
                'joined_at'  => $referred->created_at?->toISOString(),
            ]);

        return response()->json([
            'success'          => true,
            'referral_code'    => $user->referral_code,
            'referral_link'    => route('referral.link', ['referralCode' => $user->referral_code]),
            'total_referrals'  => $referrals->count(),
            'referrals'        => $referrals,
        ]);
    }

    /**
     * Visitar /referrals/r/{code} deja una cookie temporal (15 días) que
     * RegisteredUserController::store() lee al crear la cuenta para
     * vincular referred_by_user_id — igual que antes, pero sin requerir
     * sesión iniciada (esta ruta debe ser accesible por un visitante nuevo,
     * no solo por un usuario ya autenticado).
     *
     * Un código inexistente no deja cookie — RegisteredUserController::store()
     * ya tolera un código inválido (no bloquea el registro), pero sin este
     * guard cualquier string en la URL quedaba 15 días en la cookie del
     * visitante sin propósito.
     */
    public function link(Request $request, string $referralCode): RedirectResponse
    {
        if (! $request->hasCookie('referral')) {
            $exists = app(UserRepository::class)->existsByReferralCode($referralCode);
            if ($exists) {
                return redirect('/register')->withCookie(cookie('referral', $referralCode, 60 * 24 * 15));
            }
        }

        return redirect('/register');
    }
}
