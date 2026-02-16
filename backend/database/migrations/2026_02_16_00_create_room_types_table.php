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
        Schema::create('room_types', function (Blueprint $table) {
            $table->string('id', 50)
                ->primary()
                ->comment('Código único del tipo de sala (PRIVATE, PUBLIC, VIP, MIXED)');

            $table->string('label', 100)
                ->comment('Nombre visible del tipo de sala');

            $table->string('status', 20)
                ->default('ACTIVE')
                ->comment('Estado del tipo de sala (ACTIVE|INACTIVE)');

            $table->timestamps();

            $table->index('status', 'idx_room_types_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
