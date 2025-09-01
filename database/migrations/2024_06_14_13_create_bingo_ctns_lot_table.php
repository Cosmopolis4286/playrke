<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bingo_ctns_lot', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('seller_id')
                ->nullable()
                ->constrained('sellers')
                ->cascadeOnUpdate();

            $table->foreignId('room_user_id')
                ->constrained('bingo_rooms_user')
                ->cascadeOnUpdate();

            $table->integer('ctns')->comment('Cantidad de cartones');
            $table->string('coin', 15);
            $table->double('ctn_price', 10, 2);
            $table->double('exchange_rate', 10, 2);
            $table->double('sale_amount', 10, 2);
            $table->double('seller_commn', 10, 2)->nullable();
            $table->boolean('status')->default(0)->comment('0 = inactivo, 1 = activo');

            $table->timestamps();

            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_ctns_lot');
    }
};
