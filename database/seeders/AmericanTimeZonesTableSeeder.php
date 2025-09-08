<?php

namespace Database\Seeders;

use App\Models\Countries\Country;
use App\Models\User\Shared\TimeZone;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class AmericanTimeZonesTableSeeder extends Seeder
{
    public function run()
    {
        $timezones = [
            'Venezuela' => ['America/Caracas'],
            'Colombia'  => ['America/Bogota'],
            'Chile'     => ['America/Santiago', 'Pacific/Easter'],
            'Brazil'    => [
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

        $snowflake = new Snowflake();
        $data = [];

        foreach ($timezones as $countryName => $zones) {
            $countryId = Country::where('name', $countryName)->value('id');

            foreach ($zones as $zone) {
                $offset = (new \DateTimeZone($zone))->getOffset(new \DateTime('now'));
                $utc = 'UTC' . ($offset < 0 ? '-' : '+') . str_pad(floor(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);

                $data[] = [
                    'id'         => $snowflake->id(), // ID Snowflake
                    'name'       => $zone,
                    'utc'        => $utc,
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        TimeZone::insert($data);
    }
}
