<?php

namespace Database\Seeders;

use App\Models\Settings\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la tabla 'settings'.
     *
     * @return void
     */
    public function run(): void
    {
        $timestamp = now();

        $entries = [
            ['param' => 'LOGIN', 'status' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['param' => 'REGISTER', 'status' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];

        // Inserta o actualiza los registros para evitar duplicados
        foreach ($entries as $entry) {
            Settings::updateOrCreate(
                ['param' => $entry['param']], // Condición única
                $entry
            );
        }
    }
}
