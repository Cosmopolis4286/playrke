<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->string('prefix')->unique();
            $table->enum('type', ['web', 'api'])->default('web')->comment('Tipo de rol: web o api');
            $table->string('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Orden/nivel en el organigrama de roles (menor = más alto en jerarquía)');
            $table->string('icon')->nullable()->comment('Clase de icono FontAwesome para el organigrama, ej. fa-crown');
            $table->string('color', 20)->nullable()->comment('Color hex/token para el organigrama, ej. #7c3aed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
