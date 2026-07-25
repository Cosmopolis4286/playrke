<script setup lang="ts">
import { computed } from 'vue'
import type { LobbyCard } from '@js/composables/games/bingo/room/lobby/useLobbyMyCards'

const props = defineProps<{
  show: boolean
  cards: LobbyCard[]
  loading: boolean
  fetchError: boolean
}>()

const emit = defineEmits<{ close: []; retry: [] }>()

const BINGO_COLS = ['B', 'I', 'N', 'G', 'O'] as const

const customCount = computed(() => props.cards.filter(c => c.isCustomCard).length)
const randomCount = computed(() => props.cards.filter(c => !c.isCustomCard).length)

function formatDate(raw: string | null): string {
  if (!raw) return '—'
  const d = new Date(raw)
  return d.toLocaleString('es-VE', {
    day: '2-digit', month: '2-digit', year: '2-digit',
    hour: '2-digit', minute: '2-digit',
  })
}

function colNumbers(card: LobbyCard, col: string): number[] {
  return card.cardMatrix[col] ?? []
}
</script>

<template>
  <Teleport to="body">
  <Transition name="mc-fade">
    <div v-if="show" class="mc-overlay" @click.self="emit('close')">
      <div class="mc-modal" role="dialog" aria-modal="true" aria-label="Mis Cartones">

        <!-- ── Header ── -->
        <div class="mc-header">
          <span class="mc-header__icon"><i class="fas fa-ticket-alt" /></span>
          <h3 class="mc-header__title">Mis Cartones</h3>
          <template v-if="!loading && !fetchError && cards.length > 0">
            <span class="mc-header__sep">·</span>
            <span class="mc-stat mc-stat--total">{{ cards.length }} total</span>
            <span v-if="customCount > 0" class="mc-stat mc-stat--custom">
              <i class="fas fa-pen" />{{ customCount }} personalizado{{ customCount !== 1 ? 's' : '' }}
            </span>
            <span v-if="randomCount > 0" class="mc-stat mc-stat--random">
              <i class="fas fa-random" />{{ randomCount }} al azar
            </span>
          </template>
          <span v-else class="mc-header__status">
            {{ loading ? 'Cargando…' : fetchError ? 'Error al cargar' : '' }}
          </span>
          <button class="mc-close" aria-label="Cerrar" @click="emit('close')">
            <i class="fas fa-times" />
          </button>
        </div>

        <!-- ── Loading ── -->
        <div v-if="loading" class="mc-state">
          <div class="mc-spinner" />
          <span>Cargando tus cartones…</span>
        </div>

        <!-- ── Error ── -->
        <div v-else-if="fetchError" class="mc-state mc-state--error">
          <i class="fas fa-exclamation-circle mc-state__icon" />
          <span>No se pudieron cargar los cartones</span>
          <button class="mc-retry" @click="emit('retry')">
            <i class="fas fa-redo" /> Reintentar
          </button>
        </div>

        <!-- ── Vacío ── -->
        <div v-else-if="cards.length === 0" class="mc-state">
          <i class="fas fa-ticket-alt mc-state__icon" />
          <span>Aún no tienes cartones en esta sala</span>
        </div>

        <!-- ── Rejilla de cartones ── -->
        <div v-else class="mc-scroll">
          <div class="mc-grid-layout">
            <div
              v-for="(card, idx) in cards"
              :key="card.id"
              class="mc-card"
              :class="card.isCustomCard ? 'mc-card--custom' : 'mc-card--random'"
            >
              <!-- Cabecera del cartón -->
              <div class="mc-card__head">
                <span class="mc-card__num">#{{ idx + 1 }}</span>
                <span class="mc-card__type-badge" :class="card.isCustomCard ? 'mc-card__type-badge--custom' : 'mc-card__type-badge--random'">
                  <i :class="card.isCustomCard ? 'fas fa-pen' : 'fas fa-random'" />
                  {{ card.isCustomCard ? 'Personalizado' : 'Al azar' }}
                </span>
              </div>

              <!-- Info: serial + fecha -->
              <div class="mc-card__meta">
                <span class="mc-card__serial"><i class="fas fa-hashtag" />{{ card.serialNumber }}</span>
                <span class="mc-card__date"><i class="fas fa-clock" />{{ formatDate(card.purchaseDate) }}</span>
              </div>

              <!-- Grid BINGO compacto -->
              <div class="mc-bingo">
                <!-- Letras cabecera -->
                <div v-for="col in BINGO_COLS" :key="`h-${col}`" class="mc-bingo__letter">{{ col }}</div>

                <!-- Números: iterar por fila (0..4) × columna para que el CSS grid los coloque bien -->
                <template v-for="row in 5" :key="`r${row}`">
                  <div
                    v-for="col in BINGO_COLS"
                    :key="`${col}-${row}`"
                    class="mc-bingo__cell"
                    :class="{
                      'mc-bingo__cell--free': colNumbers(card, col)[row - 1] === 0,
                    }"
                  >
                    <template v-if="colNumbers(card, col)[row - 1] === 0">
                      <i class="fas fa-star" />
                    </template>
                    <template v-else>{{ colNumbers(card, col)[row - 1] }}</template>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Transition>
  </Teleport>
</template>

<style scoped>
/* ── Overlay ── */
.mc-overlay {
  position: fixed; inset: 0; z-index: 9100;
  background: rgba(2,5,16,.82); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  padding: 14px;
}

/* ── Modal ── */
.mc-modal {
  position: relative;
  width: 100%; max-width: 480px; max-height: 88vh;
  background: linear-gradient(160deg, #0c1430 0%, #080e22 100%);
  border: 1px solid rgba(100,140,255,.18);
  border-radius: 14px;
  box-shadow: 0 20px 60px rgba(0,0,0,.8), 0 0 0 1px rgba(255,255,255,.04) inset;
  display: flex; flex-direction: column; overflow: hidden;
}

/* ── Header ── */
.mc-header {
  display: flex; align-items: center; flex-wrap: nowrap;
  gap: 7px; padding: 9px 12px;
  border-bottom: 1px solid rgba(255,255,255,.07); flex-shrink: 0;
}
.mc-header__icon {
  width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
  background: rgba(42,245,152,.1); border: 1px solid rgba(42,245,152,.22);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #2af598;
}
.mc-header__title { font-size: 12px; font-weight: 700; color: #e0eaff; margin: 0; white-space: nowrap; flex-shrink: 0; }
.mc-header__sep   { color: rgba(255,255,255,.15); font-size: 10px; flex-shrink: 0; }
.mc-header__status { font-size: 10px; color: #4a6080; }
/* stats empujan al cerrar a la derecha */
.mc-header .mc-close { margin-left: auto; flex-shrink: 0; }

/* Estadísticas del header */
.mc-stat {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 9px; font-weight: 700; letter-spacing: .2px;
  padding: 1px 6px; border-radius: 20px; white-space: nowrap;
}
.mc-stat--total  { background: rgba(100,140,255,.1); border: 1px solid rgba(100,140,255,.18); color: #7ca8ff; }
.mc-stat--custom { background: rgba(123,79,255,.1);  border: 1px solid rgba(123,79,255,.22);  color: #c0a0ff; }
.mc-stat--random { background: rgba(0,158,253,.09); border: 1px solid rgba(0,158,253,.2);   color: #7cd4ff; }
.mc-stat i { font-size: 7px; }

/* ── Cerrar ── */
.mc-close {
  width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.04); color: rgba(255,255,255,.4);
  font-size: 10px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s, border-color .2s;
}
.mc-close:hover { background: rgba(255,77,109,.15); color: #ff6e8a; border-color: rgba(255,77,109,.3); }

/* ── Estados vacío / error / loading ── */
.mc-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12px; padding: 44px 20px;
  font-size: 12px; color: #4a6080; text-align: center;
}
.mc-state--error { color: #ff7090; }
.mc-state__icon  { font-size: 32px; opacity: .25; }
.mc-retry {
  padding: 7px 18px; border-radius: 8px; font-size: 10px; font-weight: 700; letter-spacing: .5px;
  cursor: pointer; background: rgba(255,77,109,.12); border: 1px solid rgba(255,77,109,.28); color: #ff7090;
  display: inline-flex; align-items: center; gap: 6px; transition: background .2s;
}
.mc-retry:hover { background: rgba(255,77,109,.22); }
.mc-spinner {
  width: 26px; height: 26px; border-radius: 50%;
  border: 2px solid rgba(100,140,255,.15); border-top-color: #2af598;
  animation: mcSpin 1s linear infinite;
}
@keyframes mcSpin { to { transform: rotate(360deg); } }

/* ── Scroll contenedor ── */
.mc-scroll {
  overflow-y: auto; flex: 1;
  padding: 8px;
}
.mc-scroll::-webkit-scrollbar { width: 3px; }
.mc-scroll::-webkit-scrollbar-track { background: transparent; }
.mc-scroll::-webkit-scrollbar-thumb { background: rgba(100,140,255,.12); border-radius: 2px; }

/* ── Layout en rejilla 2 columnas ── */
.mc-grid-layout {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
}

/* ── Cartón individual ── */
.mc-card {
  background: rgba(255,255,255,.022); border-radius: 8px; overflow: hidden;
  border: 1px solid rgba(100,140,255,.12);
  transition: border-color .2s;
  display: flex; flex-direction: column;
}
.mc-card--custom { border-color: rgba(123,79,255,.22); }
.mc-card--random  { border-color: rgba(0,158,253,.18); }

/* Cabecera del cartón */
.mc-card__head {
  display: flex; align-items: center; justify-content: space-between; gap: 4px;
  padding: 4px 7px;
  background: rgba(255,255,255,.03); border-bottom: 1px solid rgba(255,255,255,.05);
}
.mc-card__num {
  font-family: 'Anton', sans-serif; font-size: 10px; color: #2af598;
}
.mc-card__type-badge {
  font-size: 7px; font-weight: 700; letter-spacing: .2px;
  padding: 1px 5px; border-radius: 5px;
  display: inline-flex; align-items: center; gap: 3px;
}
.mc-card__type-badge i { font-size: 6px; }
.mc-card__type-badge--custom {
  background: rgba(123,79,255,.14); border: 1px solid rgba(123,79,255,.28); color: #c0a0ff;
}
.mc-card__type-badge--random {
  background: rgba(0,158,253,.12); border: 1px solid rgba(0,158,253,.25); color: #7cd4ff;
}

/* Meta: serial + fecha — una sola línea compacta */
.mc-card__meta {
  display: flex; align-items: center; justify-content: space-between; gap: 4px;
  padding: 2px 7px 3px;
  border-bottom: 1px solid rgba(255,255,255,.04);
}
.mc-card__serial, .mc-card__date {
  display: inline-flex; align-items: center; gap: 2px;
  font-size: 7px; color: #384f6a; letter-spacing: .1px;
}
.mc-card__serial i, .mc-card__date i { font-size: 6px; opacity: .45; }

/* ── Grid BINGO: ocupa todo el ancho, altura controlada ── */
.mc-bingo {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 2px;
  padding: 5px;
}
.mc-bingo__letter {
  display: flex; align-items: center; justify-content: center;
  height: 22px;
  font-family: 'Anton', sans-serif; font-size: 10px; letter-spacing: .6px;
  color: #2af598;
}
.mc-bingo__cell {
  display: flex; align-items: center; justify-content: center;
  /* cuadrado: el ancho viene del grid 1fr, la altura lo iguala */
  aspect-ratio: 1;
  border-radius: 3px;
  background: rgba(255,255,255,.045); border: 1px solid rgba(255,255,255,.07);
  font-size: 9px; font-weight: 700; color: #b8ccec;
}
.mc-bingo__cell--free {
  background: rgba(42,245,152,.13); border-color: rgba(42,245,152,.3);
  color: #2af598;
}
.mc-bingo__cell--free i { font-size: 7px; }

/* ── Transición ── */
.mc-fade-enter-active, .mc-fade-leave-active {
  transition: opacity .2s ease, transform .25s cubic-bezier(.22,1,.36,1);
}
.mc-fade-enter-from, .mc-fade-leave-to { opacity: 0; }
.mc-fade-enter-from .mc-modal, .mc-fade-leave-to .mc-modal {
  transform: scale(.96) translateY(10px);
}

/* ── Responsivo ── */
@media (max-width: 440px) {
  .mc-overlay { padding: 6px; }
  .mc-modal { max-height: 93vh; border-radius: 12px; }
  .mc-grid-layout { grid-template-columns: 1fr; }
}
</style>
