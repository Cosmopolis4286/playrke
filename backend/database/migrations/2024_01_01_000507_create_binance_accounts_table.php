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
        Schema::dropIfExists('binance_accounts');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('binance_accounts', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();
            $table->enum('account_type', ['E', 'P', 'B']);
            $table->string('data_value', 100);
            $table->foreignSnowflake('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED'])->default('ACTIVE');
            $table->timestamps();
        });
    }
};
