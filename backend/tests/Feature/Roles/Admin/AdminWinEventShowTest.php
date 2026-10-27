<?php

namespace Tests\Feature\Roles\Admin;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Events\Platform\WinEventBroadcasted;
use App\Models\Role;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Vista de detalle de una ganancia (/admin/win-event/{id}/show), a la que
 * se llega desde el ticker "En vivo". Solo superadmin/admin; el ganador se
 * muestra SIN enmascarar, por eso el acceso de cualquier otro rol debe fallar.
 */
class AdminWinEventShowTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    #[Test]
    public function admin_sees_unmasked_winner_and_generic_metadata_for_game_without_resolver() : void
    {
        $admin = $this->makeUserWithRole('admin');
        $event = $this->makeWinEvent('ruleta', ['mesa' => 7]);

        $this->actingAs($admin)
            ->get("/admin/win-event/{$event->id}/show")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Roles/Admin/WinEvent/Show')
                ->where('detail.event.id', (string) $event->id)
                ->where('detail.winner.name', 'Jackson Pérez')
                ->where('detail.has_game_detail', false)
                ->where('detail.sections.0.key', 'metadata')
                ->where('detail.sections.0.items.0.value', '7'));
    }

    #[Test]
    public function player_cannot_probe_unknown_win_event_ids() : void
    {
        $player = $this->makeUserWithRole('player');

        $this->assertNotEquals(200, $this->actingAs($player)->get('/admin/win-event/123456789/show')->getStatusCode());
    }

    #[Test]
    public function player_cannot_view_win_event_detail() : void
    {
        $player = $this->makeUserWithRole('player');
        $event = $this->makeWinEvent('bingo');

        $response = $this->actingAs($player)->get("/admin/win-event/{$event->id}/show");

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function superadmin_sees_bingo_section_even_when_source_prize_is_missing() : void
    {
        $superadmin = $this->makeUserWithRole('superadmin');
        $event = $this->makeWinEvent('bingo', ['pattern' => 'Casa Llena']);

        $this->actingAs($superadmin)
            ->get("/admin/win-event/{$event->id}/show")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('detail.has_game_detail', true)
                ->where('detail.sections.0.key', 'bingo-prize')
                ->where('detail.sections.0.items.0.value', 'Casa Llena'));
    }

    #[Test]
    public function unknown_win_event_shows_an_empty_state_instead_of_a_404() : void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/win-event/123456789/show')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Roles/Admin/WinEvent/Show')
                ->where('detail', null)
                ->where('missingId', '123456789'));
    }

    #[Test]
    public function win_event_is_broadcast_only_after_the_transaction_commits() : void
    {
        Bus::fake();
        $service = app(WinEventService::class);

        // Transacción revertida: el ticker no debe anunciar un ganador que no existe.
        try {
            DB::transaction(function() use ($service) {
                $service->reportNonMoney(null, 'Fantasma', 'bingo', 'Bingo', WinEvent::PRIZE_ITEM, 'TV');

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        Bus::assertNotDispatched(BroadcastEvent::class);

        DB::transaction(fn () => $service->reportNonMoney(null, 'Real', 'bingo', 'Bingo', WinEvent::PRIZE_ITEM, 'TV'));

        Bus::assertDispatched(BroadcastEvent::class, fn (BroadcastEvent $job) => $job->event instanceof WinEventBroadcasted);
    }

    private function makeUserWithRole(string $roleName) : User
    {
        if (! Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
            $role = new Role([
                'name' => $roleName,
                'prefix' => strtoupper($roleName),
                'status' => true,
            ]);
            $role->id = (string) (610000000000000000 + (++self::$idCounter));
            $role->guard_name = 'web';
            $role->type = 'web';
            $role->save();
        }

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function makeWinEvent(string $gameSlug, array $metadata = []) : WinEvent
    {
        return WinEvent::create([
            'winner_name_override' => 'Jackson Pérez',
            'game_slug' => $gameSlug,
            'game_name' => ucfirst($gameSlug),
            'source_type' => WinEvent::SOURCE_EXTERNAL_API,
            'prize_kind' => WinEvent::PRIZE_MONEY,
            'prize_label' => '$25.00',
            'amount_usd' => 25,
            'amount_local' => 25,
            'metadata' => $metadata,
        ]);
    }
}
