<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Enlace público de venta del vendedor (/comprar/{token}) — compra por
 * cuenta propia de compradores recurrentes con código (ver
 * SellerSalesLinkService / ExternalBuyerPurchaseCodeService).
 *
 * La columna ya está en el Schema::create consolidado de sellers
 * (instalaciones nuevas); esta migración la agrega a BDs existentes,
 * protegida con hasColumn() para ser no-op donde ya exista.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::table('sellers', function(Blueprint $table) {
            if (Schema::hasColumn('sellers', 'sales_link_token')) {
                $table->dropUnique(['sales_link_token']);
                $table->dropColumn('sales_link_token');
            }
        });
    }

    public function up() : void
    {
        Schema::table('sellers', function(Blueprint $table) {
            if (! Schema::hasColumn('sellers', 'sales_link_token')) {
                $table->string('sales_link_token', 40)->nullable()->unique()->after('status')
                    ->comment('Token del enlace público de venta (regenerable)');
            }
        });
    }
};
