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
        if (Schema::hasTable('connection_types')) {
            return;
        }

        Schema::create('connection_types', function (Blueprint $table) {
            // ID Snowflake
            $table->snowflake('id')->primary()->comment('ID único del tipo de conexión');

            $table->string('code', 30)
                ->unique()
                ->comment('Código único del tipo de conexión (ONLINE, OFFLINE, MIXED)');

            $table->string('label', 50)
                ->unique()
                ->comment('Etiqueta visible del tipo de conexión');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')
                ->comment('Estado del tipo de conexión');

            $table->timestamps();

            $table->index(['status', 'label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connection_types');
    }
};
