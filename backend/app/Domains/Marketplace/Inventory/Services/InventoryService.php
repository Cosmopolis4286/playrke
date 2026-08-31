<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Services;

use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Exceptions\InsufficientAssetQuantityException;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de mutación de player_assets. Ningún otro dominio (Marketplace,
 * Escrow, juegos) debe llamar PlayerAsset::update() directamente sobre
 * status/quantity/owner_id — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 15.
 *
 * Todas las transiciones siguen el mismo patrón que WalletRepository/
 * WalletService: lockForUpdate() dentro de DB::transaction(), y un UPDATE...
 * WHERE status = X con verificación de filas afectadas en vez de leer-luego-
 * escribir sin proteger la ventana entre ambas operaciones.
 */
final class InventoryService
{
    /**
     * Otorga quantity unidades de una definición a un usuario. Si es
     * stackable y ya existe una fila AVAILABLE del mismo owner+definición,
     * incrementa quantity sobre ella (bajo lock); si no, crea una fila
     * nueva. No stackable siempre crea filas nuevas (una por unidad).
     */
    public function grant(
        AssetDefinition $definition,
        string $ownerId,
        int $quantity,
        string $acquiredVia,
        ?string $acquiredReference = null,
        ?array $metadata = null,
    ): PlayerAsset {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('quantity debe ser mayor a 0.');
        }

        return DB::transaction(function () use ($definition, $ownerId, $quantity, $acquiredVia, $acquiredReference, $metadata) {
            if ($definition->stackable) {
                $existing = PlayerAsset::where('owner_id', $ownerId)
                    ->where('asset_definition_id', $definition->id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->increment('quantity', $quantity);
                    return $existing->fresh();
                }
            }

            return PlayerAsset::create([
                'asset_definition_id' => $definition->id,
                'owner_id'            => $ownerId,
                'quantity'            => $quantity,
                'status'              => PlayerAsset::STATUS_AVAILABLE,
                'acquired_via'        => $acquiredVia,
                'acquired_reference'  => $acquiredReference,
                'metadata'            => $metadata,
            ]);
        });
    }

    /**
     * Bloquea un asset para un escrow: AVAILABLE -> LOCKED. Si el asset es
     * stackable y solo se bloquea una parte de la quantity, la fila
     * original se reduce y se crea una fila LOCKED nueva por la porción
     * reservada — así el resto sigue disponible para otras operaciones.
     */
    public function lock(PlayerAsset $asset, string $escrowId, int $quantity): PlayerAsset
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('quantity debe ser mayor a 0.');
        }

        return DB::transaction(function () use ($asset, $escrowId, $quantity) {
            $locked = PlayerAsset::where('id', $asset->id)->lockForUpdate()->first();

            if (!$locked || !$locked->isAvailable()) {
                throw new AssetNotAvailableException('El activo ya no está disponible para bloquear.');
            }

            if ($locked->quantity < $quantity) {
                throw new InsufficientAssetQuantityException('No hay suficiente cantidad disponible del activo.');
            }

            if ($locked->quantity === $quantity) {
                $affected = PlayerAsset::where('id', $locked->id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->update(['status' => PlayerAsset::STATUS_LOCKED, 'locked_by_escrow_id' => $escrowId]);

                if ($affected === 0) {
                    throw new AssetNotAvailableException('El activo ya no está disponible para bloquear.');
                }

                return $locked->fresh();
            }

            // Bloqueo parcial: resta la porción de la fila original y crea
            // una fila LOCKED nueva por la cantidad reservada.
            $affected = PlayerAsset::where('id', $locked->id)
                ->where('status', PlayerAsset::STATUS_AVAILABLE)
                ->where('quantity', $locked->quantity)
                ->decrement('quantity', $quantity);

            if ($affected === 0) {
                throw new AssetNotAvailableException('El activo ya no está disponible para bloquear.');
            }

            return PlayerAsset::create([
                'asset_definition_id' => $locked->asset_definition_id,
                'owner_id'            => $locked->owner_id,
                'quantity'            => $quantity,
                'status'              => PlayerAsset::STATUS_LOCKED,
                'locked_by_escrow_id' => $escrowId,
                'acquired_via'        => $locked->acquired_via,
                'acquired_reference'  => $locked->acquired_reference,
                'metadata'            => $locked->metadata,
            ]);
        });
    }

    /**
     * Revierte un bloqueo sin transferencia: LOCKED -> AVAILABLE. Usado en
     * cancelación/refund de escrow. Si existe otra fila AVAILABLE del mismo
     * owner+definición (stackable), se re-fusiona sumando quantity en vez
     * de dejar dos filas separadas.
     */
    public function unlock(PlayerAsset $asset): void
    {
        DB::transaction(function () use ($asset) {
            $locked = PlayerAsset::where('id', $asset->id)
                ->where('status', PlayerAsset::STATUS_LOCKED)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new AssetNotAvailableException('El activo no está bloqueado.');
            }

            $definition = $locked->assetDefinition;

            if ($definition->stackable) {
                $sibling = PlayerAsset::where('owner_id', $locked->owner_id)
                    ->where('asset_definition_id', $locked->asset_definition_id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->where('id', '!=', $locked->id)
                    ->lockForUpdate()
                    ->first();

                if ($sibling) {
                    $sibling->increment('quantity', $locked->quantity);
                    $locked->delete();
                    return;
                }
            }

            $affected = PlayerAsset::where('id', $locked->id)
                ->where('status', PlayerAsset::STATUS_LOCKED)
                ->update(['status' => PlayerAsset::STATUS_AVAILABLE, 'locked_by_escrow_id' => null]);

            if ($affected === 0) {
                throw new AssetNotAvailableException('El activo no está bloqueado.');
            }
        });
    }

    /**
     * Transfiere un asset LOCKED a un nuevo owner tras un settlement de
     * escrow exitoso: LOCKED -> AVAILABLE bajo el nuevo owner_id. Nunca
     * hace DELETE+INSERT — es un UPDATE sobre la fila existente (ver
     * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 11, riesgos de duplicación).
     * Si el comprador ya tiene una fila AVAILABLE de la misma definición
     * (stackable), se fusiona en esa fila y la transferida se marca
     * TRANSFERRED como registro histórico vacío (quantity 0 no se permite
     * por el CHECK, así que se deja en 0 solo tras vaciar vía el merge).
     */
    public function transfer(PlayerAsset $asset, string $newOwnerId, string $escrowId): PlayerAsset
    {
        return DB::transaction(function () use ($asset, $newOwnerId, $escrowId) {
            $locked = PlayerAsset::where('id', $asset->id)
                ->where('status', PlayerAsset::STATUS_LOCKED)
                ->where('locked_by_escrow_id', $escrowId)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new AssetNotAvailableException('El activo no está bloqueado por este escrow.');
            }

            $definition = $locked->assetDefinition;

            if ($definition->stackable) {
                $buyerAsset = PlayerAsset::where('owner_id', $newOwnerId)
                    ->where('asset_definition_id', $locked->asset_definition_id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->lockForUpdate()
                    ->first();

                if ($buyerAsset) {
                    $buyerAsset->increment('quantity', $locked->quantity);

                    $affected = PlayerAsset::where('id', $locked->id)
                        ->where('status', PlayerAsset::STATUS_LOCKED)
                        ->update(['status' => PlayerAsset::STATUS_TRANSFERRED, 'quantity' => 0]);

                    if ($affected === 0) {
                        throw new AssetNotAvailableException('El activo ya no está bloqueado por este escrow.');
                    }

                    return $buyerAsset->fresh();
                }
            }

            $affected = PlayerAsset::where('id', $locked->id)
                ->where('status', PlayerAsset::STATUS_LOCKED)
                ->where('locked_by_escrow_id', $escrowId)
                ->update([
                    'status'               => PlayerAsset::STATUS_AVAILABLE,
                    'owner_id'             => $newOwnerId,
                    'locked_by_escrow_id'  => null,
                    'acquired_via'         => 'MARKETPLACE_PURCHASE',
                    'acquired_reference'   => $escrowId,
                ]);

            if ($affected === 0) {
                throw new AssetNotAvailableException('El activo ya no está bloqueado por este escrow.');
            }

            return $locked->fresh();
        });
    }

    /**
     * Consume quantity unidades de un asset AVAILABLE. Si se agota, pasa a
     * CONSUMED; si queda remanente, se resta y se mantiene AVAILABLE.
     */
    public function consume(PlayerAsset $asset, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('quantity debe ser mayor a 0.');
        }

        DB::transaction(function () use ($asset, $quantity) {
            $locked = PlayerAsset::where('id', $asset->id)
                ->where('status', PlayerAsset::STATUS_AVAILABLE)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new AssetNotAvailableException('El activo no está disponible para consumir.');
            }

            $definition = $locked->assetDefinition;
            if (!$definition->consumable) {
                throw new \DomainException('Esta definición de activo no es consumible.');
            }

            if ($locked->quantity < $quantity) {
                throw new InsufficientAssetQuantityException('No hay suficiente cantidad para consumir.');
            }

            if ($locked->quantity === $quantity) {
                $affected = PlayerAsset::where('id', $locked->id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->update(['status' => PlayerAsset::STATUS_CONSUMED]);
            } else {
                $affected = PlayerAsset::where('id', $locked->id)
                    ->where('status', PlayerAsset::STATUS_AVAILABLE)
                    ->where('quantity', $locked->quantity)
                    ->decrement('quantity', $quantity);
            }

            if (!$affected) {
                throw new AssetNotAvailableException('El activo no está disponible para consumir.');
            }
        });
    }
}
