<?php

declare(strict_types=1);

namespace App\Services\Roles\Seller;

use App\Models\Roles\Seller;
use Illuminate\Support\Str;

/**
 * Enlace público de venta de un vendedor (/comprar/{token}): por ahí sus
 * compradores RECURRENTES compran por su cuenta, siempre con un código de
 * compra vigente (ver ExternalBuyerPurchaseCodeService).
 *
 * El token es aleatorio (no derivable del id) y regenerable: regenerarlo
 * invalida el enlace anterior si se filtró.
 */
final class SellerSalesLinkService
{
    private const TOKEN_LENGTH = 32;

    /**
     * Enlace vigente del vendedor; lo crea la primera vez.
     *
     * @return array{token: string, url: string}
     */
    public function linkFor(Seller $seller): array
    {
        if (!$seller->sales_link_token) {
            $this->assignNewToken($seller);
        }

        return $this->present($seller);
    }

    /**
     * Reemplaza el token (el enlace anterior deja de funcionar).
     *
     * @return array{token: string, url: string}
     */
    public function regenerate(Seller $seller): array
    {
        $this->assignNewToken($seller);

        return $this->present($seller);
    }

    /**
     * Vendedor dueño del enlace, solo si él y su jerarquía (agencia/comercio)
     * están ACTIVE — un enlace de un vendedor bloqueado no vende.
     */
    public function resolveSeller(string $token): ?Seller
    {
        if (strlen($token) !== self::TOKEN_LENGTH) {
            return null;
        }

        $seller = Seller::where('sales_link_token', $token)->first();

        return $seller && $seller->hasActiveHierarchy() ? $seller : null;
    }

    private function assignNewToken(Seller $seller): void
    {
        do {
            $token = Str::random(self::TOKEN_LENGTH);
        } while (Seller::where('sales_link_token', $token)->exists());

        $seller->forceFill(['sales_link_token' => $token])->save();
    }

    /** @return array{token: string, url: string} */
    private function present(Seller $seller): array
    {
        return [
            'token' => (string) $seller->sales_link_token,
            'url'   => route('bingo-buy.show', ['token' => $seller->sales_link_token]),
        ];
    }
}
