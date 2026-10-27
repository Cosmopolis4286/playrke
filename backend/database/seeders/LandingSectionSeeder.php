<?php

namespace Database\Seeders;

use App\Models\Content\LandingSection;
use Illuminate\Database\Seeder;

/**
 * Precarga las 8 secciones de la Landing pública con el copy que hoy vive
 * hardcodeado en Landing.vue — idempotente (updateOrCreate por section_key)
 * para poder correr seguro en cualquier entorno, incluyendo producción en
 * el deploy. Sin esto, tras la migración la landing quedaría sin contenido.
 */
class LandingSectionSeeder extends Seeder
{
    public function run() : void
    {
        $sections = [
            [
                'section_key' => 'hero',
                'label' => 'Hero',
                'sort_order' => 10,
                'content' => [
                    'eyebrowPrefix' => '',
                    'eyebrowSuffix' => 'jugadores en salas activas',
                    'title' => 'Juega en vivo.<br/><em>Gana en serio.</em>',
                    'subtitle' => 'Salas de bingo con premios reales, actualizadas en tiempo real. Ves el pozo, ves quién compró, ves quién ganó.',
                    'ctaPrimaryLabel' => 'Crear cuenta gratis',
                    'ctaSecondaryLabel' => 'Ver salas activas',
                    'trustItems' => ['Retiros verificados', 'Cartones auditables por serial', 'Soporte 24/7'],
                ],
            ],
            [
                'section_key' => 'jackpot',
                'label' => 'Jackpot',
                'sort_order' => 20,
                'content' => [],
            ],
            [
                'section_key' => 'salas',
                'label' => 'Salas destacadas',
                'sort_order' => 30,
                'content' => [
                    'title' => 'Donde el pozo crece en vivo',
                    'subtitle' => 'Salas activas ahora mismo. Cada cartón suma al premio.',
                    'ctaLabel' => 'Ver todas las salas →',
                ],
            ],
            [
                'section_key' => 'verificar',
                'label' => 'Verificar cartón',
                'sort_order' => 40,
                'content' => [
                    'eyebrow' => 'Sin necesidad de cuenta',
                    'title' => 'Verifica tu cartón como un ticket físico',
                    'paragraph' => '¿Compraste un cartón por WhatsApp o en punto físico? Confírmalo en segundos con el número de serial y los últimos 4 dígitos de tu teléfono.',
                    'steps' => [
                        'Ingresa el serial impreso en tu cartón',
                        'Ingresa los últimos 4 dígitos de tu teléfono',
                        'Ve el estado, la sala y el resultado al instante',
                    ],
                ],
            ],
            [
                'section_key' => 'invita',
                'label' => 'Invita y gana',
                'sort_order' => 50,
                'content' => [
                    'eyebrow' => 'Programa de referidos',
                    'title' => 'Invita y gana',
                    'paragraph' => 'Comparte tu enlace único cuando crees tu cuenta. Cuando un amigo se registre y verifique su cuenta, ganas XP.',
                    'steps' => [
                        'Crea tu cuenta y obtén tu enlace de invitación personal',
                        'Comparte tu enlace con amigos',
                        'Cuando se registren y verifiquen su cuenta, ganas XP automáticamente',
                    ],
                    'cardLead' => 'Tu enlace de invitación te espera dentro de tu perfil',
                    'ctaLabel' => 'Crear cuenta gratis',
                ],
            ],
            [
                'section_key' => 'ganadores',
                'label' => 'Ganadores',
                'sort_order' => 60,
                'content' => [
                    'eyebrow' => 'Ranking de la semana',
                    'title' => 'Los mejores jugadores de los últimos 7 días',
                    'paragraph' => 'Suma de ganancias en dinero por jugador, actualizado cada pocos minutos. Los nombres se muestran parcialmente por privacidad.',
                ],
            ],
            [
                'section_key' => 'cta_final',
                'label' => 'CTA final',
                'sort_order' => 70,
                'content' => [
                    'eyebrow' => 'Empieza hoy',
                    'title' => 'Tu primera sala te espera',
                    'paragraph' => 'Crear cuenta toma menos de un minuto. El pozo no espera.',
                    'ctaLabel' => 'Crear cuenta gratis',
                ],
            ],
            [
                'section_key' => 'footer',
                'label' => 'Footer',
                'sort_order' => 80,
                'content' => [
                    'tagline' => 'Bingo en vivo con premios reales. Ves el pozo, ves quién gana.',
                    'socials' => [
                        'facebook' => 'https://www.facebook.com/my.play.rke',
                        'instagram' => 'https://www.instagram.com/my.play.rke',
                        'telegram' => 'https://t.me/+rV3KWBuuKOtjYzkx',
                    ],
                    'copyrightSuffix' => 'Todos los derechos reservados.',
                    'trustLine' => 'Retiros verificados · Cartones auditables por serial',
                ],
            ],
        ];

        foreach ($sections as $section) {
            LandingSection::query()->updateOrCreate(
                ['section_key' => $section['section_key']],
                [
                    'label' => $section['label'],
                    'content' => $section['content'],
                    'sort_order' => $section['sort_order'],
                    'status' => 'ACTIVE',
                    'is_removable' => false,
                ],
            );
        }
    }
}
