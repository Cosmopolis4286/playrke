<?php

use Database\Seeders\GameStatusSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Garantiza que los códigos de estado de juego críticos existan en la tabla
 * game_status, independientemente de si el seeder fue ejecutado previamente.
 *
 * Delega en GameStatusSeeder (la fuente canónica de estos datos de referencia)
 * en lugar de duplicar la lógica aquí. El seeder usa updateOrCreate, por lo
 * que es idempotente y seguro de ejecutar múltiples veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new GameStatusSeeder())->run();
    }

    public function down(): void
    {
        // Los datos de referencia de game_status no se eliminan en rollback —
        // otras tablas tienen FKs hacia ellos y su ausencia rompe el sistema.
    }
};
