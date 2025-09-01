<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers_countries_coins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('countries')->onDelete('set null');
            $table->foreignId('coin_id')->constrained('coins')->onDelete('cascade');

            $table->tinyInteger('level')->comment('1=básico, 2=medio, 3=avanzado');
            $table->foreignId('creator_user_id')->constrained('users')->onDelete('cascade');

            $table->boolean('status')->default(false);
            $table->timestamps();

            // Evitar duplicados
            $table->unique(['seller_id', 'country_id', 'coin_id'], 'unique_seller_country_coin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers_countries_coins');
    }
};
