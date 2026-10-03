<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Códigos de compra que un vendedor otorga a un comprador RECURRENTE (ya le
 * compró antes) para que compre por su cuenta desde el enlace público de
 * venta del vendedor. Sin un código vigente la solicitud no se procesa.
 *
 * Vigencia: vence al agotar max_uses o al pasar expires_at (lo que ocurra
 * primero), o si el vendedor lo revoca (revoked_at). Ambos límites los elige
 * el vendedor al otorgarlo (ver config('sellers.purchase_codes')).
 *
 * No reutiliza user_purchase_codes: esa tabla se crea por cada
 * ExternalPlayer (uno por sala), no está ligada al vendedor ni autoriza nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_buyer_purchase_codes')) {
            return;
        }

        Schema::create('external_buyer_purchase_codes', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del código de compra');

            $table->foreignSnowflake('seller_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Vendedor que otorgó el código (user_id)');

            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->cascadeOnUpdate()
                ->comment('País del teléfono del comprador');

            $table->string('phone', 20)->comment('Teléfono del comprador recurrente');
            $table->string('code', 16)->unique()->comment('Código que el comprador ingresa');

            $table->unsignedSmallInteger('max_uses')->comment('Compras permitidas con este código');
            $table->unsignedSmallInteger('uses_count')->default(0)->comment('Compras ya realizadas');
            $table->timestamp('expires_at')->comment('Vence en esta fecha aunque le queden usos');
            $table->timestamp('revoked_at')->nullable()->comment('Revocado por el vendedor');
            $table->timestamp('last_used_at')->nullable()->comment('Última compra con el código');

            $table->timestamps();

            $table->index(['seller_user_id', 'phone'], 'idx_ebpc_seller_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_buyer_purchase_codes');
    }
};
