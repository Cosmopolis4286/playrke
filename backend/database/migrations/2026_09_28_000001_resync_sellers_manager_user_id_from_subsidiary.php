<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
 * Migración de datos (no seeder): resincroniza sellers.manager_user_id de los
 * vendedores de agencia con el user_id del Commerce actual de su agencia.
 *
 * Convención (ManagerSellerService::resolveManagerForUser()): para un seller
 * con subsidiary_id, manager_user_id es el user_id del Commerce raíz de esa
 * agencia. Dos caminos dejaban datos fuera de esa convención:
 * - AdminSubsidiaryCrudService::update() movía la agencia a otro Commerce sin
 *   tocar a sus sellers (corregido en 54ee1597) — quedaban contados en la red
 *   del Commerce anterior (Commerce::sellers()/networkUserIds()).
 * - Fixtures/datos antiguos con manager_user_id = user de la agencia.
 *
 * Idempotente: solo toca filas desalineadas. Producción no corre seeders tras
 * el deploy inicial (solo `migrate --force`), por eso es migración.
 *
 * Sellers sin Commerce resoluble (p.ej. creados por un 'customer' antes de
 * que dejara de ser manager válido) NO se pueden corregir automáticamente —
 * no hay a qué Commerce asignarlos. Solo se reportan en el log para revisión
 * manual.
 *
 * down(): no-op — el estado anterior era inconsistente, no hay nada que
 * restaurar.
 */
return new class extends Migration
{
    public function down() : void
    {
    }

    public function up() : void
    {
        $updated = DB::table('sellers')
            ->join('subsidiaries', 'subsidiaries.id', '=', 'sellers.subsidiary_id')
            ->join('commerces', 'commerces.id', '=', 'subsidiaries.commerce_id')
            ->whereColumn('sellers.manager_user_id', '<>', 'commerces.user_id')
            ->update(['sellers.manager_user_id' => DB::raw('commerces.user_id')]);

        if ($updated > 0) {
            Log::info('resync_sellers_manager_user_id: sellers de agencia resincronizados', ['count' => $updated]);
        }

        $orphans = DB::table('sellers')
            ->whereNull('sellers.subsidiary_id')
            ->whereNotExists(fn ($q) => $q->from('commerces')->whereColumn('commerces.user_id', 'sellers.manager_user_id'))
            ->pluck('sellers.id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($orphans !== []) {
            Log::warning('resync_sellers_manager_user_id: sellers sin Commerce resoluble (revisión manual)', ['seller_ids' => $orphans]);
        }
    }
};
