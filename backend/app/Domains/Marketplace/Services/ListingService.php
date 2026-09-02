<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\AntiFraud\MarketplaceFraudService;
use App\Domains\Marketplace\Exceptions\InvalidListingException;
use App\Domains\Marketplace\Exceptions\ListingNoLongerAvailableException;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Gestiona el ciclo de vida de un Listing. No bloquea el PlayerAsset — eso
 * ocurre recién cuando una Order avanza a escrow (Fase 3, EscrowService::lockAsset()).
 * Mientras el listing está ACTIVE, el vendedor conserva el asset disponible
 * para otras cosas (ej. usarlo en juego) hasta que alguien lo compre.
 */
final class ListingService
{
    public function __construct(
        private readonly MarketplaceFraudService $fraudService,
    ) {
    }

    /**
     * Crea un listing en estado ACTIVE directamente (sin paso DRAFT
     * intermedio en esta fase — el borrador solo tiene sentido cuando exista
     * un flujo de edición previo a publicar, fuera de alcance todavía).
     */
    public function create(PlayerAsset $asset, string $sellerId, int $quantity, float $price, string $currencyId, ?\DateTimeInterface $expiresAt = null): Listing
    {
        $this->fraudService->checkListingVelocity($sellerId);

        if ((string) $asset->owner_id !== (string) $sellerId) {
            throw new InvalidListingException('Solo el dueño del activo puede publicarlo.');
        }

        if (!$asset->isAvailable()) {
            throw new InvalidListingException('El activo no está disponible para publicar.');
        }

        $definition = $asset->assetDefinition;
        if (!$definition->canBeListedOnMarketplace()) {
            throw new InvalidListingException('Este tipo de activo no puede publicarse en el marketplace.');
        }

        if ($quantity < 1 || $quantity > $asset->quantity) {
            throw new InvalidListingException('La cantidad a publicar excede la disponible.');
        }

        if ($price <= 0) {
            throw new InvalidListingException('El precio debe ser mayor a 0.');
        }

        return Listing::create([
            'seller_id'        => $sellerId,
            'player_asset_id'  => $asset->id,
            'quantity'         => $quantity,
            'price'            => $price,
            'currency_id'      => $currencyId,
            'status'           => Listing::STATUS_ACTIVE,
            'expires_at'       => $expiresAt,
        ]);
    }

    /**
     * Cancela un listing ACTIVE. No afecta ninguna Order — en esta fase un
     * listing con una Order PENDING ya está en RESERVED, no ACTIVE, así que
     * un vendedor no puede cancelar por debajo de una compra en curso.
     */
    public function cancel(Listing $listing): Listing
    {
        $affected = Listing::where('id', $listing->id)
            ->where('status', Listing::STATUS_ACTIVE)
            ->update(['status' => Listing::STATUS_CANCELLED]);

        if ($affected === 0) {
            throw new ListingNoLongerAvailableException('El listing ya no está activo.');
        }

        return $listing->fresh();
    }

    /**
     * Expira listings ACTIVE vencidos. Pensado para un scheduled command
     * (fuera de alcance de esta fase) — expuesto aquí como unidad reutilizable.
     */
    public function expireDue(): int
    {
        return DB::transaction(fn () => Listing::where('status', Listing::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => Listing::STATUS_EXPIRED]));
    }
}
