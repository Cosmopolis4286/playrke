<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->boolean('allow_custom_card')->default(false)->after('min_cards_to_start');
            $table->decimal('custom_card_price', 8, 2)->nullable()->after('allow_custom_card');
            $table->tinyInteger('max_custom_cards')->unsigned()->nullable()->after('custom_card_price');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_rooms_config', function (Blueprint $table) {
            $table->dropColumn(['allow_custom_card', 'custom_card_price', 'max_custom_cards']);
        });
    }
};
