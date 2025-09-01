<?php

namespace Database\Seeders;

use App\Models\User\Shared\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'document_type' con los tipos de documento.
     *
     * @return void
     */
    public function run(): void
    {
        // Lista de tipos de documento predefinidos
        $documentTypes = [
            ['value' => 'CPF', 'description' => 'CPF', 'country' => 2, 'status' => 1],
            ['value' => 'V',   'description' => 'Venezolano', 'country' => 3, 'status' => 1],
            ['value' => 'E',   'description' => 'Extranjero', 'country' => 3, 'status' => 1],
            ['value' => 'J',   'description' => 'Comercio', 'country' => 3, 'status' => 1],
        ];

        // Inserta o actualiza los registros según 'value' y 'country' para evitar duplicados
        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(
                ['value' => $type['value'], 'country' => $type['country']], // Clave única compuesta
                ['description' => $type['description'], 'status' => $type['status']]
            );
        }
    }
}
