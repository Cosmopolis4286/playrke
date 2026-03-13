<?php

namespace Database\Seeders;

use App\Models\UserPreference;
use Illuminate\Database\Seeder;

class UserPreferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        $defaultPreferences = [
            // Preferencias de Exchange Rate (para tu implementación)
            [
                'user_id' => null, // Global
                'category' => 'exchange_rate',
                'key' => 'auto_update_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'status' => 'ACTIVE',
                'sort_order' => 1,
                'description' => 'Actualización automática de tasas de cambio',
                'default_value' => 'false',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'user_id' => null,
                'category' => 'exchange_rate',
                'key' => 'update_frequency',
                'value' => 'hourly',
                'type' => 'string',
                'status' => 'ACTIVE',
                'sort_order' => 2,
                'description' => 'Frecuencia de actualización automática',
                'allowed_values' => json_encode(['hourly', 'daily', 'weekly']),
                'default_value' => 'hourly',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'user_id' => null,
                'category' => 'exchange_rate',
                'key' => 'notification_on_update',
                'value' => 'true',
                'type' => 'boolean',
                'status' => 'ACTIVE',
                'sort_order' => 3,
                'description' => 'Notificar cuando se actualicen las tasas',
                'default_value' => 'true',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],

            // Preferencias de interfaz (opcionales)
            [
                'user_id' => null,
                'category' => 'ui',
                'key' => 'theme',
                'value' => 'light',
                'type' => 'string',
                'status' => 'ACTIVE',
                'sort_order' => 1,
                'description' => 'Tema visual de la interfaz',
                'allowed_values' => json_encode(['light', 'dark', 'auto']),
                'default_value' => 'light',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'user_id' => null,
                'category' => 'ui',
                'key' => 'language',
                'value' => 'es',
                'type' => 'string',
                'status' => 'ACTIVE',
                'sort_order' => 2,
                'description' => 'Idioma de la interfaz',
                'allowed_values' => json_encode(['es', 'en', 'pt']),
                'default_value' => 'es',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],

            // Preferencias de notificaciones (opcionales)
            [
                'user_id' => null,
                'category' => 'notifications',
                'key' => 'email_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'status' => 'ACTIVE',
                'sort_order' => 1,
                'description' => 'Recibir notificaciones por email',
                'default_value' => 'true',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        // Insertar preferencias por defecto
        foreach ($defaultPreferences as $preference) {
            UserPreference::updateOrCreate(
                [
                    'user_id' => $preference['user_id'],
                    'category' => $preference['category'],
                    'key' => $preference['key'],
                ],
                $preference
            );
        }

        $this->command->info('User preferences seeded successfully.');
    }
}
