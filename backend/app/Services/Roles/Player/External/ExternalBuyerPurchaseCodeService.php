<?php

declare(strict_types=1);

namespace App\Services\Roles\Player\External;

use App\Models\Roles\Player\External\ExternalBuyerPurchaseCode;
use App\Models\Roles\Player\External\ExternalPlayer;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Códigos de compra para compradores recurrentes (regla de negocio
 * 2026-10-03): solo puede comprar por su cuenta quien YA le compró antes al
 * vendedor, y solo con un código que el vendedor le otorga. El código vence
 * al agotar sus usos o al pasar su vigencia (lo que ocurra primero), o si el
 * vendedor lo revoca.
 *
 * Los teléfonos se comparan por dígitos: el punto de venta los guarda con el
 * formato de la máscara ("+58 0414-1234567").
 */
final class ExternalBuyerPurchaseCodeService
{
    /** Sin 0/O ni 1/I/L: se dicta por teléfono o se copia de WhatsApp. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 8;

    /**
     * Consume un uso de forma atómica: el UPDATE solo aplica si el código
     * sigue vigente en ese instante, así dos compras simultáneas no pueden
     * gastar el mismo último uso.
     */
    public function consume(ExternalBuyerPurchaseCode $code) : bool
    {
        return ExternalBuyerPurchaseCode::whereKey($code->id)
            ->usable()
            ->update([
                'uses_count' => \Illuminate\Support\Facades\DB::raw('uses_count + 1'),
                'last_used_at' => now(),
                'updated_at' => now(),
            ]) === 1;
    }

    /** Solo dígitos: "+58 0414-1234567" → "5804141234567". */
    public function digits(string $phone) : string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Código vigente del vendedor que coincide con país + teléfono + código,
     * o null. No consume usos (ver consume()).
     */
    public function findUsable(string $sellerUserId, string $countryId, string $phone, string $code) : ?ExternalBuyerPurchaseCode
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', $code) ?? '');

        if ($normalized === '') {
            return null;
        }

        return $this->usableFor($sellerUserId, $countryId, $this->digits($phone))
            ->where('code', $normalized)
            ->first();
    }

    public function isRecurringBuyer(string $sellerUserId, string $countryId, string $phone) : bool
    {
        return $this->previousBuyer($sellerUserId, $countryId, $phone) !== null;
    }

    /**
     * Otorga un código a un comprador recurrente del vendedor. Revoca los
     * códigos vigentes previos del mismo comprador (uno activo a la vez).
     *
     * @throws DomainException si el comprador nunca le compró a este vendedor
     *                         o si los límites no están entre las opciones
     */
    public function issue(string $sellerUserId, string $countryId, string $phone, int $maxUses, int $validDays) : ExternalBuyerPurchaseCode
    {
        $config = config('sellers.purchase_codes');

        if (! in_array($maxUses, $config['uses_options'], true) || ! in_array($validDays, $config['valid_days_options'], true)) {
            throw new DomainException('Los usos o la vigencia del código no son válidos.');
        }

        if (! $this->isRecurringBuyer($sellerUserId, $countryId, $phone)) {
            throw new DomainException('Solo puedes dar un código a un comprador que ya te compró antes.');
        }

        $digits = $this->digits($phone);

        // Un código vigente por comprador: el nuevo reemplaza al anterior.
        $this->usableFor($sellerUserId, $countryId, $digits)->update(['revoked_at' => now()]);

        return ExternalBuyerPurchaseCode::create([
            'seller_user_id' => $sellerUserId,
            'country_id' => $countryId,
            'phone' => $digits,
            'code' => $this->generateCode(),
            'max_uses' => $maxUses,
            'uses_count' => 0,
            'expires_at' => now()->addDays($validDays),
        ]);
    }

    /**
     * Códigos otorgados por el vendedor (más recientes primero), con estado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForSeller(string $sellerUserId, int $limit = 50) : array
    {
        return ExternalBuyerPurchaseCode::where('seller_user_id', $sellerUserId)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ExternalBuyerPurchaseCode $code) => $this->present($code))
            ->all();
    }

    /** @return array<string, mixed> */
    public function present(ExternalBuyerPurchaseCode $code) : array
    {
        return [
            'id' => $code->id,
            'code' => $code->code,
            'phone' => $code->phone,
            'country_id' => $code->country_id,
            'max_uses' => $code->max_uses,
            'uses_count' => $code->uses_count,
            'remaining_uses' => $code->remainingUses(),
            'expires_at' => $code->expires_at?->toIso8601String(),
            'status' => $code->statusLabel(),
            'usable' => $code->isUsable(),
        ];
    }

    /**
     * ¿El comprador ya le compró a este vendedor? (algún ExternalPlayer
     * captado por él con ese país y teléfono). Datos para precargar la
     * compra: nombre del registro más reciente.
     *
     * `phone` es el teléfono TAL COMO se guardó en su compra anterior: la
     * compra por cuenta propia debe usar ese mismo string para que
     * resolveOrCreatePlayer() reconozca al comprador (mismo registro y mismo
     * link por sala) en vez de crear uno duplicado por diferencia de formato.
     *
     * @return array{first_name: string, last_name: string, phone: string}|null
     */
    public function previousBuyer(string $sellerUserId, string $countryId, string $phone) : ?array
    {
        $digits = $this->digits($phone);

        $match = $this->candidates($sellerUserId, $countryId)
            ->first(fn (ExternalPlayer $p) => $this->digits((string) $p->phone) === $digits);

        return $match ? [
            'first_name' => (string) $match->first_name,
            'last_name' => (string) $match->last_name,
            'phone' => (string) $match->phone,
        ] : null;
    }

    /**
     * Devuelve un uso consumido cuando la compra falló después de consume()
     * (precio cambiado, sala cerrada, comprobante repetido…): el comprador
     * no debe perder un uso por una venta que no se registró.
     */
    public function releaseUse(ExternalBuyerPurchaseCode $code) : void
    {
        ExternalBuyerPurchaseCode::whereKey($code->id)
            ->where('uses_count', '>', 0)
            ->update([
                'uses_count' => \Illuminate\Support\Facades\DB::raw('uses_count - 1'),
                'updated_at' => now(),
            ]);
    }

    /** Revoca un código del propio vendedor. */
    public function revoke(string $sellerUserId, string $codeId) : void
    {
        $updated = ExternalBuyerPurchaseCode::where('id', $codeId)
            ->where('seller_user_id', $sellerUserId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($updated === 0) {
            throw new DomainException('El código no existe o ya estaba revocado.');
        }
    }

    /** @return Collection<int, ExternalPlayer> */
    private function candidates(string $sellerUserId, string $countryId) : Collection
    {
        return ExternalPlayer::where('seller_user_id', $sellerUserId)
            ->where('country_id', $countryId)
            ->latest()
            ->get(['id', 'phone', 'first_name', 'last_name', 'created_at']);
    }

    private function generateCode() : string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (ExternalBuyerPurchaseCode::where('code', $code)->exists());

        return $code;
    }

    private function usableFor(string $sellerUserId, string $countryId, string $digits) : \Illuminate\Database\Eloquent\Builder
    {
        return ExternalBuyerPurchaseCode::query()
            ->usable()
            ->where('seller_user_id', $sellerUserId)
            ->where('country_id', $countryId)
            ->where('phone', $digits);
    }
}
