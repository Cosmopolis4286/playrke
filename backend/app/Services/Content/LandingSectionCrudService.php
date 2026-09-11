<?php

namespace App\Services\Content;

use App\DTOs\Content\LandingSectionDTO;
use App\Models\Content\LandingSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent directo, sin repositorio — el catálogo de 8 filas fijas no
 * justifica esa capa de abstracción.
 */
final class LandingSectionCrudService
{
    /** Admin: todas las secciones, ordenadas para el panel. */
    public function list(): Collection
    {
        return LandingSection::query()->orderBy('sort_order')->get();
    }

    public function updateContent(LandingSection $section, LandingSectionDTO $dto): LandingSection
    {
        $section->update($dto->toArray());

        return $section->refresh();
    }

    public function toggleStatus(LandingSection $section): LandingSection
    {
        $section->update([
            'status' => $section->isActive() ? 'INACTIVE' : 'ACTIVE',
        ]);

        return $section->refresh();
    }

    /**
     * Recibe el array completo de section_key en el nuevo orden (no un
     * "mover de X a Y" incremental) — más simple y sin condiciones de
     * carrera: el frontend siempre manda el estado completo tras el drag.
     */
    public function reorder(array $orderedSectionKeys): void
    {
        DB::transaction(function () use ($orderedSectionKeys) {
            foreach ($orderedSectionKeys as $index => $key) {
                LandingSection::where('section_key', $key)->update([
                    'sort_order' => ($index + 1) * 10,
                ]);
            }
        });
    }

    /**
     * Público — solo secciones activas, ordenadas, shape mínimo (key +
     * content). Sin id/status/is_removable: son detalles de administración,
     * no del contrato público.
     */
    public function publicList(): array
    {
        return LandingSection::active()
            ->orderBy('sort_order')
            ->get(['section_key', 'content'])
            ->map(fn (LandingSection $s) => [
                'key' => $s->section_key,
                'content' => $s->content,
            ])
            ->values()
            ->all();
    }
}
