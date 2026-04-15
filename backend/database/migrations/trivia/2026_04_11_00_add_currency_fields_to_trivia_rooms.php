<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            // Tipo de moneda de entrada: fixed = moneda específica, dynamic = moneda local convertida
            $table->string('entry_currency_type', 20)->default('fixed')->after('time_per_question');
            
            // Coste de inscripción/entrada
            $table->decimal('entry_fee', 20, 8)->default(0)->after('entry_currency_type');
            
            // Moneda de entrada (si es fixed)
            $table->ulid('entry_currency_id')->nullable()->after('entry_fee');
            
            // Moneda local origen (si es dynamic)
            $table->ulid('local_currency_id')->nullable()->after('entry_currency_id');
            
            // Coste convertido a USD (para comparar y calcular botes)
            $table->decimal('entry_fee_usd', 20, 8)->default(0)->after('local_currency_id');
            
            // Tipo de bote: fixed = monto fijo, accumulated = acumulado de entradas
            $table->string('prize_type', 20)->default('accumulated')->after('entry_fee_usd');
            
            // Monto del bote/premio (si es fixed) o monto base (si es accumulated)
            $table->decimal('prize_amount', 20, 8)->default(0)->after('prize_type');
            
            // Moneda del bote
            $table->ulid('prize_currency_id')->nullable()->after('prize_amount');
            
            // Porcentaje de comisión de la sala (opcional)
            $table->decimal('commission_percent', 5, 2)->default(0)->after('prize_currency_id');
            
            $table->index('entry_currency_type');
            $table->index('entry_currency_id');
            $table->index('prize_type');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_trivia')->table('trivia_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'entry_currency_type',
                'entry_fee',
                'entry_currency_id',
                'local_currency_id',
                'entry_fee_usd',
                'prize_type',
                'prize_amount',
                'prize_currency_id',
                'commission_percent',
            ]);
        });
    }
};
