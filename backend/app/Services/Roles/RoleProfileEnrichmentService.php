<?php

namespace App\Services\Roles;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\User;

/**
 * Datos compartidos entre las 3 fichas de autogestión (Commerce/Subsidiary/
 * Seller "Profile") que no dependen del tipo de rol: wallet y actividad
 * reciente de movimientos. Antes cada ficha solo mostraba nombre/teléfono/
 * país/email — ninguna exponía saldo ni historial, pese a que ambos ya
 * existen en el modelo (User::wallets(), WalletMovement).
 *
 * Extraído a un servicio propio (no duplicado en los 3 *ProfileService*)
 * porque la lógica es idéntica sea cual sea el rol: el dueño de la ficha es
 * siempre un User con sus propias wallets, sin importar si además es
 * Commerce/Subsidiary/Seller.
 */
final class RoleProfileEnrichmentService
{
    private const RECENT_MOVEMENTS_LIMIT = 5;

    /**
     * Metadatos de cuenta: miembro desde / última conexión — created_at del
     * User (fecha de alta real, no de la entidad de rol) y last_login_at, ya
     * poblado por el flujo de login existente (ver AuthenticatedSessionController).
     *
     * Recibe solo el id, no el $user cargado por el *ProfileService* llamante
     * — cada uno hace su propio ->load(['user:id,username,email']) con select
     * angosto (sin created_at/last_login_at), y pasar ese objeto parcial aquí
     * lanzaba "Call to a member function toIso8601String() on null". Se
     * resuelve el User completo directo por id, independiente de qué columnas
     * haya cargado el caller.
     *
     * @return array{member_since: string, last_login_at: string|null}
     */
    public function accountMeta(string $userId) : array
    {
        $user = User::query()->select(['id', 'created_at', 'last_login_at'])->findOrFail($userId);

        return [
            'member_since' => $user->created_at->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    /**
     * Últimos movimientos de wallet del usuario (cualquier moneda), para el
     * timeline de "Actividad reciente" — mismo criterio de campos que
     * WalletMovementController::mapMovement() pero recortado a lo que la
     * ficha necesita mostrar.
     *
     * @return array<int, array{id: string, type: string, status: string, amount: float, currency: string|null, created_at: string}>
     */
    public function recentMovements(User $user) : array
    {
        return WalletMovement::where('user_id', $user->id)
            ->with('wallet.currency:id,iso_code,symbol')
            ->latest('created_at')
            ->limit(self::RECENT_MOVEMENTS_LIMIT)
            ->get()
            ->map(fn (WalletMovement $movement) => [
                'id' => (string) $movement->id,
                'type' => $movement->type,
                'type_label' => $movement->getTypeLabel(),
                'status' => $movement->status,
                'amount' => (float) $movement->amount,
                'currency' => $movement->wallet?->currency?->iso_code,
                'created_at' => $movement->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Wallets del usuario con su moneda, para la sección "Billetera" de la
     * ficha — de solo lectura aquí (gestión real vive en /wallet).
     *
     * @return array<int, array{currency: string, symbol: string, balance: float, bonus_balance: float}>
     */
    public function wallets(User $user) : array
    {
        return $user->wallets()
            ->with('currency:id,iso_code,symbol,decimal_places')
            ->get()
            ->map(fn ($wallet) => [
                'currency' => $wallet->currency?->iso_code ?? '—',
                'symbol' => $wallet->currency?->symbol ?? '',
                'balance' => (float) $wallet->balance,
                'bonus_balance' => (float) $wallet->bonus_balance,
            ])
            ->all();
    }
}
