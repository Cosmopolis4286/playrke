<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('unique_codes');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('unique_codes', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();
            $table->string('uuid_code', 36)->unique();
            $table->string('rke_code', 25)->unique();
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });
    }
};
