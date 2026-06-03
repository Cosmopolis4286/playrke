<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('treasurers')) {
            return;
        }

        Schema::create('treasurers', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();;

            $table->string('name', 75)->comment('Nombre del tesorero');

            $table->string('phone', 20)->unique()->comment('Teléfono de contacto');

            $table->decimal('commission', 5, 2)->nullable()->comment('Comisión (%)');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignSnowflake('unique_code_id')
                ->nullable()
                ->constrained('unique_codes')
                ->nullOnDelete()
                ->comment('Código único asociado');

            $table->uuid('uuid_code')->unique()->comment('Identificador único universal');

            $table->enum('status', ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'])
                ->default('ACTIVE')
                ->index();

            $table->timestamps();

            $table->softDeletes();

            // Índice compuesto si consultas frecuentes por país y estado
            $table->index(['country_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasurers');
    }
};
