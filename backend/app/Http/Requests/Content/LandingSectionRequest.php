<?php

namespace App\Http\Requests\Content;

use App\Http\Requests\BaseRequest;
use App\Models\Content\LandingSection;
use App\Services\Auth\PermissionService;

/**
 * Valida el contenido editorial de una sección de landing. Las reglas
 * dependen de `section_key` (cada sección tiene una forma de contenido
 * distinta) — se resuelven dinámicamente contra el modelo ya bindeado
 * por ruta.
 */
class LandingSectionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return PermissionService::checkPrefix(['superadmin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'array'],
            ...$this->contentRules(),
        ];
    }

    private function contentRules(): array
    {
        /** @var LandingSection|null $section */
        $section = $this->route('landingSection');

        return match ($section?->section_key) {
            'hero' => [
                'content.eyebrowPrefix' => ['required', 'string', 'max:60'],
                'content.eyebrowSuffix' => ['required', 'string', 'max:60'],
                'content.title' => ['required', 'string', 'max:150'],
                'content.subtitle' => ['required', 'string', 'max:300'],
                'content.ctaPrimaryLabel' => ['required', 'string', 'max:40'],
                'content.ctaSecondaryLabel' => ['required', 'string', 'max:40'],
                'content.trustItems' => ['required', 'array', 'size:3'],
                'content.trustItems.*' => ['required', 'string', 'max:60'],
            ],
            'salas' => [
                'content.title' => ['required', 'string', 'max:120'],
                'content.subtitle' => ['required', 'string', 'max:200'],
                'content.ctaLabel' => ['required', 'string', 'max:40'],
            ],
            'verificar' => [
                'content.eyebrow' => ['required', 'string', 'max:60'],
                'content.title' => ['required', 'string', 'max:150'],
                'content.paragraph' => ['required', 'string', 'max:400'],
                'content.steps' => ['required', 'array', 'size:3'],
                'content.steps.*' => ['required', 'string', 'max:150'],
            ],
            'invita' => [
                'content.eyebrow' => ['required', 'string', 'max:60'],
                'content.title' => ['required', 'string', 'max:150'],
                'content.paragraph' => ['required', 'string', 'max:400'],
                'content.steps' => ['required', 'array', 'size:3'],
                'content.steps.*' => ['required', 'string', 'max:150'],
                'content.cardLead' => ['required', 'string', 'max:150'],
                'content.ctaLabel' => ['required', 'string', 'max:40'],
            ],
            'ganadores' => [
                'content.eyebrow' => ['required', 'string', 'max:60'],
                'content.title' => ['required', 'string', 'max:150'],
                'content.paragraph' => ['required', 'string', 'max:400'],
            ],
            'cta_final' => [
                'content.eyebrow' => ['required', 'string', 'max:60'],
                'content.title' => ['required', 'string', 'max:150'],
                'content.paragraph' => ['required', 'string', 'max:400'],
                'content.ctaLabel' => ['required', 'string', 'max:40'],
            ],
            'footer' => [
                'content.tagline' => ['required', 'string', 'max:200'],
                'content.socials' => ['required', 'array'],
                'content.socials.facebook' => ['nullable', 'url', 'max:255'],
                'content.socials.instagram' => ['nullable', 'url', 'max:255'],
                'content.socials.telegram' => ['nullable', 'url', 'max:255'],
                'content.copyrightSuffix' => ['required', 'string', 'max:100'],
                'content.trustLine' => ['required', 'string', 'max:150'],
            ],
            'jackpot' => [],
            default => [],
        };
    }
}
