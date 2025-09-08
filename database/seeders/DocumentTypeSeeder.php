<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Repositories\User\Shared\DocumentTypeRepository;
use App\Repositories\Countries\CountryRepository;
use BradieTilley\Snowflake\Snowflake;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'document_types' con tipos de documento.
     */
    public function run(): void
    {
        $countryRepo = new CountryRepository();
        $documentRepo = new DocumentTypeRepository();

        // Instanciar Snowflake
        $snowflake = new Snowflake();

        // Obtener países por ISO
        $brasil    = $countryRepo->getCountryByIsoCode('BR');
        $venezuela = $countryRepo->getCountryByIsoCode('VE');

        // Validar existencia antes de asignar
        $brasilId    = $brasil?->id ?? null;
        $venezuelaId = $venezuela?->id ?? null;

        // Lista de tipos de documento predefinidos
        $documentTypes = [
            ['value' => 'CPF', 'description' => 'CPF',        'country_id' => $brasilId,    'status' => 1],
            ['value' => 'V',   'description' => 'Venezolano', 'country_id' => $venezuelaId, 'status' => 1],
            ['value' => 'E',   'description' => 'Extranjero', 'country_id' => $venezuelaId, 'status' => 1],
            ['value' => 'J',   'description' => 'Comercio',   'country_id' => $venezuelaId, 'status' => 1],
        ];

        // Inserta o actualiza registros evitando duplicados
        foreach ($documentTypes as $type) {
            if ($type['country_id']) { // solo si existe el país
                $documentRepo->updateOrCreate(
                    ['value' => $type['value'], 'country_id' => $type['country_id']], // clave compuesta
                    [
                        'id' => $snowflake->id(), // Generar ID Snowflake
                        'description' => $type['description'],
                        'status' => $type['status']
                    ]
                );
            }
        }
    }
}
