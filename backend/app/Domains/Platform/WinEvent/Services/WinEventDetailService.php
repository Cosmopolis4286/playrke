<?php

namespace App\Domains\Platform\WinEvent\Services;

use App\Domains\Platform\WinEvent\Contracts\WinEventDetailResolver;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Support\WinEventDetailSection;
use Illuminate\Support\Facades\Log;

/**
 * Arma la vista de detalle (solo admin) de un evento de ganancia de
 * cualquier juego: datos comunes de WinEvent + las secciones que aporte el
 * resolver registrado para su game_slug (ver WinEventDetailResolver::TAG).
 */
final class WinEventDetailService
{
    private const SOURCE_TYPE_LABELS = [
        WinEvent::SOURCE_GAME_ENGINE  => 'Motor de juego',
        WinEvent::SOURCE_ADMIN_MANUAL => 'Registro manual',
        WinEvent::SOURCE_EXTERNAL_API => 'API externa',
    ];

    private const PRIZE_KIND_LABELS = [
        WinEvent::PRIZE_MONEY   => 'Dinero',
        WinEvent::PRIZE_ITEM    => 'Objeto',
        WinEvent::PRIZE_SERVICE => 'Servicio',
    ];

    /** @var array<string, WinEventDetailResolver> */
    private array $resolvers = [];

    /**
     * @param iterable<WinEventDetailResolver> $resolvers inyectados vía tag
     */
    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) {
            $this->resolvers[$resolver->gameSlug()] = $resolver;
        }
    }

    public function build(WinEvent $winEvent): array
    {
        $winEvent->loadMissing([
            'user:id,first_name,last_name,username,email',
            'reportedByUser:id,first_name,last_name,username',
            'currency:id,iso_code,symbol,decimal_places',
        ]);

        $resolver = $this->resolvers[$winEvent->game_slug] ?? null;
        $gameSections = $resolver ? $this->resolveSafely($resolver, $winEvent) : [];

        $sections = array_filter([
            ...$gameSections,
            $this->metadataSection($winEvent, $resolver?->consumedMetadataKeys() ?? []),
        ], fn (WinEventDetailSection $s) => ! $s->isEmpty());

        return [
            'event'           => $this->eventPayload($winEvent),
            'winner'          => $this->winnerPayload($winEvent),
            'sections'        => array_values(array_map(fn (WinEventDetailSection $s) => $s->toArray(), $sections)),
            'has_game_detail' => $gameSections !== [],
        ];
    }

    /**
     * Un resolver roto (registro de origen con datos inconsistentes, BD de
     * trivia caída, etc.) no debe tumbar la vista: se degrada a los datos
     * genéricos del evento.
     *
     * @return WinEventDetailSection[]
     */
    private function resolveSafely(WinEventDetailResolver $resolver, WinEvent $winEvent): array
    {
        try {
            return $resolver->resolve($winEvent);
        } catch (\Throwable $e) {
            Log::warning('[WinEventDetailService] Resolver falló, se muestra solo el detalle genérico', [
                'win_event_id' => (string) $winEvent->id,
                'game_slug'    => $winEvent->game_slug,
                'error'        => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function eventPayload(WinEvent $e): array
    {
        $currency = $e->currency;

        return [
            'id'                => (string) $e->id,
            'game_slug'         => $e->game_slug,
            'game_name'         => $e->game_name,
            'prize_kind'        => $e->prize_kind,
            'prize_kind_label'  => self::PRIZE_KIND_LABELS[$e->prize_kind] ?? $e->prize_kind,
            'prize_label'       => $e->prize_label,
            'amount_usd'        => $e->amount_usd !== null ? (float) $e->amount_usd : null,
            'amount_local'      => $e->amount_local !== null ? (float) $e->amount_local : null,
            'currency'          => $currency ? [
                'iso_code'       => $currency->iso_code,
                'symbol'         => $currency->symbol,
                'decimal_places' => (int) ($currency->decimal_places ?? 2),
            ] : null,
            'source_type'       => $e->source_type,
            'source_type_label' => self::SOURCE_TYPE_LABELS[$e->source_type] ?? $e->source_type,
            'source_reference'  => $e->source_reference,
            'reported_by'       => $e->reportedByUser?->full_name ?: $e->reportedByUser?->username,
            'created_at'        => $e->created_at?->toISOString(),
        ];
    }

    /** Nombre SIN enmascarar: esta vista es exclusiva de superadmin/admin. */
    private function winnerPayload(WinEvent $e): array
    {
        $user = $e->user;

        return [
            'has_account' => $user !== null,
            'user_id'     => $user ? (string) $user->id : null,
            'name'        => $e->resolveWinnerName(),
            'username'    => $user?->username,
            'email'       => $user?->email,
        ];
    }

    /**
     * Metadata cruda que ningún resolver representó — garantiza que un juego
     * nuevo sin resolver muestre igualmente todo lo que reportó.
     */
    private function metadataSection(WinEvent $e, array $consumedKeys): WinEventDetailSection
    {
        $section = WinEventDetailSection::make('metadata', 'Datos adicionales', 'fas fa-database');

        foreach ((array) ($e->metadata ?? []) as $key => $value) {
            if (in_array($key, $consumedKeys, true)) {
                continue;
            }

            $section->item(
                ucfirst(str_replace('_', ' ', (string) $key)),
                is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE),
                is_scalar($value) ? WinEventDetailSection::TYPE_TEXT : WinEventDetailSection::TYPE_CODE,
            );
        }

        return $section;
    }
}
