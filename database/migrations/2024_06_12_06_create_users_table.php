<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Tabla principal de usuarios
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 20)->unique();
            $table->unsignedBigInteger('user_type')->default(1);
            $table->string('first_name', 20)->nullable();
            $table->string('last_name', 30)->nullable();
            $table->unsignedBigInteger('country');
            $table->unsignedBigInteger('time_zone')->nullable();
            $table->string('photo', 50)->nullable();
            $table->string('phone', 12)->nullable();
            $table->string('email', 250)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('remember_token', 100)->nullable();
            $table->string('referral_code', 12);
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();

            // Claves foráneas
            $table->foreign('user_type')->references('id')->on('user_type');
            $table->foreign('country')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('time_zone')->references('id')->on('time_zones')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Eliminamos tabla users con sus claves foráneas
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country']);
            $table->dropForeign(['time_zone']);
            $table->dropForeign(['referred_by']);
        });
        Schema::dropIfExists('users');
    }
};
