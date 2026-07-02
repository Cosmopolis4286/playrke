<script setup lang="ts">
import { ref } from 'vue'
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

const viewingCard = ref<null | { serialNumber: string; cardMatrix: number[][]; loading?: boolean }>(null)
const viewingCardOwner = ref('')

function toRowMatrix(cardNumbers: Record<string, (number | null)[]> | number[][]): number[][] {
  if (Array.isArray(cardNumbers)) return cardNumbers as number[][]
  const obj = cardNumbers as Record<string, (number | null)[]>
  const cols = ['B', 'I', 'N', 'G', 'O']
  const numRows = obj[cols[0]]?.length ?? 0
  const rows: number[][] = []
  for (let r = 0; r < numRows; r++) {
    rows.push(cols.map(c => (obj[c]?.[r] ?? null) as number))
  }
  return rows
}

async function openCardViewer(winner: WinnerEntry) {
  if (!winner.card_id) return
  viewingCardOwner.value = winner.user_name
  viewingCard.value = { serialNumber: winner.card_serial, cardMatrix: [], loading: true }
  try {
    const routeKey = props.roomRouteKey || props.roomId
    const res = await fetch(`/bingo/${routeKey}/card/${winner.card_id}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    const data = await res.json()
    if (data.success && data.card) {
      viewingCard.value = {
        serialNumber: data.card.serialNumber ?? winner.card_serial,
        cardMatrix:   toRowMatrix(data.card.cardMatrix ?? {}),
        loading: false,
      }
    } else {
      viewingCard.value = null
    }
  } catch {
    viewingCard.value = null
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
      <div class="gm-modal pm-modal">

        <div class="pm-modal__head">
          <div class="pm-modal__title-row">
            <div class="pm-modal__icon"><i class="fas fa-users" /></div>
            <div>
              <div class="pm-modal__title">Jugadores</div>
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
              <button v-if="w.card_id" class="pm-card-btn" title="Ver cartón" @click="openCardViewer(w)">
                <i class="fas fa-th" />
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
    <div v-if="viewingCard" class="gm-overlay" @click.self="closeCardViewer">
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
              <div v-for="l in ['B','I','N','G','O']" :key="l" class="cv-card__lbl" :class="`cv-card__lbl--${l}`">{{ l }}</div>
            </div>
            <div class="cv-card__grid">
              <div v-for="(row, r) in viewingCard.cardMatrix" :key="`row-${r}`" class="cv-card__row">
                <div
                  v-for="(num, c) in row"
                  :key="`cell-${r}-${c}`"
                  class="cv-cell"
                  :class="{
                    'cv-cell--drawn': num > 0 && drawnNumbers.includes(num),
                    'cv-cell--free':  num === 0,
                  }"
                >
                  <template v-if="num === 0">
                    <img v-if="customerLogo" :src="customerLogo" class="cv-cell__logo" alt="logo" />
                    <span v-else class="cv-cell__free-icon"><i class="fas fa-star" /></span>
                  </template>
                  <template v-else>
                    <span class="cv-cell__num">{{ num }}</span>
                    <span v-if="drawnNumbers.includes(num)" class="cv-cell__mark"><i class="fas fa-check" /></span>
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
