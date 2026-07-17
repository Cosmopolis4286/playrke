<script setup lang="ts">
import { computed } from 'vue'
import type { GameEndData } from './types'
import { useActivePlayers } from '@js/composables/games/bingo/game/useActivePlayers'
import { useCardViewer } from '@js/composables/games/bingo/game/useCardViewer'
import WinnersList from './Players/WinnersList.vue'
import ActivePlayersList from './Players/ActivePlayersList.vue'
import CardViewerModal from './Players/CardViewerModal.vue'

interface Props {
  show: boolean
  livePlayers: number
  totalWinners: number
  gameEndData: GameEndData | null
  userId?: string | null
  currencySym: string
  customerLogo?: string | null
  drawnNumbers: number[]
  roomRouteKey: string
  roomId: string
}

const props = withDefaults(defineProps<Props>(), {
  userId: null,
  customerLogo: null,
})

const emit = defineEmits<{
  close: []
}>()

const { activePlayers, loadingPlayers, playersError, fetchActivePlayers } = useActivePlayers({
  show: computed(() => props.show),
  totalWinners: computed(() => props.totalWinners),
  roomRouteKey: computed(() => props.roomRouteKey),
  roomId: computed(() => props.roomId),
})

const { viewingCard, viewingCardOwner, loadingCardId, openCardViewer, closeCardViewer } = useCardViewer({
  roomRouteKey: computed(() => props.roomRouteKey),
  roomId: computed(() => props.roomId),
})

// O(1) lookup en template para evitar includes() O(n) en cada celda
const drawnSet = computed(() => new Set(props.drawnNumbers))
</script>

<template>
  <!-- Players / Winners modal -->
  <Transition name="gm-fade">
    <div v-if="show" class="gm-overlay" @click.self="emit('close')">
      <div class="gm-modal pm-modal" role="dialog" aria-modal="true" aria-labelledby="pm-title-text">

        <div class="pm-modal__head">
          <div class="pm-modal__title-row">
            <div class="pm-modal__icon"><i class="fas fa-users" /></div>
            <div>
              <div id="pm-title-text" class="pm-modal__title">Jugadores</div>
              <div class="pm-modal__sub">
                <span>{{ livePlayers }} jugador{{ livePlayers === 1 ? '' : 'es' }}</span>
                <template v-if="totalWinners > 0">
                  <span class="pm-modal__sub-sep">·</span>
                  <i class="fas fa-trophy" style="color:#ffd700;font-size:10px" />
                  <span>{{ totalWinners }} ganador{{ totalWinners === 1 ? '' : 'es' }}</span>
                </template>
                <template v-if="drawnNumbers.length > 0">
                  <span class="pm-modal__sub-sep">·</span>
                  <i class="fas fa-shuffle" style="font-size:9px;opacity:0.6" />
                  <span>{{ drawnNumbers.length }} fichas</span>
                </template>
              </div>
            </div>
          </div>
          <button class="pm-modal__close" @click="emit('close')">
            <i class="fas fa-times" />
          </button>
        </div>

        <div class="pm-modal__body">
          <WinnersList
            v-if="totalWinners > 0"
            :game-end-data="gameEndData"
            :user-id="userId"
            :currency-sym="currencySym"
            :loading-card-id="loadingCardId"
            @view-card="openCardViewer"
          />
          <ActivePlayersList
            v-else
            :active-players="activePlayers"
            :loading-players="loadingPlayers"
            :players-error="playersError"
            :user-id="userId"
            @retry="fetchActivePlayers"
          />
        </div>

        <div class="pm-modal__foot">
          <button class="pm-close-btn" @click="emit('close')">
            <i class="fas fa-check" /> Cerrar
          </button>
        </div>

      </div>
    </div>
  </Transition>

  <CardViewerModal
    :viewing-card="viewingCard"
    :viewing-card-owner="viewingCardOwner"
    :drawn-set="drawnSet"
    :customer-logo="customerLogo"
    @close="closeCardViewer"
  />
</template>

<style scoped>
/* ── Shared overlay / modal shell ── */
.gm-overlay {
  position: fixed; inset: 0; z-index: 1200;
  background: rgba(4,6,20,0.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}

.gm-modal {
  background: linear-gradient(160deg, rgba(11,18,48,0.98) 0%, rgba(6,10,30,0.98) 100%);
  border: 1px solid rgba(80,120,255,0.18); border-radius: 22px;
  box-shadow: 0 24px 80px rgba(0,0,0,0.7), 0 1px 0 rgba(255,255,255,0.04) inset;
  width: 100%; max-width: 460px; overflow: hidden; position: relative;
  max-height: 90vh; display: flex; flex-direction: column;
}

/* ── Players modal ── */
.pm-modal { max-width: 460px; }

.pm-modal__head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 20px 12px; border-bottom: 1px solid rgba(80,120,255,0.1);
}
.pm-modal__title-row { display: flex; align-items: center; gap: 12px; }
.pm-modal__icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  display: flex; align-items: center; justify-content: center; font-size: 14px; color: #7c9dff;
}
.pm-modal__title { font-size: 15px; font-weight: 800; color: #e0eaff; }
.pm-modal__sub { display: flex; align-items: center; gap: 5px; font-size: 11px; color: #4a68a8; margin-top: 2px; }
.pm-modal__sub-sep { color: #2a3860; }
.pm-modal__close {
  width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08); color: #5a7acc; font-size: 12px;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: background 0.2s, color 0.2s; flex-shrink: 0;
}
.pm-modal__close:hover { background: rgba(255,255,255,0.1); color: #a0c0ff; }

.pm-modal__body { padding: 12px 20px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; }
.pm-modal__body::-webkit-scrollbar { width: 4px; }
.pm-modal__body::-webkit-scrollbar-track { background: transparent; }
.pm-modal__body::-webkit-scrollbar-thumb { background: rgba(80,120,255,0.2); border-radius: 2px; }

.pm-modal__foot { padding: 12px 20px; border-top: 1px solid rgba(80,120,255,0.1); }
.pm-close-btn {
  width: 100%; padding: 9px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  color: #7c9dff; font-size: 12px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s, color 0.2s;
}
.pm-close-btn:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; }

/* ── Fade transition ── */
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.25s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }

/* ── Responsive ── */
@media (max-width: 480px) {
  .gm-modal { border-radius: 16px; }
}
</style>
