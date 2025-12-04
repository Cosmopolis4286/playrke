<?php

namespace Database\Seeders;

use App\Models\Countries\Country;
use App\Models\Users\Shared\TimeZone;
use Illuminate\Database\Seeder;

class AmericanTimeZoneSeeder extends Seeder
{
    public function run()
    {
        $countryTimezones = [
            'VE' => ['America/Caracas'],                     // Venezuela
            'CO' => ['America/Bogota'],                      // Colombia
            'CL' => ['America/Santiago', 'Pacific/Easter'],  // Chile
            'BR' => [
                'America/Sao_Paulo',
                'America/Manaus',
                'America/Belem',
                'America/Recife',
                'America/Fortaleza',
                'America/Campo_Grande',
                'America/Cuiaba',
                'America/Porto_Velho',
                'America/Boa_Vista',
                'America/Eirunepe',
                'America/Rio_Branco',
                'America/Noronha',
            ],
        ];

        $now = now();

        foreach ($countryTimezones as $isoCode2 => $zones) {

            $countryId = Country::where('iso_code_2', $isoCode2)->value('id');

            if (!$countryId) {
                $this->command->warn("País no encontrado: {$isoCode2}");
                continue;
            }

            foreach ($zones as $zone) {

                // Calcular offset dinámico
                $offset = (new \DateTimeZone($zone))
                    ->getOffset(new \DateTime());

                $utc = 'UTC' . ($offset < 0 ? '-' : '+')
                    . str_pad(floor(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);

                /**
                 * updateOrCreate evita duplicados.
                 *
                 * El trait SnowflakeId generará el ID automáticamente
                 * solo cuando el registro NO exista.
                 */
                TimeZone::updateOrCreate(
                    [
                        'name' => $zone,              // clave única lógica
                    ],
                    [
                        'utc'        => $utc,
                        'country_id' => $countryId,
                        'is_active'  => true,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $this->command->info('Zonas horarias americanas registradas sin duplicación.');
    }
}
