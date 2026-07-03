<script setup lang="ts">
import { ref, computed } from 'vue'
import type { WinnerEntry, GameEndData } from './types'

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

type ViewedCard = { serialNumber: string; cardMatrix: number[][]; colLabels?: string[]; loading?: boolean }

const viewingCard      = ref<ViewedCard | null>(null)
const viewingCardOwner = ref('')
const loadingCardId    = ref<string | null>(null)

// Cache de cartones ya consultados en esta sesión del modal — evita refetch al
// reabrir el mismo cartón ganador (los datos de un cartón no cambian una vez
// declarado ganador, así que no hay riesgo de servir datos obsoletos).
const cardCache = new Map<string, ViewedCard>()

// O(1) lookup en template para evitar includes() O(n) en cada celda
const drawnSet = computed(() => new Set(props.drawnNumbers))

function toRowMatrix(cardNumbers: Record<string, (number | null)[]> | number[][]): { matrix: number[][]; cols: string[] } {
  if (Array.isArray(cardNumbers)) {
    return { matrix: cardNumbers as number[][], cols: ['B', 'I', 'N', 'G', 'O'] }
  }
  const obj = cardNumbers as Record<string, (number | null)[]>
  // Columnas dinámicas: funciona con BINGO_75, 90, 80, 30
  const cols = Object.keys(obj)
  if (!cols.length) {
    console.warn('[PlayersModal] toRowMatrix: cardMatrix vacía', cardNumbers)
    return { matrix: [], cols: [] }
  }
  const numRows = obj[cols[0]]?.length ?? 0
  const matrix: number[][] = []
  for (let r = 0; r < numRows; r++) {
    matrix.push(cols.map(c => (obj[c]?.[r] ?? 0) as number))
  }
  return { matrix, cols }
}

async function openCardViewer(winner: WinnerEntry) {
  if (!winner.card_id || loadingCardId.value === winner.card_id) return

  const cached = cardCache.get(winner.card_id)
  if (cached) {
    viewingCardOwner.value = winner.user_name
    viewingCard.value      = cached
    return
  }

  loadingCardId.value    = winner.card_id
  viewingCardOwner.value = winner.user_name
  viewingCard.value      = { serialNumber: winner.card_serial, cardMatrix: [], loading: true }
  try {
    const routeKey = props.roomRouteKey || props.roomId
    const res = await fetch(`/bingo/${routeKey}/card/${winner.card_id}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    const data = await res.json()
    if (data.success && data.card) {
      const { matrix, cols } = toRowMatrix(data.card.cardMatrix ?? {})
      const resolved: ViewedCard = {
        serialNumber: data.card.serialNumber ?? winner.card_serial,
        cardMatrix:   matrix,
        colLabels:    cols,
        loading:      false,
      }
      cardCache.set(winner.card_id, resolved)
      viewingCard.value = resolved
    } else {
      viewingCard.value = null
    }
  } catch {
    viewingCard.value = null
  } finally {
    loadingCardId.value = null
  }
}

function closeCardViewer() {
  viewingCard.value = null
  viewingCardOwner.value = ''
}
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
              </div>
            </div>
          </div>
          <button class="pm-modal__close" @click="emit('close')">
            <i class="fas fa-times" />
          </button>
        </div>

        <div class="pm-modal__body">
          <template v-if="totalWinners > 0">
            <div class="pm-section-label"><i class="fas fa-trophy" /> Ganadores</div>
            <div
              v-for="(w, idx) in gameEndData?.winners ?? []"
              :key="`pm-w-${w.card_id}-${idx}`"
              class="pm-row"
              :class="{ 'pm-row--me': String(w.user_id) === String(userId) }"
            >
              <div class="pm-row__rank">#{{ idx + 1 }}</div>
              <div class="pm-row__info">
                <div class="pm-row__name">
                  {{ w.user_name }}
                  <span v-if="String(w.user_id) === String(userId)" class="pm-you">TÚ</span>
                </div>
                <div class="pm-row__meta">
                  <span class="pm-serial">{{ w.card_serial }}</span>
                  <span v-if="w.round" class="pm-badge pm-badge--round">Ronda {{ w.round }}</span>
                  <span v-if="w.pattern" class="pm-badge pm-badge--pattern">{{ w.pattern }}</span>
                </div>
              </div>
              <div class="pm-row__prize">
                <span class="pm-row__sym">{{ currencySym }}</span>{{ Number(w.prize).toFixed(2) }}
              </div>
              <button
                v-if="w.card_id"
                class="pm-card-btn"
                :disabled="loadingCardId === w.card_id"
                :title="loadingCardId === w.card_id ? 'Cargando…' : 'Ver cartón'"
                @click="openCardViewer(w)"
              >
                <i :class="loadingCardId === w.card_id ? 'fas fa-spinner fa-spin' : 'fas fa-th'" />
              </button>
            </div>
          </template>
          <div v-else class="pm-empty">
            <i class="fas fa-info-circle" />
            <span>Aún no hay ganadores registrados.</span>
          </div>
        </div>

        <div class="pm-modal__foot">
          <button class="pm-close-btn" @click="emit('close')">
            <i class="fas fa-check" /> Cerrar
          </button>
        </div>

      </div>
    </div>
  </Transition>

  <!-- Card viewer modal -->
  <Transition name="gm-fade">
    <div v-if="viewingCard" class="gm-overlay gm-overlay--stacked" @click.self="closeCardViewer">
      <div class="gm-modal cv-modal">

        <div class="cv-head">
          <div class="cv-head__icon"><i class="fas fa-ticket-alt" /></div>
          <div class="cv-head__info">
            <div class="cv-head__title">
              Cartón Ganador
              <span class="cv-head__serial">{{ viewingCard.serialNumber }}</span>
            </div>
            <div class="cv-head__owner">{{ viewingCardOwner }}</div>
          </div>
          <button class="pm-modal__close" @click="closeCardViewer"><i class="fas fa-times" /></button>
        </div>

        <div v-if="viewingCard.loading" class="cv-loading">
          <div class="cv-spinner" /> Cargando cartón…
        </div>

        <template v-else-if="viewingCard.cardMatrix && viewingCard.cardMatrix.length">
          <div class="cv-card">
            <div class="cv-card__header">
              <div v-for="l in (viewingCard.colLabels ?? ['B','I','N','G','O'])" :key="l" class="cv-card__lbl" :class="`cv-card__lbl--${l}`">{{ l }}</div>
            </div>
            <div class="cv-card__grid">
              <div v-for="(row, r) in viewingCard.cardMatrix" :key="`row-${r}`" class="cv-card__row">
                <div
                  v-for="(num, c) in row"
                  :key="`cell-${r}-${c}`"
                  class="cv-cell"
                  :class="{
                    'cv-cell--drawn': num > 0 && drawnSet.has(num),
                    'cv-cell--free':  num === 0,
                  }"
                >
                  <template v-if="num === 0">
                    <img v-if="customerLogo" :src="customerLogo" class="cv-cell__logo" alt="logo" crossorigin="anonymous" />
                    <span v-else class="cv-cell__free-icon"><i class="fas fa-star" /></span>
                  </template>
                  <template v-else>
                    <span class="cv-cell__num">{{ num }}</span>
                    <span v-if="drawnSet.has(num)" class="cv-cell__mark"><i class="fas fa-check" /></span>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <div class="cv-legend">
            <span class="cv-legend__item cv-legend__item--drawn"><span class="cv-legend__dot" />Marcado</span>
            <span class="cv-legend__item cv-legend__item--free"><span class="cv-legend__dot" />Libre</span>
            <span class="cv-legend__item cv-legend__item--normal"><span class="cv-legend__dot" />Sin marcar</span>
          </div>
        </template>

        <div v-else class="pm-empty">
          <i class="fas fa-exclamation-triangle" />
          <span>No se pudo cargar el cartón.</span>
        </div>

        <div class="pm-modal__foot">
          <button class="pm-close-btn" @click="closeCardViewer">
            <i class="fas fa-arrow-left" /> Volver
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* ── Shared overlay / modal shell ── */
.gm-overlay {
  position: fixed; inset: 0; z-index: 1200;
  background: rgba(4,6,20,0.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.gm-overlay--stacked { z-index: 1300; }

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

.pm-section-label { font-size: 9px; font-weight: 700; color: #3a5080; text-transform: uppercase; letter-spacing: 1.2px; display: flex; align-items: center; gap: 5px; padding: 4px 0; }
.pm-section-label i { color: #ffd700; font-size: 8px; }

.pm-row {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 10px; border-radius: 10px;
  background: rgba(255,255,255,0.025); border: 1px solid rgba(80,120,255,0.08);
  transition: background 0.2s;
}
.pm-row--me { background: rgba(42,245,152,0.06); border-color: rgba(42,245,152,0.2); }
.pm-row__rank { font-size: 10px; font-weight: 700; color: #4a68a8; width: 22px; flex-shrink: 0; }
.pm-row__info { flex: 1; min-width: 0; }
.pm-row__name { font-size: 12px; font-weight: 700; color: #c8d8ff; display: flex; align-items: center; gap: 5px; }
.pm-row__meta { display: flex; align-items: center; gap: 5px; margin-top: 2px; flex-wrap: wrap; }
.pm-row__prize { font-size: 13px; font-weight: 800; color: #ffd700; font-family: 'Courier New', monospace; flex-shrink: 0; white-space: nowrap; }
.pm-row__sym { font-size: 10px; font-weight: 700; margin-right: 2px; }
.pm-serial { font-size: 9.5px; color: #3a5080; }
.pm-you { font-size: 8px; font-weight: 900; color: #2af598; background: rgba(42,245,152,0.12); border: 1px solid rgba(42,245,152,0.3); border-radius: 4px; padding: 1px 5px; letter-spacing: 0.5px; }
.pm-badge { font-size: 9px; font-weight: 700; border-radius: 4px; padding: 1px 6px; }
.pm-badge--round { background: rgba(80,120,255,0.1); border: 1px solid rgba(80,120,255,0.2); color: #7c9dff; }
.pm-badge--pattern { background: rgba(180,80,255,0.1); border: 1px solid rgba(180,80,255,0.2); color: #c07fff; }
.pm-card-btn {
  width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  color: #7c9dff; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, color 0.2s;
}
.pm-card-btn:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; }

.pm-empty { display: flex; align-items: center; gap: 8px; padding: 20px 0; color: #3a5080; font-size: 12px; }
.pm-empty i { font-size: 14px; }

.pm-modal__foot { padding: 12px 20px; border-top: 1px solid rgba(80,120,255,0.1); }
.pm-close-btn {
  width: 100%; padding: 9px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  color: #7c9dff; font-size: 12px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s, color 0.2s;
}
.pm-close-btn:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; }

/* ── Card Viewer modal ── */
.cv-modal { max-width: 380px; }

.cv-head {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px 12px; border-bottom: 1px solid rgba(80,120,255,0.1);
}
.cv-head__icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  display: flex; align-items: center; justify-content: center; font-size: 14px; color: #7c9dff;
  flex-shrink: 0;
}
.cv-head__info { flex: 1; min-width: 0; }
.cv-head__title { font-size: 13px; font-weight: 800; color: #e0eaff; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.cv-head__serial { font-size: 10px; color: #4a68a8; font-family: 'Courier New', monospace; background: rgba(80,120,255,0.08); border: 1px solid rgba(80,120,255,0.12); border-radius: 5px; padding: 1px 6px; }
.cv-head__owner { font-size: 11px; color: #5a7acc; margin-top: 2px; }

.cv-loading { display: flex; align-items: center; gap: 10px; padding: 30px 20px; color: #5a7acc; font-size: 12px; }
.cv-spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(80,120,255,0.2); border-top-color: #7c9dff; animation: cv-spin 0.7s linear infinite; }
@keyframes cv-spin { to { transform: rotate(360deg) } }

.cv-card { padding: 16px 20px; }
.cv-card__header { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 4px; }
.cv-card__lbl {
  display: flex; align-items: center; justify-content: center;
  height: 28px; border-radius: 6px; font-size: 13px; font-weight: 900; letter-spacing: 1px;
}
.cv-card__lbl--B { background: rgba(40,140,255,0.15); color: #78c8ff; }
.cv-card__lbl--I { background: rgba(180,80,255,0.15); color: #d08cff; }
.cv-card__lbl--N { background: rgba(42,245,152,0.12); color: #50f0a0; }
.cv-card__lbl--G { background: rgba(255,200,0,0.12); color: #ffdc50; }
.cv-card__lbl--O { background: rgba(255,100,50,0.12); color: #ff9650; }

.cv-card__grid { display: flex; flex-direction: column; gap: 4px; }
.cv-card__row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; }
.cv-cell {
  display: flex; align-items: center; justify-content: center;
  height: 46px; border-radius: 8px; position: relative;
  background: rgba(255,255,255,0.035); border: 1px solid rgba(80,120,255,0.1);
  transition: all 0.25s ease;
}
.cv-cell--drawn { background: rgba(42,245,152,0.12); border-color: rgba(42,245,152,0.35); box-shadow: 0 0 10px rgba(42,245,152,0.1); }
.cv-cell--free  { background: rgba(255,200,0,0.07); border-color: rgba(255,200,0,0.2); }
.cv-cell__num { font-size: 14px; font-weight: 700; color: #c8d8ff; font-family: 'Courier New', monospace; }
.cv-cell__mark {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  background: rgba(42,245,152,0.12); border-radius: 8px; font-size: 16px; color: #2af598;
}
.cv-cell__logo { width: 28px; height: 28px; object-fit: contain; opacity: 0.6; }
.cv-cell__free-icon { font-size: 14px; color: #ffc840; }

.cv-legend { display: flex; gap: 14px; padding: 0 20px 12px; }
.cv-legend__item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #4a68a8; }
.cv-legend__dot { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.cv-legend__item--drawn .cv-legend__dot { background: rgba(42,245,152,0.3); border: 1px solid rgba(42,245,152,0.5); }
.cv-legend__item--free  .cv-legend__dot { background: rgba(255,200,0,0.2); border: 1px solid rgba(255,200,0,0.4); }
.cv-legend__item--normal .cv-legend__dot { background: rgba(255,255,255,0.06); border: 1px solid rgba(80,120,255,0.15); }

/* ── Fade transition ── */
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.25s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }

/* ── Responsive ── */
@media (max-width: 480px) {
  .gm-modal { border-radius: 16px; }
  .cv-cell { height: 38px; }
  .cv-cell__num { font-size: 12px; }
}
</style>
