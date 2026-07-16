<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { shortSerial } from '@js/utils/bingoUtils'

interface TopCard {
  id:      string
  serial:  string
  missing: number
}

interface Props {
  /** Hasta 3 cartones activos más cerca de completar (serial + fichas faltantes). */
  topCards?: TopCard[]
  isFinished: boolean
}

const props = withDefaults(defineProps<Props>(), {
  topCards: () => [],
})

const emit = defineEmits<{
  focusCard: [id: string]
}>()

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
</template>

<style scoped>
/* ═══════════════════════════════════════════
   MIS CARTONES — chips + popover
═══════════════════════════════════════════ */
.gh-spacer { flex: 1; }

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

/* Feedback táctil tipo app nativa — el :hover no existe en touch, así que el
   :active es la única señal de "toqué esto" en móvil. */
.gh-chip:active { transform: scale(0.94); }

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

/* Responsive */
@media (max-width: 560px) {
  /* Cartones cercanos: baja a fila propia con scroll horizontal en mobile */
  .gh-missing { flex-basis: 100%; order: 3; margin-top: 8px; overflow-x: auto; overflow-y: visible; }
  .gh-chip { padding: 4px 8px 4px 6px; }
  .gh-missing__label { display: none; }
}
</style>
