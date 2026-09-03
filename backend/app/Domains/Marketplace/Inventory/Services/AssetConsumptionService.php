<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Services;

use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;

/**
 * Punto único por el que un juego (Bingo, Trivia, o el futuro Cargas)
 * consume un activo del jugador durante una partida — ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 9 y 31.
 *
 * No conoce nada de ningún juego concreto: recibe game_group como filtro de
 * seguridad opcional (evita que Trivia consuma sin querer un booster
 * definido para Bingo si dos definiciones comparten código por error), y
 * game_id/room_id solo para trazabilidad en metadata, nunca como lógica de
 * negocio. InventoryService::consume() ya hace todo el trabajo pesado de
 * concurrencia (lockForUpdate + guards) — este servicio es la fachada que
 * un juego debe llamar en vez de tocar InventoryService/PlayerAsset
 * directamente, así queda un solo lugar para auditar "qué juego consumió
 * qué activo, cuándo, en qué partida".
 */
final class AssetConsumptionService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    /**
     * Consume una unidad (o $quantity) de la primera instancia AVAILABLE
     * del usuario para el AssetDefinition indicado por code. Lanza
     * AssetNotAvailableException si el usuario no posee ninguna instancia
     * disponible — el juego decide qué hacer con eso (bloquear la acción,
     * mostrar "no tienes este booster").
     *
     * @param string|null $expectedGameGroup Si se indica, valida que la
     *   definición pertenezca a ese game_group — evita consumos cruzados
     *   entre juegos por error de código, sin acoplar este servicio a
     *   ningún juego en particular.
     */
    public function consumeByCode(
        string $userId,
        string $assetDefinitionCode,
        int $quantity = 1,
        ?string $expectedGameGroup = null,
        ?string $gameRoomId = null,
    ): void {
        $definition = AssetDefinition::catalog()->get($assetDefinitionCode);

        if (!$definition || !$definition->consumable) {
            throw new AssetNotAvailableException("El activo '{$assetDefinitionCode}' no existe o no es consumible.");
        }

        if ($expectedGameGroup !== null && $definition->game_group !== $expectedGameGroup) {
            throw new AssetNotAvailableException("El activo '{$assetDefinitionCode}' no pertenece a {$expectedGameGroup}.");
        }

        $asset = PlayerAsset::where('owner_id', $userId)
            ->where('asset_definition_id', $definition->id)
            ->where('status', PlayerAsset::STATUS_AVAILABLE)
            ->where('quantity', '>=', $quantity)
            ->first();

        if (!$asset) {
            throw new AssetNotAvailableException("No tienes suficiente '{$assetDefinitionCode}' disponible para usar.");
        }

        $this->inventoryService->consume($asset, $quantity);
    }

    /**
     * Cuántas unidades disponibles tiene el usuario de un AssetDefinition,
     * sin consumir nada — para que la UI del juego muestre "te quedan N"
     * antes de intentar usarlo.
     */
    public function availableQuantity(string $userId, string $assetDefinitionCode): int
    {
        $definition = AssetDefinition::catalog()->get($assetDefinitionCode);
        if (!$definition) {
            return 0;
        }

        return (int) PlayerAsset::where('owner_id', $userId)
            ->where('asset_definition_id', $definition->id)
            ->where('status', PlayerAsset::STATUS_AVAILABLE)
            ->sum('quantity');
    }

    /**
     * Otorga una unidad (o $quantity) de un AssetDefinition a un jugador
     * como recompensa de juego (ej. premio de evento, drop de partida). No
     * reemplaza a GamePrizeService (dinero) — es el equivalente para
     * activos no monetarios.
     */
    public function grantByCode(
        string $userId,
        string $assetDefinitionCode,
        int $quantity,
        string $acquiredReference,
    ): PlayerAsset {
        $definition = AssetDefinition::catalog()->get($assetDefinitionCode);

        if (!$definition) {
            throw new AssetNotAvailableException("El activo '{$assetDefinitionCode}' no existe.");
        }

        return $this->inventoryService->grant(
            definition: $definition,
            ownerId: $userId,
            quantity: $quantity,
            acquiredVia: 'GAME_PRIZE',
            acquiredReference: $acquiredReference,
        );
    }
}
