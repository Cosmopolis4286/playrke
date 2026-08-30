<script setup lang="ts">
import { ref, watch } from 'vue'
import backendClient from '@js/plugins/axios'
import type { BingoGameRoomTopBuyer } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = withDefaults(defineProps<{
  roomId: string | null
  roomLabel?: string | null
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

// Medalla para los primeros 3 puestos, número simple para el resto — mismo
// lenguaje visual que el ranking mensual de vendedores (SellerRankingSection).
const MEDALS = ['🥇', '🥈', '🥉']

watch(() => props.roomId, async (roomId) => {
  if (!roomId) return

  loading.value = true
  error.value = null
  buyers.value = []

  try {
    const { data } = await backendClient.get(`${props.endpointBase}/${roomId}/top-buyers`)
    buyers.value = data.buyers ?? []
  } catch {
    error.value = 'No se pudo cargar el ranking de esta sala.'
  } finally {
    loading.value = false
  }
}, { immediate: true })
</script>

<template>
  <Teleport to="body">
    <Transition name="tbm-fade">
      <div v-if="roomId" class="tbm-backdrop" @click.self="emit('close')">
        <div class="tbm-card" role="dialog" aria-modal="true" aria-labelledby="tbm-title">
          <div class="tbm-header">
            <div>
              <h3 id="tbm-title" class="tbm-title">
                <i class="fas fa-ranking-star" /> Ranking Top 10
              </h3>
              <p v-if="roomLabel" class="tbm-subtitle">{{ roomLabel }}</p>
            </div>
            <button class="tbm-close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>
          </div>

          <div class="tbm-body">
            <div v-if="loading" class="tbm-state">
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
            <span class="tbm-footer-count">{{ buyers.length }} participante{{ buyers.length !== 1 ? 's' : '' }}</span>
            <button class="tbm-footer-btn" @click="emit('close')">Cerrar Ventana</button>
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
  gap: 8px;
  padding: 18px 18px 12px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.tbm-title {
  margin: 0;
  font-family: 'Orbitron', 'Anton', sans-serif;
  font-size: 16px; font-weight: 800; letter-spacing: .5px;
  color: #ffd75a;
  display: flex; align-items: center; gap: 8px;
}
.tbm-subtitle {
  margin: 4px 0 0;
  font-size: 11px; font-weight: 600; letter-spacing: .3px;
  color: #8ea0c4;
}
.tbm-close {
  flex-shrink: 0;
  width: 30px; height: 30px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.04);
  color: #c8d8f0;
  cursor: pointer;
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
  padding: 12px 18px 16px;
  border-top: 1px solid rgba(255,255,255,.06);
}
.tbm-footer-count { font-size: 10.5px; font-weight: 600; color: #6a86ac; }
.tbm-footer-btn {
  padding: 8px 18px; border-radius: 999px; border: none;
  background: #ffd75a; color: #2a1f00;
  font-size: 12px; font-weight: 800;
  cursor: pointer;
}
.tbm-footer-btn:hover { filter: brightness(1.08); }
</style>
