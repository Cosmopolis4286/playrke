<?php

namespace App\Services\Roles\Admin\User;

use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\User;
use App\Services\User\UserLevelService;
use App\Services\User\UserPasswordService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

final class AdminUserService
{
    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService     $userPasswordService,
        private readonly WalletService           $walletService,
        private readonly UserLevelService        $userLevelService,
    ) {}

    public function fetchList(): array
    {
        return $this->context->resolveUsers()['users']['rows'] ?? [];
    }

    /**
     * Alternar estado ACTIVE / INACTIVE.
     */
    public function toggleStatus(User $user): void
    {
        if ($user->status === 'DELETED') {
            throw AdminUserException::invalidStatusChange();
        }

        $user->update([
            'status' => $user->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
        ]);
    }

    /**
     * Eliminación lógica (soft delete).
     */
    public function delete(User $user): void
    {
        if ($user->status === 'DELETED') {
            throw AdminUserException::alreadyDeleted();
        }

        $user->update(['status' => 'DELETED']);
        $user->delete();
    }

    /**
     * Eliminación definitiva (hard delete).
     */
    public function destroy(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->forceDelete();
        });
    }

    /**
     * Resetear contraseña.
     */
    public function resetPassword(User $user): string
    {
        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($user);
    }

    /**
     * Cargar saldo en wallet del usuario (operación admin).
     */
    public function loadWalletBalance(User $user, float $amount): string
    {
        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::walletLoadRequiresActiveStatus();
        }

        if ($amount <= 0) {
            throw AdminUserException::invalidWalletAmount();
        }

        $wallet = $this->walletService->recordAdminDeposit(
            user:        $user,
            amount:      $amount,
            description: 'Carga de saldo por administrador',
        );

        $wallet->load('currency');

        return "Saldo cargado exitosamente: {$wallet->currency->symbol}{$amount}. Nuevo saldo: {$wallet->currency->symbol}{$wallet->balance}";
    }

    /**
     * Otorgar XP manualmente al usuario (operación admin).
     */
    public function grantXp(User $user, int $amount, ?string $reason = null): array
    {
        if ($amount <= 0) {
            throw AdminUserException::invalidXpAmount();
        }

        $result = $this->userLevelService->addXpToUser(
            $user,
            $amount,
            $reason ?: 'XP otorgado por administrador',
        );

        if (! $result['success']) {
            throw AdminUserException::invalidXpAmount();
        }

        return $result;
    }

    /**
     * Obtener balance de wallet del usuario.
     */
    public function getWalletBalance(User $user): array
    {
        $wallet = $user->wallets()
            ->with('currency:id,iso_code,symbol,name')
            ->first();
        
        if (!$wallet) {
            return [
                'balance' => 0,
                'currency' => null
            ];
        }

        return [
            'balance' => (float) $wallet->balance,
            'currency' => $wallet->currency ? [
                'iso_code' => $wallet->currency->iso_code,
                'symbol' => $wallet->currency->symbol,
                'name' => $wallet->currency->name,
            ] : null
        ];
    }
}
