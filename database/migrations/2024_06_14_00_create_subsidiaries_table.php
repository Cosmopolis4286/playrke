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
            $table->id();
            $table->string('name', 30);
            $table->foreignId('country_id')->constrained('countries')->cascadeOnUpdate();
            $table->foreignId('coin_id')->constrained('coins')->cascadeOnUpdate();
            $table->string('phone', 14)->nullable();
            $table->double('commn', 8, 2)->default(0); // mejor precisión
            $table->foreignId('commerce_id')->nullable()->constrained('commerces')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('brand', 20)->nullable();
            $table->string('product', 30)->nullable();
            $table->string('link_route', 255)->nullable();
            $table->string('token', 255);
            $table->string('security_key', 255);
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración eliminando la tabla 'subsidiaries'.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiaries');
    }
};
