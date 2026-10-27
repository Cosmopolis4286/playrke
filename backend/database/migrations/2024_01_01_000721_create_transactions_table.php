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
        Schema::dropIfExists('transactions');
    }

    /**
     * Run the migrations.
     */
    public function up() : void
    {
        Schema::create('transactions', function(Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('user_id')
                ->constrained('customers_users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('game_id')
                ->constrained('games')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('connection_type_id')
                ->constrained('connection_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate()
                ->comment('Tipo de conexión asignado');

            $table->string('bet_id');
            $table->string('code')->unique();
            $table->string('transaction_type', 15);
            $table->string('currency_id', 15);
            $table->decimal('amount', 16, 8);
            $table->enum('status', ['WAITING', 'EXECUTED'])->default('WAITING');
            $table->timestamp('processing_data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['customer_id', 'status']);
        });
    }
};
