<?php

namespace Database\Seeders;

use App\Models\Settings\Setting;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $snowflake = new Snowflake();
        $timestamp = now();

        $entries = [
            ['param' => 'LOGIN', 'status' => 'ACTIVE', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['param' => 'REGISTER', 'status' => 'ACTIVE', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];

        foreach ($entries as &$entry) {
            // Agregar ID Snowflake
            $entry['id'] = $snowflake->id();
        }

        // Inserción usando upsert para evitar duplicados
        foreach ($entries as $entry) {
            Setting::updateOrCreate(
                ['param' => $entry['param']], // Condición única
                $entry
            );
        }
    }
}
