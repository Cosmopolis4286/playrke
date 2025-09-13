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
            $table->snowflake('id')->primary(); // Snowflake en lugar de auto-increment
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->string('prefix')->unique();
            $table->enum('type', ['web', 'api'])->default('web')->comment('Tipo de rol: web o api');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->comment('true = activo, false = inactivo');
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
