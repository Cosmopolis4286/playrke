<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría del flujo completo de Subsidiary confirmó 5 columnas sin
     * valor real de negocio:
     * - token/security_key: se escriben una sola vez al crear
     *   (Str::random(40)) pero NUNCA se leen en ningún punto del código —
     *   secretos vestigiales sin consumidor real.
     * - link_route: se leía solo para mostrarse en la ficha
     *   (AdminSubsidiaryResource/SubsidiaryProfileService), sin que ningún
     *   flujo resolviera una URL real a partir de él.
     * - brand/product: sí tenían flujo completo (form, ficha admin, ficha
     *   propia), pero ningún consumidor de lógica real — no filtran
     *   catálogo, no aparecen en reportes/facturación, solo se mostraban.
     * Las 5 quedan fuera del modelo de negocio actual de la agencia —
     * eliminadas junto con sus referencias en
     * modelo/DTO/Request/Resource/formulario/servicios/frontend.
     */
    public function up(): void
    {
        // Guard de idempotencia — sin esto, un migrate:fresh que corre en
        // paralelo con otra sesión que ya dropeó estas columnas pero sin
        // registrar la migración en `migrations` revienta con "column does
        // not exist" y aborta toda la corrida de tests antes de llegar al
        // seeder (mismo problema ya visto con receipt_hash).
        if (! Schema::hasColumn('subsidiaries', 'token')) {
            return;
        }

        Schema::table('subsidiaries', function (Blueprint $table) {
            $table->dropColumn(['token', 'security_key', 'link_route', 'brand', 'product']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('subsidiaries', 'token')) {
            return;
        }

        Schema::table('subsidiaries', function (Blueprint $table) {
            $table->string('brand', 20)->nullable()->after('name')->comment('Marca asociada');
            $table->string('product', 30)->nullable()->after('brand')->comment('Producto principal');
            $table->string('token', 255)->nullable()->after('product')->comment('Token de autenticación');
            $table->string('security_key', 255)->nullable()->after('token')->comment('Llave de seguridad');
            $table->string('link_route', 255)->nullable()->after('security_key')->comment('Ruta de enlace o URL');
        });
    }
};
