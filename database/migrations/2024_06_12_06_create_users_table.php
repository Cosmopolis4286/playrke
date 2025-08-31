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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 20)->unique();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->foreignId('country')->constrained('countries')->onDelete('cascade');
            $table->foreignId('time_zone')->nullable()->constrained('time_zones')->onDelete('restrict')->onUpdate('cascade');
            $table->string('photo', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 250)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->string('referral_code', 16)->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('status')->default(1);
            $table->timestamps();

            // Indexes adicionales
            $table->index(['country', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country']);
            $table->dropForeign(['time_zone']);
            $table->dropForeign(['referred_by']);
        });

        Schema::dropIfExists('users');
    }
};
