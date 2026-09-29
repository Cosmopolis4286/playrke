<?php

namespace App\Domains\Platform\WinEvent\Contracts;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Support\WinEventDetailSection;

/**
 * Aporta el detalle específico de un juego a la vista admin de un evento de
 * ganancia (/admin/win-event/{id}/show).
 *
 * Cada juego que reporte ganancias vía WinEventService puede registrar su
 * propio resolver con el tag WinEventDetailResolver::TAG en su ServiceProvider.
 * Un game_slug sin resolver sigue funcionando: la vista muestra los datos
 * genéricos del evento + su metadata cruda.
 */
interface WinEventDetailResolver
{
    public const TAG = 'win-event.detail-resolvers';

    /** game_slug que atiende este resolver (el mismo que se pasa a WinEventService). */
    public function gameSlug(): string;

    /**
     * Secciones de detalle del juego a partir de source_reference/metadata.
     * Debe tolerar registros de origen borrados o inexistentes (devolver []).
     *
     * @return WinEventDetailSection[]
     */
    public function resolve(WinEvent $winEvent): array;

    /**
     * Claves de metadata ya representadas en las secciones — se omiten del
     * bloque genérico "Datos adicionales" para no duplicar información.
     *
     * @return string[]
     */
    public function consumedMetadataKeys(): array;
}
