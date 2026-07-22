<?php

namespace Database\Seeders;

use App\Models\Role;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();
        $snowflake = new Snowflake();

        // Definimos todos los roles del sistema
        // 'type' indica si es rol web o API
        $roles = [
            ['name' => 'SuperAdmin', 'prefix' => 'superadmin', 'description' => 'Super Administrador', 'status' => true, 'type' => 'web'],
            ['name' => 'Jugador',   'prefix' => 'player',     'description' => 'Jugador registrado', 'status' => true, 'type' => 'web'],
            ['name' => 'Admin',     'prefix' => 'admin',      'description' => 'Administrador', 'status' => true, 'type' => 'web'],
            ['name' => 'Comercio',  'prefix' => 'commerce',   'description' => 'Comercio', 'status' => true, 'type' => 'web'],
            ['name' => 'Sucursal',  'prefix' => 'subsidiary', 'description' => 'Sucursal de un comercio', 'status' => true, 'type' => 'web'],
            ['name' => 'Vendedor',  'prefix' => 'seller',     'description' => 'Vendedor asociado', 'status' => true, 'type' => 'web'],
            ['name' => 'Proveedor', 'prefix' => 'provider',   'description' => 'Proveedor del sistema', 'status' => true, 'type' => 'api'],      // API guard
            ['name' => 'Cliente',   'prefix' => 'customer',   'description' => 'Cliente que usa nuestos servicios',               'status' => true, 'type' => 'web'],      // Cambiado a 'web' para acceso al menú
            ['name' => 'Tesorero',  'prefix' => 'treasurer',  'description' => 'Responsable financiero', 'status' => true, 'type' => 'web'],
        ];

        // Añadimos ID único (Snowflake) y timestamps
        foreach ($roles as &$role) {
            $role['id'] = $snowflake->id();
            $role['created_at'] = $timestamp;
            $role['updated_at'] = $timestamp;

            // ------------------------------
            // Asignación automática del guard
            // - Roles web → 'web'
            // - Roles API → 'api'
            // ------------------------------
            $role['guard_name'] = $role['type'] === 'api' ? 'api' : 'web';
        }
        unset($role); // evita que la referencia del foreach anterior corrompa el último elemento al reusar $role abajo

        // Creamos o actualizamos cada rol en la base de datos.
        // Usa App\Models\Role (no Spatie\Permission\Models\Role): mismo
        // registro/tabla, pero App\Models\Role es el modelo real que consume
        // el resto del sistema (User::roles(), RoleUserRepository, etc.) y
        // tiene el cast id => string vía SnowflakeId.
        foreach ($roles as $role) {
            $existingRole = Role::where('name', $role['prefix'])
                ->where('guard_name', $role['guard_name'])
                ->first();

            if (!$existingRole) {
                $newRole = new Role();
                $newRole->id = (string) $snowflake->id();
                $newRole->name = $role['prefix'];           // player, admin, etc
                $newRole->guard_name = $role['guard_name']; // web o api
                $newRole->prefix = $role['prefix'];
                $newRole->type = $role['type'];
                $newRole->description = $role['description'];
                $newRole->status = $role['status'];
                $newRole->save();
            }
        }

        $this->command->info('✅ Roles creados exitosamente');
    }
}
