<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Users\Shared\DocumentType;
use App\Repositories\Countries\CountryRepository;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Resolver repositorio de países usando IoC
        $countryRepo = app(CountryRepository::class);

        // Obtener países por ISO
        $brasil    = $countryRepo->getCountryByIsoCode('BR');
        $venezuela = $countryRepo->getCountryByIsoCode('VE');

        // IDs (si existen)
        $brasilId    = $brasil?->id;
        $venezuelaId = $venezuela?->id;

        // Datos a insertar o actualizar
        $documentTypes = [
            ['value' => 'CPF', 'description' => 'CPF',        'country_id' => $brasilId,    'status' => true],
            ['value' => 'V',   'description' => 'Venezolano', 'country_id' => $venezuelaId, 'status' => true],
            ['value' => 'E',   'description' => 'Extranjero', 'country_id' => $venezuelaId, 'status' => true],
            ['value' => 'J',   'description' => 'Comercio',   'country_id' => $venezuelaId, 'status' => true],
        ];

        // Insertar o actualizar cada tipo de documento de forma idempotente
        foreach ($documentTypes as $type) {
            if (!$type['country_id']) {
                continue;
            }

            DocumentType::updateOrCreate(
                [
                    'code'       => $type['value'],
                    'country_id' => $type['country_id'],
                ],
                [
                    'description' => $type['description'],
                    'status'   => $type['status'],
                ]
            );
        }
    }
}
