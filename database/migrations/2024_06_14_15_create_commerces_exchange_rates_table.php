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
    public function up()
    {
        Schema::create('commerces_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commerce')->constrained('commerces')->onDelete('cascade');
            $table->unsignedBigInteger('coin');
            $table->double('exchange_rate')->default(1);
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'OLD', 'DIRECTORY']);
            $table->string('pct', 8)->default('0');
            $table->timestamps();

            // Foreign keys
            $table->foreign('coin')->references('id')->on('coins');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commerces_exchange_rates');
    }
};
