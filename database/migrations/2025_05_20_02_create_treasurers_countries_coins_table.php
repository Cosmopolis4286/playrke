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
        Schema::create('treasurers_countries_currencies', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('treasurer_id')->constrained('treasurers');
            $table->foreignSnowflake('country_id')->nullable()->constrained('countries');
            $table->foreignSnowflake('currency_id')->constrained('currencies');
            $table->enum('level', ['1', '2', '3']);
            $table->foreignSnowflake('creator_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasurers_countries_currencies');
    }
};
