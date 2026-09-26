<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla 'subsidiaries'.
     */
    public function up(): void
    {
        Schema::create('subsidiaries', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la sucursal');

            // Datos de la sucursal
            $table->string('name', 30)->comment('Nombre de la sucursal');
            $table->string('brand', 20)->nullable()->comment('Marca asociada');
            $table->string('product', 30)->nullable()->comment('Producto principal');
            $table->string('link_route', 255)->nullable()->comment('Ruta de enlace o URL');
            $table->string('token', 255)->comment('Token de autenticación');
            $table->string('security_key', 255)->comment('Llave de seguridad');

            // Comisiones y teléfono
            $table->double('commission', 8, 2)->default(0)->comment('Comisión (%) por defecto');
            // 20 (no 14): el formato canónico validado por PhonePatterns/
            // ValidPhoneForCountry para VE es "+58 0414-1234567" (16 chars) y
            // para BR "+55 (11) 91234-5678" (19 chars) — con 14 era
            // físicamente imposible guardar un teléfono que pasara esa
            // validación (AdminSubsidiaryRequest exigía 'max:14' junto con la
            // regla de formato en la misma columna). Mismo ancho que
            // commerces/sellers/customers/treasurers.phone.
            $table->string('phone', 20)->nullable()->comment('Teléfono de contacto');

            // Relaciones Snowflake
            $table->foreignSnowflake('commerce_id')
                ->nullable()
                ->constrained('commerces')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Comercio asociado (opcional)');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario creador');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País de la sucursal');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->comment('Moneda utilizada');

            // Estado de la sucursal — incluye DELETED: AdminSubsidiaryCrudService::delete()
            // (soft delete admin, igual que Treasurer/Commerce) marca status='DELETED'
            // antes de softDeletes(); sin este valor el ENUM trunca el update
            // (SQLSTATE 01000 "Data truncated for column 'status'").
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->comment('Estado activo/inactivo/bloqueado/eliminado');

            // Timestamps automáticos
            $table->timestamps();

            // Soft deletes — BaseModel (que Subsidiary extiende) usa
            // SoftDeletes por defecto; sin esta columna, delete() fallaría.
            $table->softDeletes();

            // Índices compuestos para consultas frecuentes
            $table->index(['status', 'commerce_id', 'country_id']);
        });

        // FK de sellers.subsidiary_id → subsidiaries.id, agregada aquí (no
        // en 2024_01_01_000402_create_sellers_table) porque esta tabla se
        // crea después — declarar el constraint inline en `sellers` fallaría
        // con "Failed to open the referenced table 'subsidiaries'".
        Schema::table('sellers', function (Blueprint $table) {
            $table->foreign('subsidiary_id')
                ->references('id')->on('subsidiaries')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Revierte la migración eliminando la tabla 'subsidiaries'.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['subsidiary_id']);
        });

        Schema::dropIfExists('subsidiaries');
    }
};
