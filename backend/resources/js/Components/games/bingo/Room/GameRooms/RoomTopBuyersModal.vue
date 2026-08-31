<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import backendClient from '@js/plugins/axios'
import echo from '@js/plugins/echo'
import { shortRoomCode } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import type { BingoGameRoomTopBuyer } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = withDefaults(defineProps<{
  roomId: string | null
  /** uuid_code completo de la sala — se trunca internamente (ver shortRoomCode),
   * igual criterio que RoomCardThumbnail/BingoRoomInfoModal. */
  roomUuidCode?: string | null
  /** Prefijo del endpoint — distinto según se use desde el lobby autenticado
   * (bingo/game-rooms) o la vitrina pública sin sesión (game-rooms, ver
   * PublicController::gameRoomsShowcase y routes/modules/public.php). */
  endpointBase?: string
}>(), {
  endpointBase: '/bingo/game-rooms',
})

const emit = defineEmits<{ (e: 'close'): void }>()

const buyers  = ref<BingoGameRoomTopBuyer[]>([])
const loading = ref(false)
const error   = ref<string | null>(null)
const roomCode = ref<string | null>(null)

// Medalla para los primeros 3 puestos, número simple para el resto — mismo
// lenguaje visual que el ranking mensual de vendedores (SellerRankingSection).
const MEDALS = ['🥇', '🥈', '🥉']

let subscribedChannel: string | null = null
let refetchTimer: ReturnType<typeof setTimeout> | null = null

async function fetchBuyers(roomId: string): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const { data } = await backendClient.get(`${props.endpointBase}/${roomId}/top-buyers`)
    buyers.value = data.buyers ?? []
  } catch {
    error.value = 'No se pudo cargar el ranking de esta sala.'
  } finally {
    loading.value = false
  }
}

/** Reintenta el fetch tras una compra en vivo (ver evento RoomUpdated más
 * abajo) — debounced 800ms: varias compras seguidas en la misma sala
 * disparan varios RoomUpdated casi simultáneos, y no tiene sentido pedir
 * el ranking una vez por cada uno. */
function scheduleRefetch(roomId: string): void {
  if (refetchTimer) clearTimeout(refetchTimer)
  refetchTimer = setTimeout(() => fetchBuyers(roomId), 800)
}

function unsubscribe(): void {
  if (subscribedChannel) {
    echo.leaveChannel(subscribedChannel)
    subscribedChannel = null
  }
  if (refetchTimer) {
    clearTimeout(refetchTimer)
    refetchTimer = null
  }
}

function subscribe(roomId: string): void {
  unsubscribe()

  const channelName = `bingo.room.${roomId}`
  echo.channel(channelName).listen('.RoomUpdated', () => {
    scheduleRefetch(roomId)
  })
  subscribedChannel = channelName
}

watch(() => props.roomId, (roomId) => {
  unsubscribe()
  buyers.value = []
  roomCode.value = shortRoomCode(props.roomUuidCode)

  if (!roomId) return

  fetchBuyers(roomId)
  subscribe(roomId)
}, { immediate: true })

onUnmounted(unsubscribe)
</script>

<template>
  <Teleport to="body">
    <Transition name="tbm-fade">
      <div v-if="roomId" class="tbm-backdrop" @click.self="emit('close')">
        <div class="tbm-card" role="dialog" aria-modal="true" aria-labelledby="tbm-title">
          <div class="tbm-header">
            <div class="tbm-header-text">
              <h3 id="tbm-title" class="tbm-title">
                <i class="fas fa-ranking-star" /> Top de Compras
              </h3>
              <p v-if="roomCode" class="tbm-subtitle">
                <i class="fas fa-hashtag" />{{ roomCode }}
              </p>
            </div>
            <button class="tbm-close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>
          </div>

          <div class="tbm-body">
            <div v-if="loading && !buyers.length" class="tbm-state">
              <span class="tbm-spinner" /> Cargando ranking…
            </div>
            <div v-else-if="error" class="tbm-state tbm-state--error">
              <i class="fas fa-triangle-exclamation" /> {{ error }}
            </div>
            <div v-else-if="!buyers.length" class="tbm-state">
              <i class="fas fa-ticket" /> Todavía no hay cartones vendidos en esta sala.
            </div>
            <ul v-else class="tbm-list">
              <li v-for="(buyer, idx) in buyers" :key="buyer.user_id" class="tbm-row">
                <span class="tbm-rank">{{ MEDALS[idx] ?? `#${idx + 1}` }}</span>
                <span v-if="buyer.avatar_url" class="tbm-avatar">
                  <img :src="buyer.avatar_url" :alt="buyer.name" />
                </span>
                <span v-else class="tbm-avatar tbm-avatar--fallback">
                  {{ buyer.name.charAt(0).toUpperCase() }}
                </span>
                <span class="tbm-name">{{ buyer.name }}</span>
                <span class="tbm-count"><i class="fas fa-ticket" /> {{ buyer.cards_count }}</span>
              </li>
            </ul>
          </div>

          <div class="tbm-footer">
            <span class="tbm-footer-count">
              <span class="tbm-footer-live" aria-hidden="true" />
              {{ buyers.length }} participante{{ buyers.length !== 1 ? 's' : '' }}
            </span>
            <button class="tbm-footer-btn" @click="emit('close')">Cerrar</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.tbm-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
  background: rgba(6, 10, 24, .72);
  backdrop-filter: blur(4px);
}
.tbm-fade-enter-active, .tbm-fade-leave-active { transition: opacity .25s ease; }
.tbm-fade-enter-from, .tbm-fade-leave-to { opacity: 0; }

.tbm-card {
  width: 100%; max-width: 380px;
  max-height: 84vh;
  display: flex; flex-direction: column;
  border-radius: 20px;
  background: linear-gradient(165deg, #1a1440 0%, #120c30 65%);
  border: 1px solid rgba(255, 215, 90, .18);
  box-shadow: 0 20px 60px rgba(0,0,0,.55);
  overflow: hidden;
}

.tbm-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 10px;
  padding: 18px 16px 12px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.tbm-header-text { min-width: 0; }
.tbm-title {
  margin: 0;
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 15px; font-weight: 800; letter-spacing: .4px;
  color: #ffd75a;
  display: flex; align-items: center; gap: 7px;
  white-space: nowrap;
}
.tbm-subtitle {
  display: inline-flex; align-items: center; gap: 3px;
  margin: 5px 0 0;
  font-size: 10px; font-weight: 600; letter-spacing: .4px;
  color: #6a86ac;
}
.tbm-subtitle i { font-size: 8px; opacity: .8; }
.tbm-close {
  flex-shrink: 0;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.04);
  color: #c8d8f0;
  cursor: pointer;
  transition: background .2s ease;
}
.tbm-close:hover { background: rgba(255,255,255,.1); }

.tbm-body {
  flex: 1; min-height: 0; overflow-y: auto;
  padding: 10px 14px;
}

.tbm-state {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 32px 12px;
  font-size: 12.5px; font-weight: 600;
  color: #7a96c8;
  text-align: center;
}
.tbm-state--error { color: #ff8a8a; }
.tbm-spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.2); border-top-color: #ffd75a;
  animation: tbmSpin .7s linear infinite;
}
@keyframes tbmSpin { to { transform: rotate(360deg); } }

.tbm-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }

.tbm-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px;
  border-radius: 12px;
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.05);
  transition: background .3s ease;
}
.tbm-rank {
  flex-shrink: 0; width: 26px; text-align: center;
  font-size: 15px; font-weight: 800;
  color: #ffd75a;
}
.tbm-avatar {
  flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.08);
}
.tbm-avatar img { width: 100%; height: 100%; object-fit: cover; }
.tbm-avatar--fallback {
  font-size: 12px; font-weight: 800; color: #ffd75a;
  background: rgba(255,215,90,.12);
}
.tbm-name {
  flex: 1; min-width: 0;
  font-size: 12.5px; font-weight: 700;
  color: #ff9ad0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.tbm-count {
  flex-shrink: 0;
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 9px; border-radius: 999px;
  background: #ffd75a; color: #2a1f00;
  font-size: 11px; font-weight: 800;
}

.tbm-footer {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 12px 16px 16px;
  border-top: 1px solid rgba(255,255,255,.06);
}
.tbm-footer-count {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 10.5px; font-weight: 600; color: #6a86ac;
}
/* Punto "en vivo" — indica que el ranking se actualiza solo, sin recargar */
.tbm-footer-live {
  width: 6px; height: 6px; border-radius: 50%;
  background: #2af598;
  box-shadow: 0 0 6px rgba(42,245,152,.6);
  animation: tbmLivePulse 1.8s ease-in-out infinite;
}
@keyframes tbmLivePulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }
.tbm-footer-btn {
  padding: 7px 16px; border-radius: 999px; border: none;
  background: #ffd75a; color: #2a1f00;
  font-size: 12px; font-weight: 800;
  cursor: pointer;
}
.tbm-footer-btn:hover { filter: brightness(1.08); }
</style>
