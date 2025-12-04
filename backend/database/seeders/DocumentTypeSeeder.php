<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Repositories\User\Shared\DocumentTypeRepository;
use App\Repositories\Countries\CountryRepository;
use BradieTilley\Snowflake\Snowflake;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Resolver repositorios usando IoC
        $countryRepo  = app(CountryRepository::class);
        $documentRepo = app(DocumentTypeRepository::class);

        $snowflake = new Snowflake();

        // Obtener países por ISO
        $brasil    = $countryRepo->getCountryByIsoCode('BR');
        $venezuela = $countryRepo->getCountryByIsoCode('VE');

        // IDs (si existen)
        $brasilId    = $brasil?->id;
        $venezuelaId = $venezuela?->id;

        // Datos a insertar o actualizar
        $documentTypes = [
            ['value' => 'CPF', 'description' => 'CPF',        'country_id' => $brasilId,    'is_active' => true],
            ['value' => 'V',   'description' => 'Venezolano', 'country_id' => $venezuelaId, 'is_active' => true],
            ['value' => 'E',   'description' => 'Extranjero', 'country_id' => $venezuelaId, 'is_active' => true],
            ['value' => 'J',   'description' => 'Comercio',   'country_id' => $venezuelaId, 'is_active' => true],
        ];

        // upsert manual evitando duplicados
        foreach ($documentTypes as $type) {
            if (! $type['country_id']) {
                continue;
            }

            $documentRepo->updateOrCreate(
                [
                    'code'       => $type['value'],
                    'country_id' => $type['country_id'],
                ],
                [
                    'id'          => $snowflake->id(),
                    'description' => $type['description'],
                    'is_active'   => $type['is_active'],
                ]
            );
        }
    }
}
