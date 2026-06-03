<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transactions')) {
            return;
        }

        Schema::create('transactions', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;
            $table->foreignSnowflake('customer_id');
            $table->foreignSnowflake('user_id');
            $table->foreignSnowflake('game_id');
            $table->foreignSnowflake('game_mode_id');
            $table->string('bet_id');
            $table->string('code')->unique();
            $table->string('transaction_type', 15);
            $table->string('currency_id', 15);
            $table->decimal('amount', 16, 8);
            $table->enum('status', ['WAITING', 'EXECUTED'])->default('WAITING');
            $table->timestamp('processing_data')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('customers_users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('game_mode_id')->references('id')->on('game_modes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
