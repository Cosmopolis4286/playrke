<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'wallets'.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id()->comment('ID de la wallet');
            $table->foreignId('user_id')->constrained('users')
                ->cascadeOnUpdate()->restrictOnDelete()->comment('ID del usuario dueño de la wallet');
            $table->foreignId('coin_id')->constrained('coins')
                ->cascadeOnUpdate()->cascadeOnDelete()->comment('ID de la moneda asociada');
            $table->decimal('balance', 15, 2)->default(0.00)->comment('Balance en moneda local');
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Revertir la migración: eliminar tabla 'wallets'.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
