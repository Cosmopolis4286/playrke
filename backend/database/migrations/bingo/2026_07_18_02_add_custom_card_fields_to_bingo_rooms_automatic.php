<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->boolean('allow_custom_card')->default(false)->after('min_cards_to_start');
            $table->decimal('custom_card_price_pct', 5, 2)->nullable()->after('allow_custom_card');
            $table->enum('custom_card_mode', ['paid', 'every_x', 'free'])
                ->nullable()
                ->after('custom_card_price_pct');
            $table->unsignedSmallInteger('custom_card_every_x')->nullable()->after('custom_card_mode');
            $table->boolean('allow_special_card')->default(false)->after('custom_card_every_x');
            $table->tinyInteger('max_special_cards')->unsigned()->nullable()->after('allow_special_card');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_automatic', function (Blueprint $table) {
            $table->dropColumn([
                'allow_custom_card',
                'custom_card_price_pct',
                'custom_card_mode',
                'custom_card_every_x',
                'allow_special_card',
                'max_special_cards',
            ]);
        });
    }
};
