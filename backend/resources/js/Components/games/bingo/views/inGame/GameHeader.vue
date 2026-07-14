<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { shortSerial } from '@js/utils/bingoUtils'

interface TopCard {
  id:      string
  serial:  string
  missing: number
}

interface Props {
  prizePoolType: string
  currencySym: string
  prizeFormatted: string
  prizeFlash: boolean
  hasTime: boolean
  cd: { time: { days: number; hours: number; minutes: number; seconds: number } } | null
  isFinished: boolean
  livePlayers: number
  totalWinners: number
  playersFlash: boolean
  cardCount: number
  rounds?: number
  round?: number
  bingoPattern?: string
  muted: boolean
  isLocal: boolean
  devResetting: boolean
  /** Hasta 3 cartones activos más cerca de completar (serial + fichas faltantes). */
  topCards?: TopCard[]
}

const props = withDefaults(defineProps<Props>(), {
  rounds: 0,
  round: 1,
  bingoPattern: '',
  topCards: () => [],
})

// Directiva local: cierra el popover al hacer click fuera del componente
const vClickOutside = {
  mounted(el: HTMLElement, binding: { value: () => void }) {
    (el as any)._clickOutside = (e: MouseEvent) => {
      if (!el.contains(e.target as Node)) binding.value()
    }
    document.addEventListener('mousedown', (el as any)._clickOutside)
  },
  unmounted(el: HTMLElement) {
    document.removeEventListener('mousedown', (el as any)._clickOutside)
  },
}

const emit = defineEmits<{
  toggleMute: []
  openPlayers: []
  devReset: []
  focusCard: [id: string]
}>()

function pad(n: number) { return String(n).padStart(2, '0') }

const countdownUrgency = computed(() => {
  if (!props.cd) return 'normal'
  const { days, hours, minutes, seconds } = props.cd.time
  const totalSeconds = days * 86400 + hours * 3600 + minutes * 60 + seconds
  if (totalSeconds <= 60)  return 'urgent'
  if (totalSeconds <= 300) return 'warning'
  return 'normal'
})

// Fix #14: guard contra datos inconsistentes del backend (round > rounds)
const safeRound = computed(() =>
  props.rounds ? Math.min(props.round ?? 1, props.rounds) : (props.round ?? 1)
)

function missingClass(missing: number): string {
  if (missing < 0) return ''
  if (missing === 0) return 'gh-chip--bingo'
  if (missing <= 2)  return 'gh-chip--hot'
  if (missing <= 5)  return 'gh-chip--warn'
  return ''
}

// Popover: id del chip abierto + posición calculada via getBoundingClientRect
// Se usa Teleport to="body" para escapar del overflow:hidden del .gh-root.
const openPopover  = ref<string | null>(null)
const popoverPos   = ref({ top: 0, left: 0, arrowLeft: 0 })
const chipRefs     = ref<Record<string, HTMLElement>>({})

function setChipRef(id: string, el: HTMLElement | null) {
  if (el) chipRefs.value[id] = el
  else delete chipRefs.value[id]
}

function togglePopover(id: string) {
  if (openPopover.value === id) { openPopover.value = null; return }
  const btn = chipRefs.value[id]
  if (!btn) return
  const r = btn.getBoundingClientRect()
  const popW = 220
  // Centrado bajo el chip; clampear para no salirse del viewport
  let left = r.left + r.width / 2 - popW / 2
  const margin = 8
  left = Math.max(margin, Math.min(left, window.innerWidth - popW - margin))
  const arrowLeft = r.left + r.width / 2 - left
  popoverPos.value = { top: r.bottom + window.scrollY + 10, left, arrowLeft }
  openPopover.value = id
}
function closePopover() { openPopover.value = null }

// Recalcular posición al hacer scroll/resize mientras el popover está abierto
function onScrollResize() {
  if (!openPopover.value) return
  const btn = chipRefs.value[openPopover.value]
  if (!btn) return
  const r = btn.getBoundingClientRect()
  const popW = 220; const margin = 8
  let left = r.left + r.width / 2 - popW / 2
  left = Math.max(margin, Math.min(left, window.innerWidth - popW - margin))
  popoverPos.value = { top: r.bottom + window.scrollY + 10, left, arrowLeft: r.left + r.width / 2 - left }
}
onMounted(() => {
  window.addEventListener('scroll', onScrollResize, { passive: true })
  window.addEventListener('resize', onScrollResize, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('scroll', onScrollResize)
  window.removeEventListener('resize', onScrollResize)
})

</script>

<template>
  <div class="gh-root">
    <!-- Accent line top -->
    <div class="gh-accent" />

    <!-- ══ Main row ══ -->
    <div class="gh-main">

      <!-- Prize -->
      <div class="gh-prize" :class="{ 'gh-prize--flash': prizeFlash }">
        <span class="gh-prize__type">
          <i class="fas fa-trophy" />
          {{ prizePoolType }}
        </span>
        <div class="gh-prize__amount">
          <span class="gh-prize__sym">{{ currencySym }}</span>
          <span class="gh-prize__val">{{ prizeFormatted }}</span>
        </div>
      </div>

      <div class="gh-divider" />

      <!-- Status badge -->
      <div v-if="isFinished" class="gh-badge gh-badge--done">
        <i class="fas fa-flag-checkered" /> FINALIZADA
      </div>
      <div v-else-if="hasTime && cd" class="gh-countdown" :class="`gh-countdown--${countdownUrgency}`">
        <span class="gh-countdown__label"><i class="fas fa-clock" /> Inicia en</span>
        <div class="gh-timer" :class="`gh-timer--${countdownUrgency}`">
          <template v-if="cd.time.days > 0">
            <div class="gh-tu"><b>{{ cd.time.days }}</b><small>D</small></div>
            <span class="gh-tsep">:</span>
          </template>
          <template v-if="cd.time.hours > 0">
            <div class="gh-tu"><b>{{ cd.time.hours }}</b><small>H</small></div>
            <span class="gh-tsep">:</span>
          </template>
          <div class="gh-tu"><b>{{ pad(cd.time.minutes) }}</b><small>M</small></div>
          <span class="gh-tsep">:</span>
          <div class="gh-tu"><b>{{ pad(cd.time.seconds) }}</b><small>S</small></div>
        </div>
      </div>
      <div v-else class="gh-badge gh-badge--live">
        <span class="gh-badge__dot" /> EN VIVO
      </div>

      <!-- Mis cartones más cerca de completar (hasta 3) -->
      <div v-if="topCards.length > 0 && !isFinished" class="gh-missing" v-click-outside="closePopover">
        <span class="gh-missing__label"><i class="fas fa-crosshairs" /> MIS CARTONES</span>
        <div
          v-for="tc in topCards"
          :key="tc.id"
          class="gh-chip-wrap"
          :class="{ 'gh-chip-wrap--open': openPopover === tc.id }"
        >
          <button
            :ref="(el) => setChipRef(tc.id, el as HTMLElement | null)"
            class="gh-chip"
            :class="missingClass(tc.missing)"
            @click="togglePopover(tc.id)"
          >
            <span class="gh-chip__dot" :class="missingClass(tc.missing)" />
            <span class="gh-chip__serial">{{ shortSerial(tc.serial) }}</span>
            <span class="gh-chip__badge" :class="missingClass(tc.missing)">
              <template v-if="tc.missing === 0">¡BINGO!</template>
              <template v-else>{{ tc.missing }}</template>
            </span>
            <i class="fas fa-chevron-down gh-chip__arrow" />
          </button>
        </div>
      </div>
      <div v-else class="gh-spacer" />

      <!-- Popover en Teleport: escapa del overflow:hidden del .gh-root -->
      <Teleport to="body">
        <template v-for="tc in topCards" :key="`pop-${tc.id}`">
          <Transition name="gh-pop">
            <div
              v-if="openPopover === tc.id"
              class="gh-popover"
              role="tooltip"
              :style="{ top: popoverPos.top + 'px', left: popoverPos.left + 'px', '--arrow-left': popoverPos.arrowLeft + 'px' }"
            >
              <div class="gh-popover__arrow" />
              <div class="gh-popover__inner">
                <div class="gh-popover__row gh-popover__row--serial">
                  <i class="fas fa-ticket-alt" />
                  <span class="gh-popover__serial">{{ tc.serial }}</span>
                </div>
                <div class="gh-popover__divider" />
                <div class="gh-popover__row gh-popover__row--status" :class="missingClass(tc.missing)">
                  <template v-if="tc.missing === 0">
                    <i class="fas fa-trophy" />
                    <span>¡BINGO completo!</span>
                  </template>
                  <template v-else-if="tc.missing <= 2">
                    <i class="fas fa-fire" />
                    <span>¡Faltan solo <b>{{ tc.missing }}</b> ficha{{ tc.missing === 1 ? '' : 's' }}!</span>
                  </template>
                  <template v-else-if="tc.missing <= 5">
                    <i class="fas fa-bolt" />
                    <span>Faltan <b>{{ tc.missing }}</b> fichas</span>
                  </template>
                  <template v-else>
                    <i class="fas fa-hourglass-half" />
                    <span>Faltan <b>{{ tc.missing }}</b> fichas</span>
                  </template>
                </div>
                <button class="gh-popover__cta" @click="emit('focusCard', tc.id); closePopover()">
                  <i class="fas fa-search" /> Ver cartón
                </button>
              </div>
            </div>
          </Transition>
        </template>
      </Teleport>

      <!-- Actions -->
      <div class="gh-actions">
        <Link v-if="isFinished" href="/bingo/game-rooms" class="gh-btn gh-btn--rooms">
          <i class="fas fa-door-open" /> Volver
        </Link>
        <button
          class="gh-btn gh-btn--icon"
          :class="{ 'gh-btn--muted': muted }"
          :title="muted ? 'Activar sonido' : 'Silenciar'"
          :aria-label="muted ? 'Activar sonido' : 'Silenciar'"
          :aria-pressed="muted"
          @click="emit('toggleMute')"
        >
          <i :class="muted ? 'fas fa-volume-mute' : 'fas fa-volume-up'" />
        </button>
      </div>

    </div>

    <!-- ── separator ── -->
    <div class="gh-sep" />

    <!-- ══ Stats row ══ -->
    <div class="gh-stats">

      <!-- Players -->
      <button class="gh-stat gh-stat--btn" :class="{ 'gh-stat--flash': playersFlash }" @click="emit('openPlayers')" title="Ver jugadores y ganadores">
        <div class="gh-stat__icon-wrap">
          <i class="fas fa-users" />
          <i v-if="totalWinners > 0" class="fas fa-trophy gh-stat__badge-icon" />
        </div>
        <div class="gh-stat__body">
          <span class="gh-stat__val">
            {{ livePlayers }}
            <em v-if="totalWinners > 0" class="gh-stat__sub-win">· {{ totalWinners }} <i class="fas fa-trophy" /></em>
          </span>
          <span class="gh-stat__lbl">jugadores</span>
        </div>
      </button>

      <div class="gh-stat-sep" />

      <!-- Cards -->
      <div class="gh-stat">
        <i class="fas fa-ticket-alt gh-stat__solo-icon" />
        <div class="gh-stat__body">
          <span class="gh-stat__val">{{ cardCount }}</span>
          <span class="gh-stat__lbl">cartone{{ cardCount === 1 ? '' : 's' }}</span>
        </div>
      </div>

      <!-- Rounds -->
      <template v-if="rounds">
        <div class="gh-stat-sep" />
        <div class="gh-stat">
          <i class="fas fa-sync-alt gh-stat__solo-icon" />
          <div class="gh-stat__body">
            <span class="gh-stat__val">{{ safeRound }}<em class="gh-stat__sub">/{{ rounds }}</em></span>
            <span class="gh-stat__lbl">ronda</span>
          </div>
        </div>
      </template>

      <!-- Pattern -->
      <template v-if="bingoPattern">
        <div class="gh-stat-sep" />
        <div class="gh-stat">
          <i class="fas fa-th gh-stat__solo-icon" />
          <div class="gh-stat__body">
            <span class="gh-stat__val gh-stat__val--sm">{{ bingoPattern }}</span>
            <span class="gh-stat__lbl">patrón</span>
          </div>
        </div>
      </template>

    </div>

    <!-- ══ DEV ribbon ══ -->
    <div v-if="isLocal" class="gh-dev">
      <span class="gh-dev__label"><i class="fas fa-code" /> DEV</span>
      <button class="gh-dev__btn" :disabled="devResetting" @click="emit('devReset')">
        <span v-if="devResetting" class="gh-dev__spin" />
        <i v-else class="fas fa-play" />
        {{ devResetting ? 'Iniciando…' : 'Iniciar sorteo de prueba' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   ROOT
═══════════════════════════════════════════ */
.gh-root {
  display: flex; flex-direction: column;
  background: linear-gradient(160deg, rgba(10,16,44,0.97) 0%, rgba(5,9,28,0.97) 100%);
  border: 1px solid rgba(80,120,255,0.14);
  border-radius: 16px; overflow: hidden;
  backdrop-filter: blur(20px);
  box-shadow: 0 6px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
}

/* Accent line */
.gh-accent {
  height: 2px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(80,140,255,0.55) 18%,
    rgba(42,245,152,0.75) 50%,
    rgba(123,79,255,0.55) 82%,
    transparent 100%);
}

/* ═══════════════════════════════════════════
   MAIN ROW
═══════════════════════════════════════════ */
.gh-main {
  display: flex; align-items: center;
  padding: 10px 18px; gap: 0; min-height: 60px;
}

/* Prize */
.gh-prize { display: flex; flex-direction: column; gap: 0; flex-shrink: 0; }
.gh-prize--flash { animation: prize-pop 0.6s ease; }
@keyframes prize-pop { 0%,100% { transform: scale(1); } 45% { transform: scale(1.06); } }

.gh-prize__type {
  display: flex; align-items: center; gap: 4px;
  font-size: 8px; font-weight: 700; color: #3a5890;
  text-transform: uppercase; letter-spacing: 1.2px;
}
.gh-prize__type i { color: #f5c518; font-size: 8px; }
.gh-prize__amount { display: flex; align-items: baseline; gap: 3px; }
.gh-prize__sym { font-size: 15px; font-weight: 700; color: #ffc840; }
.gh-prize__val {
  font-family: 'Anton', sans-serif; font-size: 44px; line-height: 1;
  background: linear-gradient(130deg, #ffe566 0%, #ffb800 55%, #ff8800 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 2px 10px rgba(255,180,0,0.35)); letter-spacing: -1px;
}

/* Divider */
.gh-divider {
  width: 1px; height: 36px; flex-shrink: 0; margin: 0 16px;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.18), transparent);
}
.gh-spacer { flex: 1; }

/* ═══════════════════════════════════════════
   MIS CARTONES — chips + popover
═══════════════════════════════════════════ */
.gh-missing {
  flex: 1; display: flex; align-items: center; gap: 7px;
  padding: 0 6px; overflow: visible; min-width: 0;
}
.gh-missing__label {
  font-size: 8px; font-weight: 800; color: #2a3860;
  text-transform: uppercase; letter-spacing: 0.8px;
  display: flex; align-items: center; gap: 4px;
  white-space: nowrap; flex-shrink: 0;
}
.gh-missing__label i { font-size: 8px; color: #3a5090; }

/* Wrapper para posicionar el popover relativo al chip */
.gh-chip-wrap { position: relative; flex-shrink: 0; }

/* ── Chip button ── */
.gh-chip {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 10px 5px 7px; border-radius: 22px;
  background: rgba(80,110,220,0.07);
  border: 1px solid rgba(80,110,220,0.2);
  cursor: pointer; transition: all 0.18s ease;
  position: relative; overflow: hidden;
}
.gh-chip::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(110deg, transparent 30%, rgba(255,255,255,0.06) 50%, transparent 70%);
  transform: translateX(-100%); pointer-events: none;
  transition: transform 0s;
}
.gh-chip:hover::before { transform: translateX(100%); transition: transform 0.4s ease; }
.gh-chip:hover {
  background: rgba(80,110,220,0.13);
  border-color: rgba(100,140,255,0.38);
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(80,100,255,0.18);
}
.gh-chip-wrap--open .gh-chip {
  background: rgba(80,110,220,0.14);
  border-color: rgba(100,140,255,0.45);
  box-shadow: 0 0 0 2px rgba(80,120,255,0.15);
}

/* Dot indicator */
.gh-chip__dot {
  width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
  background: rgba(130,160,255,0.5);
}
.gh-chip__dot.gh-chip--warn  { background: #ffb060; box-shadow: 0 0 5px rgba(255,160,0,0.5); }
.gh-chip__dot.gh-chip--hot   { background: #ff7070; box-shadow: 0 0 6px rgba(255,60,60,0.6); animation: dot-hot 1s ease-in-out infinite alternate; }
.gh-chip__dot.gh-chip--bingo { background: #2af598; box-shadow: 0 0 8px rgba(42,245,152,0.7); animation: dot-bingo 0.8s ease-in-out infinite alternate; }
@keyframes dot-hot   { from { opacity: 0.6; } to { opacity: 1; transform: scale(1.3); } }
@keyframes dot-bingo { from { opacity: 0.7; } to { opacity: 1; transform: scale(1.4); } }

/* Serial */
.gh-chip__serial {
  font-size: 9.5px; font-weight: 700; color: #7a90c8;
  font-family: 'Courier New', monospace; letter-spacing: 0.3px;
  max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Badge numérico */
.gh-chip__badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 9px; font-size: 10px; font-weight: 900;
  background: rgba(100,140,255,0.12); color: #a0b8ff;
  font-family: 'Courier New', monospace;
  border: 1px solid rgba(100,140,255,0.2);
  line-height: 1;
}
.gh-chip__badge.gh-chip--warn  { background: rgba(255,160,0,0.16); color: #ffb84a; border-color: rgba(255,160,0,0.3); }
.gh-chip__badge.gh-chip--hot   { background: rgba(255,60,60,0.16);  color: #ff8080; border-color: rgba(255,60,60,0.3); }
.gh-chip__badge.gh-chip--bingo { background: rgba(42,245,152,0.16); color: #2af598; border-color: rgba(42,245,152,0.3); font-size: 8px; padding: 0 6px; }

/* Chevron */
.gh-chip__arrow {
  font-size: 7px; color: #4a5e90;
  transition: transform 0.2s ease; margin-left: 1px;
}
.gh-chip-wrap--open .gh-chip__arrow { transform: rotate(180deg); }

/* Estado del chip */
.gh-chip--warn  { background: rgba(255,160,0,0.07);  border-color: rgba(255,160,0,0.28); }
.gh-chip--hot   { background: rgba(255,50,50,0.08);   border-color: rgba(255,60,60,0.32); animation: gh-hot-border 1.2s ease-in-out infinite alternate; }
.gh-chip--bingo { background: rgba(42,245,152,0.08);  border-color: rgba(42,245,152,0.38); animation: gh-bingo-border 0.9s ease-in-out infinite alternate; }
.gh-chip--warn .gh-chip__serial  { color: #c89040; }
.gh-chip--hot  .gh-chip__serial  { color: #c07070; }
.gh-chip--bingo .gh-chip__serial { color: #30b870; }
@keyframes gh-hot-border   { from { box-shadow: 0 0 0 0 rgba(255,60,60,0.0); } to { box-shadow: 0 0 8px 1px rgba(255,60,60,0.25); } }
@keyframes gh-bingo-border { from { box-shadow: 0 0 0 0 rgba(42,245,152,0.0); } to { box-shadow: 0 0 10px 2px rgba(42,245,152,0.3); } }

/* ── Popover (Teleport to body — posición calculada con JS) ── */
.gh-popover {
  position: absolute; /* absolute dentro de body = fixed visual */
  width: 220px; z-index: 9500;
  filter: drop-shadow(0 8px 28px rgba(0,0,0,0.6));
  pointer-events: auto;
}
.gh-popover__arrow {
  position: absolute; top: -6px; left: var(--arrow-left, 50%);
  transform: translateX(-50%);
  width: 12px; height: 7px; overflow: hidden;
}
.gh-popover__arrow::before {
  content: ''; position: absolute; top: 1px; left: 50%;
  width: 10px; height: 10px;
  background: rgba(12,20,56,0.98);
  border: 1px solid rgba(80,120,255,0.2);
  border-radius: 2px;
  transform: translateX(-50%) rotate(45deg);
  transform-origin: center;
}
.gh-popover__inner {
  background: linear-gradient(155deg, rgba(12,20,56,0.98) 0%, rgba(6,10,32,0.98) 100%);
  border: 1px solid rgba(80,120,255,0.22);
  border-radius: 14px; overflow: hidden;
  backdrop-filter: blur(20px);
}

.gh-popover__row {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px; font-size: 11px;
}
.gh-popover__row i { font-size: 10px; flex-shrink: 0; }
.gh-popover__row--serial { color: #4a6aaa; }
.gh-popover__row--serial i { color: #3a5090; }
.gh-popover__serial {
  font-family: 'Courier New', monospace; font-size: 9.5px;
  color: #7a9ad8; letter-spacing: 0.3px;
  word-break: break-all; line-height: 1.3;
}

.gh-popover__row--status { font-weight: 700; color: #8aa8e8; gap: 6px; }
.gh-popover__row--status i { font-size: 11px; }
.gh-popover__row--status b { color: #c8d8ff; }
.gh-popover__row--status.gh-chip--warn  { color: #ffb060; }
.gh-popover__row--status.gh-chip--warn b  { color: #ffd090; }
.gh-popover__row--status.gh-chip--warn i  { color: #ffb060; }
.gh-popover__row--status.gh-chip--hot   { color: #ff8888; }
.gh-popover__row--status.gh-chip--hot b   { color: #ffbbbb; }
.gh-popover__row--status.gh-chip--hot i   { color: #ff7070; }
.gh-popover__row--status.gh-chip--bingo { color: #2af598; }
.gh-popover__row--status.gh-chip--bingo b { color: #70ffcc; }
.gh-popover__row--status.gh-chip--bingo i { color: #2af598; }

.gh-popover__divider { height: 1px; background: rgba(80,120,255,0.1); margin: 0 10px; }

.gh-popover__cta {
  width: 100%; padding: 9px 14px; margin: 0;
  background: rgba(80,120,255,0.08); border: none;
  border-top: 1px solid rgba(80,120,255,0.12);
  color: #7c9dff; font-size: 11px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.15s ease, color 0.15s ease;
}
.gh-popover__cta:hover { background: rgba(80,120,255,0.16); color: #b0c8ff; }
.gh-popover__cta i { font-size: 9px; }

/* Transition popover */
.gh-pop-enter-active { transition: opacity 0.18s ease, transform 0.18s cubic-bezier(0.34,1.56,0.64,1); }
.gh-pop-leave-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.gh-pop-enter-from  { opacity: 0; transform: translateY(-6px) scale(0.96); }
.gh-pop-leave-to    { opacity: 0; transform: translateY(-3px) scale(0.98); }

/* Status badges */
.gh-badge {
  display: flex; align-items: center; gap: 7px;
  padding: 6px 16px; border-radius: 30px;
  font-size: 11px; font-weight: 900; letter-spacing: 2px;
  flex-shrink: 0;
}
.gh-badge--live {
  background: rgba(42,245,152,0.08); border: 1px solid rgba(42,245,152,0.24);
  color: #2af598; box-shadow: 0 0 18px rgba(42,245,152,0.07);
}
.gh-badge__dot {
  width: 7px; height: 7px; border-radius: 50%; background: #2af598;
  animation: dot-pulse 1.4s ease-in-out infinite;
  box-shadow: 0 0 6px rgba(42,245,152,0.8);
}
@keyframes dot-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(42,245,152,0.7); } 50% { box-shadow: 0 0 0 6px rgba(42,245,152,0); } }
.gh-badge--done {
  background: rgba(100,120,160,0.09); border: 1px solid rgba(100,120,160,0.22);
  color: #7090b8; letter-spacing: 1.5px;
}

/* Countdown */
.gh-countdown { display: flex; flex-direction: column; gap: 2px; flex-shrink: 0; }
.gh-countdown__label {
  font-size: 8.5px; font-weight: 700; color: #3a5890;
  text-transform: uppercase; letter-spacing: 0.9px;
  display: flex; align-items: center; gap: 3px;
}
.gh-timer { display: flex; align-items: center; gap: 2px; }
.gh-tu {
  display: flex; flex-direction: column; align-items: center;
  background: rgba(80,120,255,0.07); border: 1px solid rgba(80,120,255,0.13);
  border-radius: 6px; padding: 2px 5px; min-width: 28px;
}
.gh-tu b  { font-size: 15px; font-weight: 800; color: #ddeaff; line-height: 1.1; font-family: 'Courier New', monospace; }
.gh-tu small { font-size: 6.5px; color: #4a6aaa; text-transform: uppercase; letter-spacing: 0.3px; }
.gh-tsep { color: #2a3860; font-size: 16px; font-weight: 300; margin: 0 1px; padding-bottom: 8px; }

/* ── Countdown urgency states ── */
/* warning: <5 min → naranja */
.gh-countdown--warning .gh-countdown__label { color: #c07820; }
.gh-countdown--warning .gh-countdown__label i { color: #e09030; }
.gh-timer--warning .gh-tu {
  background: rgba(255,160,0,0.09);
  border-color: rgba(255,160,0,0.24);
}
.gh-timer--warning .gh-tu b { color: #ffcf70; }
.gh-timer--warning .gh-tu small { color: #a07030; }
.gh-timer--warning .gh-tsep { color: #7a5010; }

/* urgent: ≤60 s → rojo pulsante */
.gh-countdown--urgent .gh-countdown__label { color: #b03030; }
.gh-countdown--urgent .gh-countdown__label i { color: #e05050; }
.gh-timer--urgent .gh-tu {
  background: rgba(255,60,60,0.1);
  border-color: rgba(255,80,80,0.32);
  animation: cd-urgent-border 0.7s ease-in-out infinite alternate;
}
.gh-timer--urgent .gh-tu b {
  color: #ff8080;
  animation: cd-urgent-shake 0.5s ease-in-out infinite;
}
.gh-timer--urgent .gh-tu small { color: #903030; }
.gh-timer--urgent .gh-tsep { color: #602020; animation: cd-urgent-sep 0.7s ease-in-out infinite alternate; }
@keyframes cd-urgent-border {
  from { box-shadow: 0 0 0 0 rgba(255,60,60,0.0); border-color: rgba(255,80,80,0.25); }
  to   { box-shadow: 0 0 10px 1px rgba(255,60,60,0.3); border-color: rgba(255,80,80,0.55); }
}
@keyframes cd-urgent-shake {
  0%,100% { transform: translateX(0); }
  25%      { transform: translateX(-1px); }
  75%      { transform: translateX(1px); }
}
@keyframes cd-urgent-sep {
  from { color: #602020; }
  to   { color: #c04040; }
}

/* Actions */
.gh-actions { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.gh-btn {
  display: inline-flex; align-items: center; gap: 5px;
  border-radius: 20px; font-size: 11px; font-weight: 700;
  cursor: pointer; transition: all 0.16s ease; border: 1px solid; text-decoration: none;
}
.gh-btn--rooms {
  padding: 6px 14px;
  background: rgba(100,140,255,0.09); border-color: rgba(100,140,255,0.22); color: #7c9dff;
}
.gh-btn--rooms:hover { background: rgba(100,140,255,0.18); color: #b0c8ff; box-shadow: 0 3px 12px rgba(100,140,255,0.18); }
.gh-btn--icon {
  width: 34px; height: 34px; padding: 0; justify-content: center; border-radius: 50%;
  background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.09); color: #6080b0; font-size: 13px;
}
.gh-btn--icon:hover { background: rgba(255,255,255,0.09); color: #a0c0ff; }
.gh-btn--muted { color: #2a3460; border-color: rgba(255,255,255,0.05); }
/* Feedback táctil tipo app nativa — el :hover no existe en touch, así que el
   :active es la única señal de "toqué esto" en móvil. */
.gh-btn:active,
.gh-chip:active,
.gh-stat--btn:active { transform: scale(0.94); }

/* ═══════════════════════════════════════════
   SEPARATOR
═══════════════════════════════════════════ */
.gh-sep { height: 1px; background: rgba(80,120,255,0.08); }

/* ═══════════════════════════════════════════
   STATS ROW
═══════════════════════════════════════════ */
.gh-stats {
  display: flex; align-items: stretch;
  padding: 0 18px; background: rgba(0,0,0,0.1);
  flex-wrap: wrap; min-height: 40px;
}

.gh-stat {
  display: flex; align-items: center; gap: 7px;
  padding: 6px 12px; flex: 1; min-width: 0; position: relative;
}
.gh-stat--btn {
  cursor: pointer; border-radius: 8px;
  transition: background 0.18s ease; background: transparent; border: none; text-align: left;
}
.gh-stat--btn:hover { background: rgba(100,140,255,0.07); }

/* Icon with optional badge overlay */
.gh-stat__icon-wrap { position: relative; width: 22px; height: 22px; flex-shrink: 0; }
.gh-stat__icon-wrap > i:first-child {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}
.gh-stat__badge-icon {
  position: absolute; bottom: -5px; left: 10px;
  width: 13px !important; height: 13px !important;
  font-size: 6.5px !important; color: #ffd700 !important;
  background: rgba(255,200,0,0.16) !important; border-color: rgba(255,200,0,0.3) !important;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
}
.gh-stat__solo-icon {
  width: 22px; height: 22px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #7c9dff;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.15); border-radius: 50%;
}

.gh-stat__body { display: flex; flex-direction: column; gap: 0; min-width: 0; }
.gh-stat__val {
  font-size: 13px; font-weight: 800; color: #c8d8ff; line-height: 1.1;
  font-family: 'Courier New', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  display: flex; align-items: center; gap: 4px;
}
.gh-stat__val--sm { font-size: 11px; font-family: inherit; }
.gh-stat__sub     { font-size: 9px; color: #2a3860; font-weight: 500; font-style: normal; }
.gh-stat__sub-win { font-size: 11px; color: #ffd700; font-family: inherit; font-style: normal; }
.gh-stat__sub-win i { font-size: 9px; }
.gh-stat__lbl { font-size: 7px; font-weight: 700; color: #2a3860; text-transform: uppercase; letter-spacing: 0.5px; }

/* Flash animation on stat */
.gh-stat--flash .gh-stat__icon-wrap > i:first-child,
.gh-stat--flash .gh-stat__solo-icon { background: rgba(42,245,152,0.14); color: #2af598; }
.gh-stat--flash .gh-stat__val { animation: stat-pop 0.5s ease; }
@keyframes stat-pop { 0% { color: #2af598; transform: scale(1); } 40% { transform: scale(1.14); } 100% { color: #c8d8ff; transform: scale(1); } }

/* Stat separators */
.gh-stat-sep {
  width: 1px; align-self: stretch; margin: 6px 0;
  background: linear-gradient(180deg, transparent, rgba(80,120,255,0.12), transparent);
  flex-shrink: 0;
}

/* "Mis fichas faltantes" — mismos umbrales de color que el badge que reemplaza */
.gh-stat--warn .gh-stat__solo-icon { background: rgba(255,160,0,0.14); color: #ffb060; }
.gh-stat--warn .gh-stat__val       { color: #ffb060; }
.gh-stat--hot .gh-stat__solo-icon  { background: rgba(255,50,50,0.14); color: #ff8080; animation: gh-stat-pulse 1s ease-in-out infinite alternate; }
.gh-stat--hot .gh-stat__val        { color: #ff8080; }
.gh-stat--bingo .gh-stat__solo-icon { background: rgba(42,245,152,0.16); color: #2af598; animation: gh-stat-pulse-bingo 0.8s ease-in-out infinite alternate; }
.gh-stat--bingo .gh-stat__val       { color: #2af598; }
@keyframes gh-stat-pulse { from { box-shadow: 0 0 0 0 rgba(255,50,50,0.15); } to { box-shadow: 0 0 8px 2px rgba(255,50,50,0.3); } }
@keyframes gh-stat-pulse-bingo { from { box-shadow: 0 0 0 0 rgba(42,245,152,0.2); } to { box-shadow: 0 0 10px 2px rgba(42,245,152,0.4); } }

/* ═══════════════════════════════════════════
   DEV RIBBON
═══════════════════════════════════════════ */
.gh-dev {
  display: flex; align-items: center; gap: 10px;
  padding: 6px 18px 8px;
  border-top: 1px dashed rgba(255,171,0,0.16);
  background: rgba(255,171,0,0.025);
}
.gh-dev__label {
  font-size: 7.5px; font-weight: 800; letter-spacing: 1.2px;
  color: rgba(255,171,0,0.4); text-transform: uppercase;
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.gh-dev__btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px; border-radius: 8px;
  background: rgba(255,171,0,0.09); border: 1px solid rgba(255,171,0,0.26);
  color: #ffd060; font-size: 11px; font-weight: 700; cursor: pointer;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
}
.gh-dev__btn:hover:not(:disabled) { background: rgba(255,171,0,0.18); border-color: rgba(255,171,0,0.48); color: #ffe090; }
.gh-dev__btn:disabled { opacity: 0.45; cursor: not-allowed; }
.gh-dev__btn i { font-size: 9px; }
.gh-dev__spin {
  width: 10px; height: 10px; border-radius: 50%;
  border: 1.5px solid rgba(255,171,0,0.28); border-top-color: #ffd060;
  animation: dev-spin 0.7s linear infinite;
}
@keyframes dev-spin { to { transform: rotate(360deg) } }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 768px) {
  .gh-main { padding: 10px 14px; }
  .gh-stats { padding: 0 14px; }
  .gh-stat { padding: 6px 8px; }
}
@media (max-width: 560px) {
  .gh-main { padding: 10px 12px; min-height: auto; gap: 0; flex-wrap: wrap; }
  .gh-divider { display: none; }
  .gh-prize { flex-direction: row; align-items: center; gap: 5px; }
  .gh-prize__type { display: none; }
  .gh-prize__sym { font-size: 12px; }
  .gh-prize__val { font-size: 28px; }
  .gh-badge { padding: 4px 10px; font-size: 10px; letter-spacing: 1px; }
  .gh-countdown__label { font-size: 7.5px; }
  .gh-tu { min-width: 24px; padding: 1px 4px; }
  .gh-tu b { font-size: 13px; }
  /* Cartones cercanos: baja a fila propia con scroll horizontal en mobile */
  .gh-missing { flex-basis: 100%; order: 3; margin-top: 8px; overflow-x: auto; overflow-y: visible; }
  .gh-chip { padding: 4px 8px 4px 6px; }
  .gh-missing__label { display: none; }
  /* Stats: grid 2-col on mobile */
  .gh-stats { display: grid; grid-template-columns: 1fr 1fr; padding: 0 12px; }
  .gh-stat-sep { display: none; }
  .gh-stat { flex: none; padding: 6px 8px; border-bottom: 1px solid rgba(80,120,255,0.06); }
  .gh-stat:nth-child(odd) { border-right: 1px solid rgba(80,120,255,0.06); }
}
@media (max-width: 380px) {
  .gh-prize__val { font-size: 22px; }
  .gh-main { padding: 8px 10px; }
  .gh-stats { padding: 0 10px; }
  .gh-stat { padding: 5px 6px; }
  .gh-stat__val { font-size: 12px; }
}
</style>
