<?php

namespace Tests\Feature\Games\Bingo;

use App\Domains\Games\Bingo\Exceptions\Room\BingoRoomException;
use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCardCapacityGuard;
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
    public function usa_lotes_vigentes_y_no_el_contador_desnormalizado_para_validar_minimo() : void
    {
        // Simula un total_cards desfasado: los lotes persistidos son la fuente
        // autoritativa que también usa la transición masiva a IN_PROGRESS.
        $roomId = $this->makeExpiredRoom(players: 3, cards: 6, minPlayers: 2, minCards: 5, roomTotalCards: 0);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('IN_PROGRESS'), (string) BingoRoom::find($roomId)->status_id);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function usa_jugadores_persistidos_si_total_players_esta_desfasado() : void
    {
        $roomId = $this->makeExpiredRoom(players: 3, cards: 6, minPlayers: 2, minCards: 2, roomTotalPlayers: 0);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('IN_PROGRESS'), (string) BingoRoom::find($roomId)->status_id);
    }

    #[Test]
    public function no_inicia_una_sala_por_un_total_players_sobrestimado() : void
    {
        $roomId = $this->makeExpiredRoom(players: 1, cards: 6, minPlayers: 2, minCards: 2, roomTotalPlayers: 3);

        app(CheckExpiredBingoRoomsService::class)->handle();

        $this->assertSame($this->statusId('CANCELLED'), (string) BingoRoom::find($roomId)->status_id);
        Queue::assertPushed(BingoCardLotRefundJob::class);
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

    #[Test]
    public function el_tope_de_cartones_usa_lotes_vigentes_y_no_total_cards() : void
    {
        $roomId = $this->makeExpiredRoom(players: 3, cards: 6, minPlayers: 2, minCards: null, roomTotalCards: 0);
        DB::table('bingo_rooms_config')->where('room_id', $roomId)->update(['max_cards_to_start' => 5]);
        $room = BingoRoom::findOrFail($roomId);

        $this->expectException(BingoRoomException::class);
        DB::transaction(fn () => BingoRoomCardCapacityGuard::assertHasCapacityFor($room, 1));
    }

    #[Test]
    public function persiste_reembolso_pendiente_si_falla_el_despacho_de_cola() : void
    {
        $roomId = $this->makeExpiredRoom(players: 1, cards: 1, minPlayers: 2, minCards: 2);
        $room = BingoRoom::findOrFail($roomId);
        $lot = \App\Domains\Games\Bingo\Models\Card\BingoCardLot::where('room_id', $roomId)->firstOrFail();
        $service = app(CheckExpiredBingoRoomsService::class);

        (new \ReflectionMethod(CheckExpiredBingoRoomsService::class, 'recordRefundDispatchFailure'))
            ->invoke($service, $lot, $room, new \RuntimeException('queue unavailable'));

        $failure = DB::table('bingo_lot_refund_failures')
            ->where('lot_id', $lot->id)
            ->where('room_id', $roomId)
            ->first();

        $this->assertNotNull($failure);
        $this->assertSame('PENDING', $failure->status);
        $this->assertSame(0, (int) $failure->attempts);
        $this->assertStringContainsString('QUEUE_DISPATCH_FAILED', $failure->error_message);
    }

    private function makeExpiredRoom(int $players, int $cards, ?int $minPlayers, ?int $minCards, ?int $roomTotalCards = null, ?int $roomTotalPlayers = null) : string
    {
        $roomId = (string) (910000000000000000 + (++self::$seq));
        DB::table('bingo_rooms')->insert([
            'id' => $roomId,
            'status_id' => $this->statusId('ACTIVE'),
            'creator_user_id' => '1',
            'paid_prizes' => 'N',
            'prize_pool' => 0,
            'uuid_code' => 'TEST-' . $roomId,
            'total_players' => $roomTotalPlayers ?? $players,
            'total_cards' => $roomTotalCards ?? $cards,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($players > 0) {
            $roomUsers = [];
            for ($i = 1; $i <= $players; $i++) {
                $roomUsers[] = [
                    'id' => (string) ((int) $roomId + 800000 + $i),
                    'room_id' => $roomId,
                    'user_id' => (string) ((int) $roomId + 900000 + $i),
                    'role' => BingoRoomUser::ROLE_PLAYER,
                    'cards_purchased' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('bingo_room_users')->insert($roomUsers);
        }
        DB::table('bingo_rooms_config')->insert([
            'id' => (string) ((int) $roomId + 500000),
            'room_id' => $roomId,
            'unix_date' => now()->subMinute()->timestamp,
            'min_players' => $minPlayers,
            'min_cards_to_start' => $minCards,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Un lote por participante para que el contador central cuente
        // jugadores con cartones vigentes, no filas room_user aisladas.
        $lotCount = max($players, 1);
        $baseCards = intdiv($cards, $lotCount);
        $remainingCards = $cards % $lotCount;
        for ($i = 1; $i <= $lotCount; $i++) {
            DB::table('bingo_card_lots')->insert([
                'id' => (string) ((int) $roomId + 700000 + $i),
                'room_id' => $roomId,
                'room_user_id' => $players > 0 ? (string) ((int) $roomId + 800000 + $i) : null,
                // La tabla de reintentos de reembolso exige usuario y moneda.
                'user_id' => '1',
                'currency_id' => '1',
                'card_count' => $baseCards + ($i <= $remainingCards ? 1 : 0),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $roomId;
    }

    private function statusId(string $code) : string
    {
        return (string) GameStatus::where('code', $code)->value('id');
    }
}
