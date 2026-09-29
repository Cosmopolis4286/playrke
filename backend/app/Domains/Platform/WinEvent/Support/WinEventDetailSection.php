<?php

namespace App\Domains\Platform\WinEvent\Support;

/**
 * Bloque de información de la vista de detalle de un evento de ganancia.
 *
 * Shape estable consumido por Pages/Roles/Admin/WinEvent/Show.vue — los
 * resolvers de cada juego solo arman secciones con este builder, nunca
 * arrays a mano, para que el frontend no necesite conocer ningún juego.
 */
final class WinEventDetailSection
{
    public const TYPE_TEXT     = 'text';
    public const TYPE_MONEY    = 'money';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_BADGE    = 'badge';
    public const TYPE_CODE     = 'code';
    public const TYPE_NUMBERS  = 'numbers';

    /** @var array<int, array{label: string, value: mixed, type: string, variant: ?string}> */
    private array $items = [];

    /** @var array<int, array{title: string, columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, mixed>>}> */
    private array $tables = [];

    private function __construct(
        private readonly string $key,
        private readonly string $title,
        private readonly string $icon,
    ) {}

    public static function make(string $key, string $title, string $icon = 'fas fa-info-circle'): self
    {
        return new self($key, $title, $icon);
    }

    /**
     * Agrega un par etiqueta/valor. Los valores null se omiten para que las
     * secciones no se llenen de "—" cuando el juego no guarda ese dato.
     *
     * @param string|null $variant Solo para TYPE_BADGE: success|warning|danger|info|secondary
     */
    public function item(string $label, mixed $value, string $type = self::TYPE_TEXT, ?string $variant = null): self
    {
        if ($value === null || $value === '') {
            return $this;
        }

        $this->items[] = [
            'label'   => $label,
            'value'   => $value,
            'type'    => $type,
            'variant' => $variant,
        ];

        return $this;
    }

    /**
     * Tabla simple dentro de la sección (ej. ranking de una sala, pagos).
     *
     * @param array<string, string> $columns key => etiqueta
     * @param array<int, array<string, mixed>> $rows
     */
    public function table(string $title, array $columns, array $rows): self
    {
        if ($rows === []) {
            return $this;
        }

        $this->tables[] = [
            'title'   => $title,
            'columns' => collect($columns)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'rows'    => array_values($rows),
        ];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->items === [] && $this->tables === [];
    }

    public function toArray(): array
    {
        return [
            'key'    => $this->key,
            'title'  => $this->title,
            'icon'   => $this->icon,
            'items'  => $this->items,
            'tables' => $this->tables,
        ];
    }
}
