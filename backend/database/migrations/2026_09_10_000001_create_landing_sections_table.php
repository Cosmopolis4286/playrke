<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('landing_sections');
    }

    public function up() : void
    {
        Schema::create('landing_sections', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único de la sección de landing');

            $table->string('section_key', 30)->unique()->comment('Identificador fijo de la sección (hero, jackpot, salas, verificar, invita, ganadores, cta_final, footer)');
            $table->string('label', 60)->comment('Nombre legible para el admin, ej. "Hero"');
            $table->json('content')->comment('Textos editoriales de la sección; el schema depende de section_key');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Orden de aparición en la landing pública');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->boolean('is_removable')->default(false)->comment('Las 8 secciones iniciales son estructurales del layout: no se destruyen, solo se desactivan');

            $table->timestamps();

            $table->index('sort_order', 'idx_landing_sections_sort_order');
            $table->index('status', 'idx_landing_sections_status');
        });
    }
};
