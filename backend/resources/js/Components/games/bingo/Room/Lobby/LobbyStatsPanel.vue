<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { LobbyRoom, LobbyLiveState } from './types'

const props = defineProps<{
  room: LobbyRoom
  live: LobbyLiveState
  roomRouteKey: string
  canNavigateGame: boolean
  canPurchaseCards: boolean
  /** Calculado en el padre (Lobby/Index.vue): max_players alcanzado Y el
   * usuario aún no tiene cartones — un jugador ya registrado puede seguir
   * comprando aunque el cupo de jugadores nuevos esté lleno (ver
   * BingoCardGenerationService::$isNewRoomUser). */
  roomIsFull: boolean
  redirecting: boolean
  playersFlash: boolean
  minCardsToStart: number | null
  cardsProgressReady: boolean
  lastSaleLabel: string | null
  notificationPermission: 'default' | 'granted' | 'denied' | 'unsupported'
}>()

const emit = defineEmits<{
  openPlayers: []
  openPurchase: []
  openMyCards: []
  requestNotification: []
}>()

// Indicador de advertencia previo al bloqueo real (solo visual, no afecta
// canPurchaseCards) — se acerca al límite de jugadores de la sala.
const roomIsNearFull = computed(() =>
  !props.roomIsFull && props.room.max_players != null &&
  props.live.totalPlayers >= props.room.max_players * 0.8
)

// min_players es informativo (no bloquea la compra, a diferencia de
// max_players) — BingoRoomHealthCheckService ya bloquea el inicio real del
// sorteo en backend si no se alcanza. Solo se muestra como contexto.
const playersTooltip = computed(() => {
  if (props.room.min_players == null) return 'Ver lista de jugadores'
  return props.live.totalPlayers >= props.room.min_players
    ? `Ver lista de jugadores — ya se alcanzó el mínimo de ${props.room.min_players} para iniciar`
    : `Ver lista de jugadores — se requieren al menos ${props.room.min_players} para iniciar el sorteo`
})

const cardsGoalTooltip = computed(() => {
  if (!props.room.max_cards_to_start) return undefined
  return `Tope de cartones vendibles en la sala: ${props.room.max_cards_to_start}`
})

</script>

<template>
  <div class="lby-panel">
    <!-- Stats -->
    <div class="lby-stats">
      <div
        class="lby-stat lby-stat--clickable"
        :class="{ 'lby-flash': playersFlash, 'lby-stat--full': roomIsFull, 'lby-stat--near-full': roomIsNearFull }"
        :title="playersTooltip"
        @click="emit('openPlayers')"
      >
        <div class="lby-stat__icon-wrap"><i class="fas fa-users" /></div>
        <span class="lby-stat__val" aria-live="polite" aria-atomic="true">
          {{ live.totalPlayers }}<template v-if="room.max_players"><small class="lby-stat__val-max">/{{ room.max_players }}</small></template>
        </span>
        <span class="lby-stat__lbl">{{ roomIsFull ? 'Sala llena' : 'Jugadores' }}</span>
      </div>

      <div
        class="lby-stat lby-stat--mine lby-stat--clickable"
        :title="live.userCards > 0 ? 'Ver mis cartones' : (canPurchaseCards ? 'Comprar cartones' : 'Mis cartones')"
        role="button"
        tabindex="0"
        @click="live.userCards > 0 ? emit('openMyCards') : canPurchaseCards && emit('openPurchase')"
        @keydown.enter="live.userCards > 0 ? emit('openMyCards') : canPurchaseCards && emit('openPurchase')"
      >
        <div class="lby-stat__icon-wrap"><i class="fas fa-ticket-alt" /></div>
        <span class="lby-stat__val" aria-live="polite" aria-atomic="true">{{ live.userCards }}</span>
        <span class="lby-stat__lbl">Mis cartones</span>
      </div>

      <div
        class="lby-stat"
        :class="{ 'lby-stat--ready': cardsProgressReady, 'lby-stat--closed': room.close_room }"
        :title="cardsGoalTooltip"
      >
        <div class="lby-stat__icon-wrap">
          <i :class="minCardsToStart
            ? (cardsProgressReady ? 'fas fa-check-circle' : 'fas fa-hourglass-half')
            : (room.close_room ? 'fas fa-lock' : 'fas fa-lock-open')" />
        </div>
        <span class="lby-stat__val lby-stat__val--sm">
          <template v-if="minCardsToStart">
            {{ cardsProgressReady ? '¡Listo!' : minCardsToStart - live.totalCards }}
          </template>
          <template v-else>
            {{ room.close_room ? 'Cerrada' : 'Abierta' }}
          </template>
        </span>
        <span class="lby-stat__lbl">
          {{ minCardsToStart ? (cardsProgressReady ? 'Meta alcanzada' : 'Faltan') : 'Sala' }}
        </span>
      </div>

      <div v-if="live.cardPrice" class="lby-stat">
        <div class="lby-stat__icon-wrap"><i class="fas fa-tag" /></div>
        <span class="lby-stat__val">{{ Number(live.cardPrice).toFixed(2) }}</span>
        <span class="lby-stat__lbl">Precio / cartón</span>
      </div>

      <div v-if="room.time_chip" class="lby-stat">
        <div class="lby-stat__icon-wrap"><i class="fas fa-stopwatch" /></div>
        <span class="lby-stat__val">{{ room.time_chip }}s</span>
        <span class="lby-stat__lbl">Tiempo ficha</span>
      </div>
    </div>

    <div class="lby-divider" />

    <!-- Acciones -->
    <div class="lby-actions">
      <Link
        v-if="canNavigateGame && !redirecting"
        :href="`/bingo/${roomRouteKey}/game`"
        class="lby-btn lby-btn--play"
      >
        <i class="fas fa-play" /> Entrar al Juego
      </Link>
      <button v-else class="lby-btn lby-btn--disabled" disabled>
        <i :class="redirecting ? 'fas fa-spinner fa-spin' : 'fas fa-play'" />
        {{ redirecting ? 'Redirigiendo…' : 'Entrar al Juego' }}
      </button>

      <button
        v-show="canPurchaseCards"
        class="lby-btn lby-btn--buy"
        :aria-hidden="!canPurchaseCards"
        @click="emit('openPurchase')"
      >
        <i class="fas fa-plus" />
        {{ live.userCards > 0 ? 'Más cartones' : 'Comprar cartones' }}
      </button>
    </div>

    <!-- Modo espectador -->
    <div v-if="room.close_room && live.userCards === 0" class="lby-spectator">
      <i class="fas fa-eye" />
      <span>Sala cerrada — modo espectador</span>
    </div>

    <!-- Cupo lleno — nuevos jugadores no podrán registrar cartones (BingoRoomCapacityGuard) -->
    <div v-else-if="roomIsFull && live.userCards === 0" class="lby-spectator">
      <i class="fas fa-lock" />
      <span>Sala llena — modo espectador</span>
    </div>

    <!-- Última compra -->
    <div v-if="lastSaleLabel" class="lby-last-sale">
      <i class="fas fa-bolt" />
      Última compra <strong>{{ lastSaleLabel }}</strong>
    </div>

    <!-- Notificación de escritorio -->
    <button
      v-if="notificationPermission === 'default'"
      class="lby-notify-btn"
      @click="emit('requestNotification')"
    >
      <i class="fas fa-bell" /> Avisarme cuando empiece
    </button>
    <div v-else-if="notificationPermission === 'granted'" class="lby-notify-active">
      <i class="fas fa-bell" /> Notificación activada
    </div>

    <p v-if="!canNavigateGame" class="lby-warn">
      <i class="fas fa-exclamation-triangle" /> Sala sin clave válida — recarga la página.
    </p>
  </div>
</template>

<style scoped>
.lby-panel {
  background: rgba(255,255,255,.022); border: 1px solid rgba(255,255,255,.07);
  border-radius: 13px; padding: 10px 14px;
  display: flex; flex-direction: column; gap: 8px;
  box-shadow: 0 4px 24px rgba(0,0,0,.45); backdrop-filter: blur(6px);
}
.lby-stats { display: flex; gap: 6px; align-items: stretch; }
.lby-stat {
  flex: 1; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);
  border-radius: 9px; padding: 6px 5px;
  display: flex; flex-direction: column; align-items: center; gap: 3px;
  transition: border-color .2s, transform .18s, background .2s; min-width: 0;
}
.lby-stat:hover { border-color: rgba(42,245,152,.2); background: rgba(42,245,152,.03); transform: translateY(-1px); }
.lby-stat--mine  { border-color: rgba(42,245,152,.15); background: rgba(42,245,152,.04); }
.lby-stat--mine:hover { border-color: rgba(42,245,152,.32); }
.lby-stat--ready { border-color: rgba(42,245,152,.2); background: rgba(42,245,152,.04); }
.lby-stat--ready .lby-stat__icon-wrap { color: #2af598; border-color: rgba(42,245,152,.25); background: rgba(42,245,152,.1); }
.lby-stat--ready .lby-stat__val { color: #2af598; }
.lby-stat--closed { border-color: rgba(255,77,109,.15); background: rgba(255,77,109,.03); }
.lby-stat--closed .lby-stat__icon-wrap { color: #ff6e8a; border-color: rgba(255,77,109,.2); background: rgba(255,77,109,.08); }
.lby-stat--closed .lby-stat__val { color: #ff6e8a; }

.lby-stat--near-full { border-color: rgba(255,191,0,.2); background: rgba(255,191,0,.03); }
.lby-stat--near-full .lby-stat__icon-wrap { color: #ffc336; border-color: rgba(255,191,0,.25); background: rgba(255,191,0,.1); }
.lby-stat--near-full .lby-stat__val { color: #ffc336; }
.lby-stat--full { border-color: rgba(255,77,109,.2); background: rgba(255,77,109,.04); }
.lby-stat--full .lby-stat__icon-wrap { color: #ff6e8a; border-color: rgba(255,77,109,.25); background: rgba(255,77,109,.1); }
.lby-stat--full .lby-stat__val { color: #ff6e8a; }
.lby-stat__val-max { font-size: 10px; font-weight: 500; opacity: .55; margin-left: 1px; }

.lby-stat__icon-wrap {
  width: 22px; height: 22px; border-radius: 6px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #3a5068;
  transition: background .2s, color .2s, border-color .2s;
}
.lby-stat:hover .lby-stat__icon-wrap { background: rgba(42,245,152,.1); border-color: rgba(42,245,152,.25); color: #2af598; }
.lby-stat--mine .lby-stat__icon-wrap { color: #2af598; border-color: rgba(42,245,152,.2); background: rgba(42,245,152,.08); }

.lby-stat__val     { font-family: 'Anton', sans-serif; font-size: 16px; color: #c8d8f0; line-height: 1; }
.lby-stat__val--sm { font-size: 12px; }
.lby-stat--mine .lby-stat__val { color: #2af598; }
.lby-stat__lbl { font-size: 7px; color: #2a3f58; text-transform: uppercase; letter-spacing: .6px; text-align: center; white-space: nowrap; }

.lby-stat--clickable { cursor: pointer; }
.lby-stat--clickable:hover { border-color: rgba(100,170,216,.3) !important; background: rgba(40,110,190,.06) !important; }
.lby-stat--clickable:hover .lby-stat__icon-wrap { background: rgba(40,110,190,.15) !important; border-color: rgba(40,110,190,.3) !important; color: #6aaad8 !important; }

.lby-divider { height: 1px; background: rgba(255,255,255,.05); margin: 0 -2px; }

.lby-actions { display: flex; gap: 8px; }
.lby-btn {
  height: 38px; padding: 0 18px; border: none; border-radius: 10px;
  font-size: 11px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
  text-decoration: none; white-space: nowrap; min-width: 0; flex: 1;
  position: relative; overflow: hidden;
  transition: transform .15s, box-shadow .2s, filter .2s;
}
.lby-btn::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,.1) 50%, transparent 100%);
  transform: translateX(-100%); transition: transform .5s ease;
}
.lby-btn:hover::after { transform: translateX(100%); }
.lby-btn--play {
  background: linear-gradient(100deg, #14804e 0%, #2af598 100%); color: #021a0c;
  box-shadow: 0 6px 24px rgba(42,245,152,.24), inset 0 1px 0 rgba(255,255,255,.12);
}
.lby-btn--play:hover {
  filter: brightness(1.08); transform: translateY(-2px);
  box-shadow: 0 10px 32px rgba(42,245,152,.38), inset 0 1px 0 rgba(255,255,255,.15);
}
.lby-btn--buy {
  background: rgba(200,150,0,.08); border: 1px solid rgba(255,191,0,.22); color: #c89020;
  box-shadow: 0 3px 14px rgba(0,0,0,.3);
}
.lby-btn--buy:hover {
  background: rgba(255,191,0,.12); border-color: rgba(255,191,0,.42);
  transform: translateY(-2px); box-shadow: 0 7px 22px rgba(0,0,0,.38);
}
.lby-btn--disabled {
  background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.05);
  color: #2a3f58; cursor: not-allowed;
}
.lby-btn--disabled::after { display: none; }
.lby-warn {
  font-size: 10px; color: #907020; text-align: center; margin: 0;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.lby-spectator {
  display: flex; align-items: center; gap: 6px; justify-content: center;
  font-size: 9.5px; color: #5a7090; padding: 5px 8px; border-radius: 8px;
  background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.05);
}
.lby-spectator i { font-size: 9px; opacity: .7; }

.lby-last-sale {
  font-size: 9px; color: #3a5068; text-align: center;
  display: flex; align-items: center; justify-content: center; gap: 5px;
  animation: lbyFadeIn .3s ease;
}
.lby-last-sale i { color: #ffc336; font-size: 8px; }
.lby-last-sale strong { color: #9ecaf0; }
@keyframes lbyFadeIn { from { opacity: 0; } to { opacity: 1; } }

.lby-notify-btn {
  width: 100%; padding: 6px 0; border-radius: 8px; font-size: 9.5px; font-weight: 700;
  letter-spacing: .4px; cursor: pointer; border: 1px dashed rgba(255,195,54,.25);
  background: rgba(255,195,54,.04); color: #7a6020;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background .2s, border-color .2s, color .2s;
}
.lby-notify-btn:hover { background: rgba(255,195,54,.1); border-color: rgba(255,195,54,.4); color: #ffc336; }
.lby-notify-btn i { font-size: 9px; }

.lby-notify-active {
  font-size: 9px; color: #2a7040; text-align: center;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}
.lby-notify-active i { color: #2af598; font-size: 9px; }

.lby-flash { animation: lbyFlash 1.8s ease-in-out; }
@keyframes lbyFlash { 0%,100%{filter:none} 40%{filter:drop-shadow(0 0 12px rgba(42,245,152,.7)) brightness(1.2)} }

@media (max-width: 640px) { .lby-actions { flex-direction: column; } }
@media (max-width: 480px) {
  .lby-panel { padding: 10px 12px; }
  .lby-stats { flex-wrap: wrap; }
  .lby-stat  { min-width: calc(50% - 3px); }
}
</style>
