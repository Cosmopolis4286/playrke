<?php

declare(strict_types=1);

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * Migración de datos (no seeder): las agencias (subsidiary) NO gestionan salas
 * de bingo ni de trivia — las crea su comercializadora; la agencia gestiona su
 * red de vendedores y consulta reportes/ganadores. Producción no vuelve a
 * correr seeders (solo `migrate --force`), por eso además de
 * RolePermissionSeeder va esta migración.
 *
 * Revoca games.bingo.manage (que el seeder les daba "con el mismo criterio que
 * Commerce") y, por defensa, cualquier permiso de gestión de juegos que se les
 * haya asignado a mano. Se conservan los de consulta (games.bingo,
 * winners, sales_report, games.trivia.view/games.view/leaderboard.view).
 * Solo toca el ROL: los permisos individuales (UserSpecificPermissionGrant)
 * de agencias concretas se respetan — así se habilita la gestión a una
 * agencia puntual sin dársela a todas.
 *
 * down(): restaura solo games.bingo.manage (el estado previo del seeder).
 */
return new class extends Migration
{
    private const MANAGEMENT_PERMISSIONS = [
        'games.manage',
        'games.bingo.manage',
        'games.trivia.rooms.manage',
        'games.trivia.questions.manage',
    ];

    public function down() : void
    {
        Role::where('name', 'subsidiary')->where('guard_name', 'web')->first()?->givePermissionTo('games.bingo.manage');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function up() : void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $subsidiary = Role::where('name', 'subsidiary')->where('guard_name', 'web')->first();

        if ($subsidiary) {
            foreach (self::MANAGEMENT_PERMISSIONS as $name) {
                if ($subsidiary->permissions()->where('name', $name)->exists()) {
                    $subsidiary->revokePermissionTo($name);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
