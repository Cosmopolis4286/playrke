<?php

namespace App\Services\Roles\Admin\User;

use App\Exceptions\Domain\Roles\Admin\User\AdminUserException;
use App\Models\User;
use App\Services\User\UserPasswordService;
use Illuminate\Support\Facades\DB;

final class AdminUserService
{
    public function __construct(
        private readonly AdminUserContextService $context,
        private readonly UserPasswordService     $userPasswordService,
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
     * Cargar saldo en wallet del usuario.
     */
    public function loadWalletBalance(User $user, float $amount): string
    {
        if ($user->status !== 'ACTIVE') {
            throw AdminUserException::walletLoadRequiresActiveStatus();
        }

        if ($amount <= 0) {
            throw AdminUserException::invalidWalletAmount();
        }

        // Implementar lógica real de carga de wallet
        try {
            // 1. Obtener o crear la wallet principal del usuario
            $wallet = $user->wallets()->with('currency')->first();
            
            if (!$wallet) {
                // Si no tiene wallet, crear una con la moneda por defecto (USD)
                $currency = \App\Models\Currency::where('iso_code', 'USD')->first();
                if (!$currency) {
                    throw new \Exception('No se encontró moneda por defecto (USD)');
                }
                
                $wallet = $user->wallets()->create([
                    'currency_id' => $currency->id,
                    'balance' => 0,
                    'is_active' => true,
                ]);
                
                $wallet->load('currency');
            }

            // 2. Actualizar el balance
            $oldBalance = $wallet->balance;
            $newBalance = $oldBalance + $amount;
            
            $wallet->update(['balance' => $newBalance]);

            // 3. Registrar el movimiento (opcional, si hay tabla de movimientos)
            // $this->recordWalletMovement($wallet, $amount, 'admin_load', $oldBalance, $newBalance);

            return "Saldo cargado exitosamente: {$wallet->currency->symbol}{$amount}. Nuevo saldo: {$wallet->currency->symbol}{$newBalance}";
            
        } catch (\Exception $e) {
            throw new \Exception('Error al cargar el saldo: ' . $e->getMessage());
        }
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
