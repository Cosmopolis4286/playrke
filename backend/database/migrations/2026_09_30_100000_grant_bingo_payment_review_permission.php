<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Migración de datos (no seeder): crea games.bingo.payments.review y lo
 * asigna a admin y commerce — producción no vuelve a correr seeders (ver
 * 2026_09_27_000001_add_sidebar_and_dashboard_visibility_permissions).
 *
 * Controla la visibilidad del ítem "Revisión de Pagos" del sidebar: la cola
 * solo le sirve a quien puede revisar (admin: todo; commerce: su red — ver
 * BingoCardPaymentReviewController::reviewableSellerUserIds()). Con
 * games.bingo.manage la verían también agencia/cliente, siempre vacía.
 *
 * Idempotente: firstOrCreate() + givePermissionTo() (no duplica pivotes ni
 * toca otros permisos del rol).
 */
return new class extends Migration
{
    private const PERMISSION = 'games.bingo.payments.review';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(
            ['name' => self::PERMISSION, 'guard_name' => 'web'],
            ['description' => 'Revisar (aprobar/rechazar) pagos de ventas de cartones a jugadores externos'],
        );

        foreach (['admin', 'commerce'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo(self::PERMISSION);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
