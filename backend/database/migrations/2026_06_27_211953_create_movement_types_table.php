<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movement_types')) {
            return;
        }

        Schema::create('movement_types', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('code', 30)->unique()
                ->comment('Código único usado como valor real en wallet_movements.type (DEPOSIT, WITHDRAWAL, BONUS...)');

            $table->string('label', 100)
                ->comment('Nombre visible es-ES (ej: Depósito)');

            $table->string('description', 255)->nullable();

            $table->string('icon', 60)->default('fas fa-circle')
                ->comment('Clase FontAwesome, ej: fas fa-arrow-down');

            $table->string('color', 9)->default('#718096')
                ->comment('Color hex para UI, ej: #16a34a');

            $table->boolean('is_credit')->default(false)
                ->comment('true = suma saldo a la wallet, false = resta saldo');

            $table->boolean('requires_approval')->default(false)
                ->comment('true = se crea en PENDING y pasa por flujo approve/reject; false = siempre COMPLETED');

            $table->boolean('is_system')->default(false)
                ->comment('true = uno de los tipos originales del sistema; protege contra borrado/edición de code');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'is_credit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_types');
    }
};
