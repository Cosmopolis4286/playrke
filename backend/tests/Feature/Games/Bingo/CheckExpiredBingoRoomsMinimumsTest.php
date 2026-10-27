<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Utils\CheckExpiredBingoRoomsService;
use App\Models\Games\Common\Utils\GameStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Al vencer el horario, una sala con jugadores que no alcanzó el mínimo de
 * cartones O de jugadores se cancela y se reembolsa. Antes solo se miraba el
 * de cartones: sin el mínimo de jugadores la sala pasaba a IN_PROGRESS y
 * StartPendingBingoDrawsCommand nunca la sorteaba (quedaba trabada).
 */
class CheckExpiredBingoRoomsMinimumsTest extends TestCase
{
    use DatabaseTransactions;

    private static int $seq = 0;
    private string $previousSqlMode = '';

    protected function setUp() : void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // bingo_rooms_config tiene muchas columnas NOT NULL ajenas a lo que se
        // prueba acá — sin modo estricto MySQL les asigna su valor implícito.
        $this->previousSqlMode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        foreach (['ACTIVE', 'INACTIVE', 'IN_PROGRESS', 'CANCELLED'] as $code) {
            if (! GameStatus::where('code', $code)->exists()) {
                GameStatus::create(['code' => $code, 'label' => $code]);
            }
        }
        Queue::fake();
    }

    protected function tearDown() : void
    {
        DB::statement('SET SESSION sql_mode = ?', [$this->previousSqlMode]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    #[Test]
    public function con_ambos_minimos_alcanzados_pasa_a_en_curso() : void
    {
        $roomId = $this->makeExpiredRoom(players: 3, cards: 6, minPlayers: 2, minCards: 2);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('IN_PROGRESS'), (string) BingoRoom::find($roomId)->status_id);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function sala_vieja_sin_minimos_configurados_pasa_a_en_curso() : void
    {
        $roomId = $this->makeExpiredRoom(players: 1, cards: 1, minPlayers: null, minCards: null);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('IN_PROGRESS'), (string) BingoRoom::find($roomId)->status_id);
    }

    #[Test]
    public function sin_minimo_de_cartones_se_sigue_cancelando() : void
    {
        $roomId = $this->makeExpiredRoom(players: 3, cards: 3, minPlayers: 2, minCards: 10);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('CANCELLED'), (string) BingoRoom::find($roomId)->status_id);
    }

    #[Test]
    public function sin_minimo_de_jugadores_se_cancela_y_reembolsa() : void
    {
        $roomId = $this->makeExpiredRoom(players: 1, cards: 5, minPlayers: 2, minCards: 2);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('CANCELLED'), (string) BingoRoom::find($roomId)->status_id);
        Queue::assertPushed(BingoCardLotRefundJob::class);
    }

    private function makeExpiredRoom(int $players, int $cards, ?int $minPlayers, ?int $minCards) : string
    {
        $roomId = (string) (910000000000000000 + (++self::$seq));
        DB::table('bingo_rooms')->insert([
            'id' => $roomId,
            'status_id' => $this->statusId('ACTIVE'),
            'creator_user_id' => '1',
            'paid_prizes' => 'N',
            'prize_pool' => 0,
            'uuid_code' => 'TEST-' . $roomId,
            'total_players' => $players,
            'total_cards' => $cards,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('bingo_rooms_config')->insert([
            'id' => (string) ((int) $roomId + 500000),
            'room_id' => $roomId,
            'unix_date' => now()->subMinute()->timestamp,
            'min_players' => $minPlayers,
            'min_cards_to_start' => $minCards,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('bingo_card_lots')->insert([
            'id' => (string) ((int) $roomId + 700000),
            'room_id' => $roomId,
            'card_count' => $cards,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $roomId;
    }

    private function statusId(string $code) : string
    {
        return (string) GameStatus::where('code', $code)->value('id');
    }
}
