<template>
  <div class="ctn-root">

    <!-- ── Toolbar ── -->
    <div class="ctn-bar" v-if="cartons.length > 0 || totalDrawn > 0">
      <div class="ctn-bar__left">
        <i class="fas fa-ticket-alt ctn-bar__icon" />
        <span class="ctn-bar__title">CARTONES</span>
        <span class="ctn-bar__count">{{ cartons.length }}</span>
        <span v-if="totalDrawn > 0" class="ctn-bar__drawn">
          <i class="fas fa-shuffle" />{{ totalDrawn }}<em>/{{ totalChips }}</em>
        </span>
        <Transition name="ctn-streak">
          <span v-if="streak >= 2" class="ctn-bar__streak" :key="streak">
            <i class="fas fa-bolt" />{{ streak }}
          </span>
        </Transition>
      </div>

      <div class="ctn-bar__filters">
        <button
          v-for="f in visibleFilters"
          :key="f.value"
          class="ctn-fbtn"
          :class="{
            'ctn-fbtn--active':  activeFilter === f.value,
            'ctn-fbtn--new-hot': f.value === 'hot' && _freshHotVer.value > 0 && _freshHotSet.size > 0 && activeFilter !== 'hot',
          }"
          @click="activeFilter = f.value"
        >
          <i :class="f.icon" />
          {{ f.label }}
          <span class="ctn-fbtn__n">{{ getFilterCount(f.value) }}</span>
        </button>
      </div>

      <div class="ctn-bar__right">
        <!-- Legend dots for BINGO columns -->
        <div v-if="totalDrawn > 0 && variantDef.columns.length <= 5" class="ctn-legend">
          <span
            v-for="col in variantDef.columns"
            :key="col.label"
            class="ctn-legend__dot"
            :class="`ctn-legend__dot--col${col.colorIdx}`"
            :title="`${col.label}: ${col.min}–${col.max}`"
          >{{ col.label }}</span>
        </div>
        <div class="ctn-sizebar">
          <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'sm' }" @click="cardSize = 'sm'" title="Compacto"><i class="fas fa-th" /></button>
          <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'md' }" @click="cardSize = 'md'" title="Normal"><i class="fas fa-th-large" /></button>
        </div>
      </div>
    </div>

    <!-- ── Card grid ── -->
    <TransitionGroup name="ctn-pop" tag="div" class="ctn-grid" :class="`ctn-grid--${cardSize}`">
      <div
        v-for="(card, idx) in filteredCards"
        :key="card.id || idx"
        class="ctn-card"
        :class="{
          'ctn-card--winner':    isWinner(card),
          'ctn-card--hot':       isHot(card) && !isWinner(card),
          'ctn-card--active':    selectedId === card.id,
          'ctn-card--hit-flash': isHit(card) && !isWinner(card),
          'ctn-card--fresh-hot': isFreshHot(card),
        }"
        :style="{ '--i': idx }"
        translate="no"
        @click="selectedId = selectedId === card.id ? null : card.id"
      >
        <!-- Header -->
        <div class="ctn-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
          <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
        </div>

        <!-- Body -->
        <div class="ctn-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
          <template v-for="r in variantDef.numRows" :key="r">
            <div
              v-for="col in variantDef.columns" :key="`${col.label}${r}`"
              class="ctn-cell"
              :class="[`ctn-cell--col${col.colorIdx}`, cellCls(card, col.label, r)]"
            >
              <template v-if="isFreeCell(col, r)">
                <span class="ctn-free">★</span>
              </template>
              <template v-else-if="drawn(card, col.label, r)">
                <div class="ctn-ball" :class="`ctn-ball--col${col.colorIdx}`">
                  <span>{{ num(card, col.label, r) }}</span>
                </div>
              </template>
              <template v-else>
                <span class="ctn-num" :class="{ 'ctn-num--empty': num(card, col.label, r) === null }">
                  {{ num(card, col.label, r) ?? '' }}
                </span>
              </template>
            </div>
          </template>
        </div>

        <!-- Progress strip -->
        <div class="ctn-prog">
          <div class="ctn-prog__fill"
            :class="{ 'ctn-prog__fill--hot': isHot(card), 'ctn-prog__fill--win': isWinner(card) }"
            :style="{ width: `${(hits(card)/variantDef.totalCells)*100}%` }"
          />
        </div>

        <!-- Footer -->
        <div class="ctn-foot">
          <span class="ctn-foot__serial">{{ shortSerial(card.serialNumber) }}</span>
          <span v-if="isWinner(card)" class="ctn-foot__badge ctn-foot__badge--win">
            <i class="fas fa-trophy" /> BINGO
          </span>
          <span v-else-if="isHot(card)" class="ctn-foot__badge ctn-foot__badge--hot">
            <i class="fas fa-fire" /> {{ hits(card) }}<small>/{{ variantDef.totalCells }}</small>
          </span>
          <span v-else-if="hits(card) > 0" class="ctn-foot__hits">
            {{ hits(card) }}<small>/{{ variantDef.totalCells }}</small>
          </span>
          <span v-else class="ctn-foot__hits ctn-foot__hits--zero">
            0<small>/{{ variantDef.totalCells }}</small>
          </span>
        </div>
      </div>
    </TransitionGroup>

    <!-- ── Detail modal ── -->
    <Transition name="ctn-modal">
      <div v-if="selectedCard" class="ctn-modal-bg" @click.self="selectedId = null" @keydown.escape.stop="selectedId = null">
        <div class="ctn-modal">
          <button class="ctn-modal__x" @click="selectedId = null"><i class="fas fa-times" /></button>

          <div class="ctn-modal__header">
            <div v-if="isWinner(selectedCard)" class="ctn-modal__tag ctn-modal__tag--win">🏆 BINGO</div>
            <div v-else-if="isHot(selectedCard)" class="ctn-modal__tag ctn-modal__tag--hot">🔥 CALIENTE</div>
            <div class="ctn-modal__serial">{{ selectedCard.serialNumber }}</div>
            <div v-if="bingoPattern" class="ctn-modal__pattern">
              <i class="fas fa-th-large" /> {{ bingoPattern }}
            </div>
          </div>

          <!-- big card -->
          <div class="ctn-dcard">
            <div class="ctn-dcard__head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
              <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
            </div>
            <div class="ctn-dcard__body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
              <template v-for="r in variantDef.numRows" :key="r">
                <div v-for="col in variantDef.columns" :key="`d${col.label}${r}`" class="ctn-dcell" :class="cellCls(selectedCard, col.label, r)">
                  <template v-if="isFreeCell(col, r)">
                    <span class="ctn-dfree">★</span>
                  </template>
                  <template v-else-if="drawn(selectedCard, col.label, r)">
                    <div class="ctn-ball ctn-dball" :class="`ctn-ball--col${col.colorIdx}`">
                      <span>{{ num(selectedCard, col.label, r) }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <span class="ctn-dnum">{{ num(selectedCard, col.label, r) ?? '' }}</span>
                  </template>
                </div>
              </template>
            </div>
          </div>

          <!-- stats row -->
          <div class="ctn-stats">
            <div class="ctn-stat">
              <span class="ctn-stat__v ctn-stat__v--hit">{{ hits(selectedCard) }}</span>
              <span class="ctn-stat__l">marcados</span>
            </div>
            <div class="ctn-stat">
              <span class="ctn-stat__v">{{ variantDef.totalCells - hits(selectedCard) }}</span>
              <span class="ctn-stat__l">faltan</span>
            </div>
            <div class="ctn-stat">
              <span class="ctn-stat__v ctn-stat__v--pct">{{ pct(selectedCard) }}%</span>
              <span class="ctn-stat__l">completado</span>
            </div>
          </div>

          <!-- Progress bar in modal -->
          <div class="ctn-modal__prog">
            <div class="ctn-modal__prog-fill"
              :class="{ 'ctn-prog__fill--hot': isHot(selectedCard), 'ctn-prog__fill--win': isWinner(selectedCard) }"
              :style="{ width: `${pct(selectedCard)}%` }"
            />
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Spectator / empty states ── -->
    <template v-if="cartons.length === 0">

      <div v-if="totalDrawn > 0" class="ctn-spectator">
        <div class="ctn-spectator__hdr">
          <span class="ctn-spectator__eye"><i class="fas fa-eye" /></span>
          <span class="ctn-spectator__label">Modo espectador</span>
          <span class="ctn-spectator__count">{{ totalDrawn }}<small>/{{ totalChips }}</small></span>
        </div>

        <div class="ctn-ball-grid">
          <template v-for="col in variantDef.columns" :key="col.label">
            <div class="ctn-ball-col">
              <div class="ctn-ball-col__hdr" :class="`ctn-ball-col__hdr--col${col.colorIdx}`">{{ col.label }}</div>
              <div
                v-for="n in colNumbers(col)"
                :key="n"
                class="ctn-ball-cell"
                :class="{
                  'ctn-ball-cell--hit':    drawnSet.has(n),
                  'ctn-ball-cell--latest': n === drawnNumbers?.[drawnNumbers.length - 1],
                  [`ctn-ball-cell--col${col.colorIdx}`]: true,
                }"
              >{{ n }}</div>
            </div>
          </template>
        </div>
      </div>

      <div v-else class="ctn-empty">
        <div class="ctn-empty__icon"><i class="fas fa-eye" /></div>
        <p class="ctn-empty__text">Modo espectador</p>
        <p class="ctn-empty__sub">El sorteo aún no ha comenzado</p>
      </div>
    </template>

  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, onUnmounted } from "vue";
// BingoCard: estructura mínima que el backend serializa en toFrontendFormat()
interface BingoCard {
  id: string
  serialNumber: string
  cardMatrix: Record<string, (number | null)[]>
}
import { useBingoSound } from "@js/composables/games/bingo/useBingoSound";
import { getBingoVariantDef, type BingoVariantDef, type BingoVariantColumn } from "@/js/modules/games/bingo/utils/bingoVariants";

type Filter = "all" | "hot" | "win";

interface Props {
  cartons?:        BingoCard[];
  customerLogo?:   string;
  roomId?:         string;
  drawnNumbers?:   number[];
  totalDrawn?:     number;
  gameVariant?:    string;
  hotThreshold?:   number;
  bingoPattern?:   string;
}

const props = withDefaults(defineProps<Props>(), {
  cartons:        () => [],
  customerLogo:   null,
  roomId:         "",
  drawnNumbers:   () => [],
  totalDrawn:     0,
  gameVariant:    "BINGO_75",
  hotThreshold:   10,
  bingoPattern:   "",
});

const emit = defineEmits<{
  (e: "bingo-detected", cards: BingoCard[]): void
  (e: "card-hit", cardId: string): void
}>()

const variantDef = computed<BingoVariantDef>(() => getBingoVariantDef(props.gameVariant));
const totalChips = computed(() => variantDef.value.totalChips);

const activeFilter = ref<Filter>("all");
// Auto-sm en mobile si hay más de 2 cartones; preferencia persistida en localStorage
const isMobile     = typeof window !== 'undefined' && window.innerWidth <= 600
const _savedSize   = typeof localStorage !== 'undefined' ? (localStorage.getItem('bingo-card-size') as "sm" | "md" | null) : null
const cardSize     = ref<"sm" | "md">(_savedSize ?? (isMobile && (props.cartons?.length ?? 0) > 2 ? "sm" : "md"));
watch(cardSize, v => { if (typeof localStorage !== 'undefined') localStorage.setItem('bingo-card-size', v) })
const selectedId   = ref<string | null>(null);
// Sets internos (no reactivos) + contador de versión para forzar re-render.
// Vue no detecta .add()/.delete() en un ref<Set> — el patrón version-bump
// es la forma correcta: mutar el Set y luego incrementar el contador para
// que los computed/bindings que leen el contador se recalculen.
const _bingoSet    = new Set<string>()
const _hitSet      = new Set<string>()
const _freshHotSet = new Set<string>()
const _bingoVer    = ref(0)
const _hitVer      = ref(0)
const _freshHotVer = ref(0)

const hitTimers      = new Map<string, ReturnType<typeof setTimeout>>()
const freshHotTimers = new Map<string, ReturnType<typeof setTimeout>>()
// Racha: cuántas bolas consecutivas cayeron en al menos un cartón del jugador
const streak       = ref(0)

const { playSelect, playWinner } = useBingoSound();

// computed sobre el prop array — Vue rastrea la dependencia automáticamente,
// sin necesidad de triggerRef ni rutas de actualización manuales.
const drawnSet = computed(() => new Set<number>(props.drawnNumbers));

const selectedCard = computed(() =>
  selectedId.value ? props.cartons.find(c => c.id === selectedId.value) ?? null : null
);

const visibleFilters = computed(() => {
  const base: { value: Filter; label: string; icon: string }[] = [
    { value: "all", label: "Todos", icon: "fas fa-border-all" },
  ];
  if (getFilterCount("hot") > 0) base.push({ value: "hot", label: "Caliente", icon: "fas fa-fire" });
  if (getFilterCount("win") > 0) base.push({ value: "win", label: "Bingo",    icon: "fas fa-trophy" });
  return base;
});

const filteredCards = computed(() => {
  if (activeFilter.value === "win") return props.cartons.filter(c => isWinner(c));
  if (activeFilter.value === "hot") return props.cartons.filter(c => isHot(c) && !isWinner(c));
  return props.cartons;
});

function isFreeCell(col: BingoVariantColumn, r: number): boolean {
  return col.freeRow !== null && col.freeRow === r - 1;
}

function num(card: BingoCard, colLabel: string, r: number): number | null {
  const m = card.cardMatrix as Record<string, (number | null)[]> | null;
  if (!m) return null;
  const val = m[colLabel]?.[r - 1];
  return (val === undefined || val === null) ? null : val;
}

function drawn(card: BingoCard, colLabel: string, r: number): boolean {
  const n = num(card, colLabel, r);
  return n !== null && drawnSet.value.has(n);
}

function cellCls(card: BingoCard, colLabel: string, r: number) {
  const col = variantDef.value.columns.find(c => c.label === colLabel);
  if (col && isFreeCell(col, r)) return "ctn-cell--free";
  if (drawn(card, colLabel, r)) return "ctn-cell--hit";
  return "";
}

function computeHits(card: BingoCard): number {
  let h = 0;
  for (const col of variantDef.value.columns) {
    for (let r = 1; r <= variantDef.value.numRows; r++) {
      if (isFreeCell(col, r)) continue;
      if (drawn(card, col.label, r)) h++;
    }
  }
  return h;
}

// Memoiza hits por cartón: se recalcula solo cuando cambian los cartones o las fichas sorteadas,
// en vez de recalcular en cada llamada desde el template (footer, progreso, filtros).
const hitsMap = computed(() => {
  const map = new Map<string, number>();
  for (const card of props.cartons) map.set(card.id, computeHits(card));
  return map;
});

function hits(card: BingoCard): number { return hitsMap.value.get(card.id) ?? computeHits(card); }
function pct(card: BingoCard) { return Math.round((hits(card) / variantDef.value.totalCells) * 100); }
function isHot(card: BingoCard)      { return hits(card) >= props.hotThreshold; }
function isWinner(card: BingoCard)   { void _bingoVer.value;    return _bingoSet.has(card.id) }
function isHit(card: BingoCard)      { void _hitVer.value;      return _hitSet.has(card.id) }
function isFreshHot(card: BingoCard) { void _freshHotVer.value; return _freshHotSet.has(card.id) }

// Menor cantidad de fichas que le faltan al jugador para completar su mejor
// cartón (asumiendo cartón lleno, igual criterio que fullBingo() más abajo).
// null si no tiene cartones activos o ya ganó — usado por el padre para
// mostrar un indicador personal fijo ("te faltan N fichas").
const myMissing = computed<number | null>(() => {
  const active = props.cartons.filter(c => !isWinner(c));
  if (active.length === 0) return null;
  let min = variantDef.value.totalCells;
  for (const card of active) {
    const missing = variantDef.value.totalCells - hits(card);
    if (missing < min) min = missing;
  }
  return min;
});

defineExpose({ myMissing, streak, selectedId });

// computed para evitar recálculo en cada render del template
const filterCounts = computed(() => {
  let win = 0, hot = 0;
  for (const c of props.cartons) {
    if (isWinner(c)) { win++; continue; }
    if (isHot(c))    hot++;
  }
  return { all: props.cartons.length, hot, win };
});

function getFilterCount(f: Filter): number {
  return filterCounts.value[f];
}

// Memoizado: la variante no cambia durante la partida
const colNumbersMap = computed(() => {
  const map = new Map<string, number[]>();
  for (const col of variantDef.value.columns) {
    map.set(col.label, Array.from({ length: col.max - col.min + 1 }, (_, i) => col.min + i));
  }
  return map;
});

function colNumbers(col: BingoVariantColumn): number[] {
  return colNumbersMap.value.get(col.label) ?? [];
}

function shortSerial(s: string): string {
  if (!s) return "—";
  const p = s.split("-");
  return p.length >= 3 ? `${p[0]}-…-${p[p.length - 1]}` : s.slice(-14);
}

function fullBingo(card: BingoCard): boolean {
  for (const col of variantDef.value.columns) {
    for (let r = 1; r <= variantDef.value.numRows; r++) {
      if (isFreeCell(col, r)) continue;
      const n = num(card, col.label, r);
      // n === null: celda vacía por diseño (BINGO_90 hasEmptyCells) — no cuenta como pendiente
      if (n === null) continue;
      if (!drawnSet.value.has(n)) return false;
    }
  }
  return true;
}

function onEscapeKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && selectedId.value) selectedId.value = null
}
window.addEventListener('keydown', onEscapeKey)
onUnmounted(() => {
  hitTimers.forEach(clearTimeout)
  freshHotTimers.forEach(clearTimeout)
  window.removeEventListener('keydown', onEscapeKey)
})

watch(() => props.drawnNumbers, (next, prev) => {
  if (next.length === 0) {
    _bingoSet.clear();    _bingoVer.value++
    hitTimers.forEach(clearTimeout)
    hitTimers.clear()
    _hitSet.clear();      _hitVer.value++
    freshHotTimers.forEach(clearTimeout)
    freshHotTimers.clear()
    _freshHotSet.clear(); _freshHotVer.value++
    streak.value = 0
    return;
  }

  // Cuando el historial cambia de forma no monotónica (reconexión, corrección de sorteo),
  // revalidar qué cartones siguen siendo BINGO con el draw actualizado.
  if ((prev?.length ?? 0) > next.length) {
    let changed = false
    for (const id of [..._bingoSet]) {
      const card = props.cartons.find(c => c.id === id);
      if (!card || !fullBingo(card)) { _bingoSet.delete(id); changed = true }
    }
    if (changed) _bingoVer.value++
  }

  const prevSet = new Set(prev ?? []);
  const newNums = next.filter(n => !prevSet.has(n));

  // Flash en cartones que recibieron alguna de las bolas nuevas (también dispara sonido)
  if (newNums.length > 0 && prev !== undefined) {
    const newNumSet = new Set(newNums)
    let anyHit = false
    for (const card of props.cartons) {
      const hit = variantDef.value.columns.some(col => {
        const m = card.cardMatrix as Record<string, (number | null)[]> | null
        return m?.[col.label]?.some(n => n !== null && newNumSet.has(n)) ?? false
      })
      if (hit) {
        anyHit = true
        _hitSet.add(card.id); _hitVer.value++
        emit("card-hit", card.id)
        // Limpiar flash existente si llega otra bola antes de que expire
        if (hitTimers.has(card.id)) clearTimeout(hitTimers.get(card.id)!)
        const t = setTimeout(() => {
          _hitSet.delete(card.id); _hitVer.value++
          hitTimers.delete(card.id)
        }, 700)
        hitTimers.set(card.id, t)
      }
    }
    if (anyHit) {
      playSelect()
      navigator.vibrate?.(80)
      streak.value++
    } else if (newNums.length > 0) {
      streak.value = 0
    }
  }

  const newWinners: BingoCard[] = [];
  for (const card of props.cartons) {
    if (_bingoSet.has(card.id)) continue;
    if (fullBingo(card)) {
      _bingoSet.add(card.id);
      if (prev !== undefined) newWinners.push(card);
    }
  }
  if (newWinners.length > 0) {
    _bingoVer.value++
    playWinner();
    emit("bingo-detected", newWinners);
  }

  // Detectar cartones que acaban de cruzar el umbral HOT por primera vez
  if (prev !== undefined) {
    const prevDrawnSet = new Set(prev ?? [])
    for (const card of props.cartons) {
      if (_bingoSet.has(card.id) || _freshHotSet.has(card.id)) continue
      if (isHot(card)) {
        // Verificar que antes NO era hot (recalculando con el draw anterior)
        const prevHits = variantDef.value.columns.reduce((acc, col) => {
          const m = card.cardMatrix as Record<string, (number | null)[]> | null
          return acc + (m?.[col.label]?.filter(n => n !== null && prevDrawnSet.has(n as number)).length ?? 0)
        }, 0)
        if (prevHits < props.hotThreshold) {
          _freshHotSet.add(card.id); _freshHotVer.value++
          if (freshHotTimers.has(card.id)) clearTimeout(freshHotTimers.get(card.id)!)
          const t = setTimeout(() => {
            _freshHotSet.delete(card.id); _freshHotVer.value++
            freshHotTimers.delete(card.id)
          }, 1500)
          freshHotTimers.set(card.id, t)
        }
      }
    }
  }
}, { immediate: true });
</script>

<style scoped>
/* ═══════════════════════════════════════════
   ROOT
═══════════════════════════════════════════ */
.ctn-root { width: 100%; display: flex; flex-direction: column; gap: 10px; }

/* ═══════════════════════════════════════════
   TOOLBAR
═══════════════════════════════════════════ */
.ctn-bar {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 14px;
  background: rgba(255,255,255,0.024);
  border: 1px solid rgba(80,110,220,0.12);
  border-radius: 12px; flex-wrap: wrap;
}
.ctn-bar__left  { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.ctn-bar__right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto; }

.ctn-bar__icon { color: #7b4fff; font-size: 11px; }
.ctn-bar__title {
  font-size: 10px; font-weight: 800; color: #5a70a0;
  text-transform: uppercase; letter-spacing: 1px;
}
.ctn-bar__count {
  background: rgba(123,79,255,0.14); border: 1px solid rgba(123,79,255,0.26);
  color: #a090e0; border-radius: 20px; padding: 1px 8px;
  font-size: 10px; font-weight: 800;
}
.ctn-bar__drawn {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; color: #2af598;
  padding: 2px 8px;
  background: rgba(42,245,152,0.06); border: 1px solid rgba(42,245,152,0.16);
  border-radius: 20px; letter-spacing: 0.2px;
}
.ctn-bar__drawn i   { font-size: 9px; opacity: 0.6; }
.ctn-bar__drawn em  { opacity: 0.35; font-style: normal; font-weight: 500; }
.ctn-bar__streak {
  display: flex; align-items: center; gap: 3px;
  font-size: 10px; font-weight: 800; color: #ffd060;
  padding: 2px 8px;
  background: rgba(255,200,0,0.08); border: 1px solid rgba(255,200,0,0.25);
  border-radius: 20px; letter-spacing: 0.2px;
  animation: streak-pop 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
.ctn-bar__streak i { font-size: 9px; opacity: 0.8; }
@keyframes streak-pop { from { transform: scale(0.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.ctn-streak-enter-active { animation: streak-pop 0.35s cubic-bezier(0.34,1.56,0.64,1); }
.ctn-streak-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.ctn-streak-leave-to     { opacity: 0; transform: scale(0.8); }

/* Filters */
.ctn-bar__filters { display: flex; align-items: center; gap: 4px; flex: 1; justify-content: center; flex-wrap: wrap; }
.ctn-fbtn {
  display: flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 8px;
  border: 1px solid rgba(80,110,220,0.15); background: transparent;
  color: #4a5e90; font-size: 10px; font-weight: 600; cursor: pointer;
  transition: all 0.14s ease; letter-spacing: 0.2px;
}
.ctn-fbtn:hover { border-color: rgba(100,140,255,0.3); color: #7a90c0; }
.ctn-fbtn--active { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.35); color: #b0c4ff; }
.ctn-fbtn__n {
  min-width: 14px; height: 14px; padding: 0 3px; border-radius: 7px;
  background: rgba(100,140,255,0.1); font-size: 9px; font-weight: 800; color: #6a80b0;
  display: inline-flex; align-items: center; justify-content: center;
}
.ctn-fbtn--active .ctn-fbtn__n { background: rgba(42,245,152,0.14); color: #2af598; }
.ctn-fbtn--new-hot {
  border-color: rgba(255,140,30,0.45); color: #ffb060;
  animation: fbtn-hot-pulse 0.7s ease-in-out infinite alternate;
}
@keyframes fbtn-hot-pulse {
  from { box-shadow: 0 0 0 0 rgba(255,140,30,0.0); background: transparent; }
  to   { box-shadow: 0 0 8px 2px rgba(255,140,30,0.25); background: rgba(255,140,30,0.08); }
}

/* Legend */
.ctn-legend { display: flex; align-items: center; gap: 3px; }
.ctn-legend__dot {
  width: 16px; height: 16px; border-radius: 50%;
  font-size: 7.5px; font-weight: 800; color: #fff;
  display: flex; align-items: center; justify-content: center;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  box-shadow: 0 1px 4px rgba(0,0,0,0.35);
  cursor: default;
}
.ctn-legend__dot--col0 { background: radial-gradient(circle at 38% 32%, var(--bc0-light), var(--bc0-solid) 60%, var(--bc0-dark)); box-shadow: 0 1px 6px var(--bc0-glow); }
.ctn-legend__dot--col1 { background: radial-gradient(circle at 38% 32%, var(--bc1-light), var(--bc1-solid) 60%, var(--bc1-dark)); box-shadow: 0 1px 6px var(--bc1-glow); }
.ctn-legend__dot--col2 { background: radial-gradient(circle at 38% 32%, var(--bc2-light), var(--bc2-solid) 60%, var(--bc2-dark)); box-shadow: 0 1px 6px var(--bc2-glow); }
.ctn-legend__dot--col3 { background: radial-gradient(circle at 38% 32%, var(--bc3-light), var(--bc3-solid) 60%, var(--bc3-dark)); box-shadow: 0 1px 6px var(--bc3-glow); }
.ctn-legend__dot--col4 { background: radial-gradient(circle at 38% 32%, var(--bc4-light), var(--bc4-solid) 60%, var(--bc4-dark)); box-shadow: 0 1px 6px var(--bc4-glow); }

/* Size toggle */
.ctn-sizebar { display: flex; gap: 2px; }
.ctn-sz {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid rgba(80,110,220,0.16); background: transparent;
  color: #384870; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.13s ease;
}
.ctn-sz:hover  { border-color: rgba(100,140,255,0.3); color: #6a80b0; }
.ctn-sz--on    { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.35); color: #b0c4ff; }

/* ═══════════════════════════════════════════
   GRID
═══════════════════════════════════════════ */
.ctn-grid { display: grid; gap: 12px; }
.ctn-grid--md { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
.ctn-grid--sm { grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 8px; }

/* ═══════════════════════════════════════════
   CARD
═══════════════════════════════════════════ */
.ctn-card {
  position: relative; border-radius: 14px; overflow: hidden;
  background: #fff;
  border: 1.5px solid rgba(180,195,240,0.55);
  box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 1px 0 rgba(255,255,255,0.9) inset;
  transition: transform 0.22s cubic-bezier(0.22,0.68,0,1.2), box-shadow 0.22s ease, border-color 0.22s ease;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  animation-delay: calc(var(--i,0) * 40ms);
  cursor: pointer;
}
/* shimmer on hover */
.ctn-card::after {
  content: ''; position: absolute; inset: 0; border-radius: inherit;
  background: linear-gradient(110deg, transparent 28%, rgba(255,255,255,0.6) 50%, transparent 72%);
  transform: translateX(-120%); pointer-events: none; z-index: 10;
}
.ctn-card:hover::after { animation: shimmer 0.5s ease forwards; }
@keyframes shimmer { to { transform: translateX(120%); } }

.ctn-card:hover {
  transform: translateY(-5px) scale(1.015);
  box-shadow: 0 16px 44px rgba(0,0,0,0.18), 0 0 0 2px rgba(80,130,255,0.25);
  border-color: rgba(80,130,255,0.5);
}
.ctn-card--active {
  border-color: rgba(42,200,130,0.65) !important;
  box-shadow: 0 16px 44px rgba(0,0,0,0.18), 0 0 0 2px rgba(42,200,130,0.35) !important;
  transform: translateY(-5px) scale(1.015);
}
.ctn-card--hot {
  border-color: rgba(255,140,30,0.5);
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, hpulse 2.2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 40ms), 0.45s;
}
.ctn-card--winner {
  border-color: rgba(220,170,0,0.7) !important;
  animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, wpulse 2.2s ease-in-out infinite;
  animation-delay: calc(var(--i,0) * 40ms), 0.45s;
}
@keyframes cin    { from { opacity:0; transform:translateY(22px) scale(0.92) } to { opacity:1; transform:translateY(0) scale(1) } }
@keyframes hpulse { 0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 1.5px rgba(255,140,30,0.3) } 50% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3.5px rgba(255,140,30,0.6) } }
@keyframes wpulse { 0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 2px rgba(220,170,0,0.38) } 50% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 4px rgba(220,170,0,0.7) } }

/* Fresh-hot — parpadeo naranja al cruzar el umbral HOT por primera vez */
.ctn-card--fresh-hot { animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, fresh-hot-ring 1.5s ease-out forwards !important; }
@keyframes fresh-hot-ring {
  0%   { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(255,140,30,0.0); }
  15%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 4px rgba(255,140,30,0.8), 0 0 24px 4px rgba(255,140,30,0.35); }
  60%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3px rgba(255,140,30,0.4); }
  100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 1.5px rgba(255,140,30,0.3); }
}

/* Hit flash — anillo que aparece y se desvanece en 700ms cuando una bola impacta el cartón */
.ctn-card--hit-flash { animation: cin 0.45s cubic-bezier(0.34,1.56,0.64,1) both, card-hit-ring 0.7s ease-out forwards !important; }
@keyframes card-hit-ring {
  0%   { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 0 rgba(100,180,255,0.0),  0 0 0 0 rgba(42,245,152,0.0); }
  20%  { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 3px rgba(100,180,255,0.7), 0 0 18px 2px rgba(42,245,152,0.25); }
  100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15), 0 0 0 5px rgba(100,180,255,0.0), 0 0 0 0 rgba(42,245,152,0.0); }
}

/* ── Header ── */
.ctn-head { display: grid; }
.ctn-head__l {
  display: flex; align-items: center; justify-content: center;
  padding: 8px 0 7px;
  font-family: 'Anton','Impact',sans-serif; font-size: 17px;
  letter-spacing: 1px; font-weight: 900; color: #fff;
  text-shadow: 0 2px 5px rgba(0,0,0,0.3); position: relative;
}
.ctn-head__l::after {
  content: ''; position: absolute; bottom: 0; left: 12%; right: 12%; height: 2px;
  background: rgba(255,255,255,0.22); border-radius: 2px;
}
.ctn-head__l--col0 { background: linear-gradient(160deg, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-head__l--col1 { background: linear-gradient(160deg, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-head__l--col2 { background: linear-gradient(160deg, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-head__l--col3 { background: linear-gradient(160deg, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-head__l--col4 { background: linear-gradient(160deg, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }
.ctn-grid--sm .ctn-head__l { font-size: 13px; padding: 6px 0 5px; }

/* ── Body ── */
.ctn-body { display: grid; background: #fff; }
.ctn-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border-right: 1px solid #eaeefc; border-bottom: 1px solid #eaeefc;
  transition: background 0.1s; position: relative;
}
.ctn-cell--col0 { background: color-mix(in srgb, var(--bc0-solid) 6%, #fff); }
.ctn-cell--col1 { background: color-mix(in srgb, var(--bc1-solid) 6%, #fff); }
.ctn-cell--col2 { background: color-mix(in srgb, var(--bc2-solid) 6%, #fff); }
.ctn-cell--col3 { background: color-mix(in srgb, var(--bc3-solid) 5%, #fff); }
.ctn-cell--col4 { background: color-mix(in srgb, var(--bc4-solid) 6%, #fff); }

.ctn-cell--hit.ctn-cell--col0 { background: var(--bc0-tint); }
.ctn-cell--hit.ctn-cell--col1 { background: var(--bc1-tint); }
.ctn-cell--hit.ctn-cell--col2 { background: var(--bc2-tint); }
.ctn-cell--hit.ctn-cell--col3 { background: var(--bc3-tint); }
.ctn-cell--hit.ctn-cell--col4 { background: var(--bc4-tint); }
.ctn-cell--free { background: linear-gradient(135deg,rgba(42,245,152,0.1),rgba(0,158,253,0.07)) !important; }

.ctn-num {
  font-family: 'Poppins','Segoe UI',sans-serif; font-size: 13px;
  font-weight: 700; color: #1e2444; line-height: 1; letter-spacing: -0.3px;
}
.ctn-num--empty { opacity: 0; pointer-events: none; }
.ctn-grid--sm .ctn-num { font-size: 11px; }

/* ── Balls ── */
.ctn-ball {
  width: 78%; height: 78%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(0,0,0,0.28), inset 0 -2px 4px rgba(0,0,0,0.18), inset 0 2px 4px rgba(255,255,255,0.35);
}
.ctn-ball--col0 { background: radial-gradient(circle at 36% 30%, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-ball--col1 { background: radial-gradient(circle at 36% 30%, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-ball--col2 { background: radial-gradient(circle at 36% 30%, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-ball--col3 { background: radial-gradient(circle at 36% 30%, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-ball--col4 { background: radial-gradient(circle at 36% 30%, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }
.ctn-ball span {
  font-family: 'Poppins',sans-serif; font-size: 11px; font-weight: 900; color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5); line-height: 1;
}
.ctn-grid--sm .ctn-ball span { font-size: 9px; }

@keyframes bpop {
  0%   { transform: scale(0) rotate(-18deg); opacity: 0 }
  65%  { transform: scale(1.2) rotate(4deg); opacity: 1 }
  82%  { transform: scale(0.95) rotate(-1deg) }
  100% { transform: scale(1) rotate(0deg) }
}
@keyframes cell-flash {
  0%   { filter: brightness(1.8) saturate(1.5); }
  100% { filter: brightness(1) saturate(1); }
}
.ctn-cell--hit .ctn-ball {
  animation: bpop 0.42s cubic-bezier(0.34,1.56,0.64,1) both, cell-flash 0.5s ease both;
}

.ctn-free {
  font-size: 18px; line-height: 1;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 1px 3px rgba(42,245,152,0.35));
}
.ctn-grid--sm .ctn-free { font-size: 14px; }

/* ── Progress strip ── */
.ctn-prog { height: 3px; background: #eaeefc; }
.ctn-prog__fill { height: 100%; background: linear-gradient(90deg,#4a7fff,#2af598); transition: width 0.55s cubic-bezier(0.4,0,0.2,1); }
.ctn-prog__fill--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); }
.ctn-prog__fill--win { background: linear-gradient(90deg,#ffd700,#ff9800); }

/* ── Footer ── */
.ctn-foot {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 8px; background: #fff; border-top: 1px solid #eaeefc; gap: 5px;
}
.ctn-foot__serial {
  font-size: 7.5px; color: #9aa0c0; font-family: 'Courier New',monospace;
  letter-spacing: 0.3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  flex: 1; min-width: 0;
}
.ctn-foot__badge {
  display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px;
  border-radius: 20px; font-size: 9px; font-weight: 800; color: #fff;
  flex-shrink: 0; white-space: nowrap; letter-spacing: 0.3px;
}
.ctn-foot__badge i    { font-size: 7.5px; }
.ctn-foot__badge small { font-size: 7px; opacity: 0.6; font-weight: 500; }
.ctn-foot__badge--hot { background: linear-gradient(90deg,#ff8c1a,#ffcc00); color: #2a1000; }
.ctn-foot__badge--win { background: linear-gradient(90deg,#e8b800,#ffd94a); color: #1a0e00; }
.ctn-foot__hits       { font-size: 9px; font-weight: 700; color: #b0b8d8; flex-shrink: 0; }
.ctn-foot__hits small { font-size: 7.5px; opacity: 0.5; font-weight: 500; }
.ctn-foot__hits--zero { color: #d0d4e8; }

/* ═══════════════════════════════════════════
   DETAIL MODAL
═══════════════════════════════════════════ */
.ctn-modal-bg {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2,4,18,0.84); backdrop-filter: blur(12px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.ctn-modal {
  position: relative; width: 100%; max-width: 400px;
  background: linear-gradient(160deg,#0c1440 0%,#060922 100%);
  border: 1.5px solid rgba(70,120,255,0.24); border-radius: 20px;
  padding: 20px 18px 18px;
  box-shadow: 0 28px 80px rgba(0,0,0,0.75), 0 0 36px rgba(70,120,255,0.07);
}
.ctn-modal__x {
  position: absolute; top: 12px; right: 12px;
  width: 26px; height: 26px; border-radius: 50%;
  border: 1px solid rgba(100,130,255,0.2); background: rgba(255,255,255,0.05);
  color: #5a70a0; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.13s;
}
.ctn-modal__x:hover { background: rgba(255,255,255,0.1); color: #b0c0e0; }

.ctn-modal__header { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
.ctn-modal__tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 900; padding: 2px 10px;
  border-radius: 10px; letter-spacing: 0.5px; flex-shrink: 0;
}
.ctn-modal__tag--win { background: linear-gradient(135deg,#ffd700,#ffaa00); color: #1a1000; }
.ctn-modal__tag--hot { background: linear-gradient(135deg,#ff8c1a,#ffcc00); color: #1a0800; }
.ctn-modal__serial   { font-size: 8px; color: #2a3860; font-family: 'Courier New',monospace; }
.ctn-modal__pattern {
  display: flex; align-items: center; gap: 4px;
  font-size: 9px; font-weight: 700; color: #7b4fff;
  background: rgba(123,79,255,0.08); border: 1px solid rgba(123,79,255,0.18);
  padding: 2px 7px; border-radius: 6px; margin-top: 2px;
  text-transform: uppercase; letter-spacing: 0.4px;
}
.ctn-modal__pattern i { font-size: 8px; opacity: 0.8; }

.ctn-dcard { border-radius: 10px; overflow: hidden; border: 1px solid rgba(70,100,200,0.2); margin-bottom: 14px; }
.ctn-dcard__head { display: grid; }
.ctn-dcard__body { display: grid; background: #eef2ff; }
.ctn-dcell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1; border: 0.5px solid rgba(140,165,230,0.3);
}
.ctn-dcell.ctn-cell--hit  { background: linear-gradient(135deg,#0e0630,#070932); }
.ctn-dcell.ctn-cell--free { background: linear-gradient(135deg,#0c0424,#080c38); }
.ctn-dfree { font-size: 20px; background: linear-gradient(135deg,#2af598,#009efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.ctn-dball { width: 70%; height: 70%; }
.ctn-dnum  { font-size: 14px; font-weight: 700; color: #18204c; }

.ctn-stats {
  display: flex; border-radius: 10px; overflow: hidden;
  border: 1px solid rgba(70,100,200,0.16); margin-bottom: 10px;
}
.ctn-stat {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 10px 4px; background: rgba(255,255,255,0.022);
  border-right: 1px solid rgba(70,100,200,0.12);
}
.ctn-stat:last-child { border-right: none; }
.ctn-stat__v     { font-size: 22px; font-weight: 800; color: #a0b8f8; line-height: 1; }
.ctn-stat__v--hit { color: #2af598; }
.ctn-stat__v--pct { color: #ffd060; }
.ctn-stat__l     { font-size: 8px; color: #384870; margin-top: 3px; letter-spacing: 0.3px; }

.ctn-modal__prog { height: 4px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; }
.ctn-modal__prog-fill { height: 100%; background: linear-gradient(90deg,#4a7fff,#2af598); transition: width 0.5s ease; border-radius: 4px; }

/* ═══════════════════════════════════════════
   SPECTATOR
═══════════════════════════════════════════ */
.ctn-spectator { padding: 6px 0 2px; }
.ctn-spectator__hdr {
  display: flex; align-items: center; gap: 6px;
  font-size: 10px; font-weight: 700; color: #4a6aaa;
  text-transform: uppercase; letter-spacing: 0.7px;
  margin-bottom: 6px; padding: 0 2px;
}
.ctn-spectator__eye {
  width: 20px; height: 20px; border-radius: 6px;
  background: rgba(80,120,255,0.1); border: 1px solid rgba(80,120,255,0.18);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #6a90d8; flex-shrink: 0;
}
.ctn-spectator__label { flex: 1; }
.ctn-spectator__count { font-size: 11px; font-weight: 800; color: #7c9dff; }
.ctn-spectator__count small { font-weight: 400; color: #4a6aaa; margin-left: 2px; }

/* ── Ball grid (spectator) — fichas reales, minimalista y compacta ── */
.ctn-ball-grid {
  display: flex; gap: 3px;
}
.ctn-ball-col  { flex: 1; display: flex; flex-direction: column; gap: 1px; min-width: 0; }

/* Column header — barra plana y baja */
.ctn-ball-col__hdr {
  text-align: center; font-family: 'Anton', sans-serif; font-size: 11px;
  padding: 2px 0; border-radius: 4px; margin-bottom: 1px;
  letter-spacing: 0.3px; color: #fff; font-weight: 900;
  text-shadow: 0 1px 2px rgba(0,0,0,0.35);
  line-height: 1.3;
}
.ctn-ball-col__hdr--col0 { background: linear-gradient(160deg, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-ball-col__hdr--col1 { background: linear-gradient(160deg, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-ball-col__hdr--col2 { background: linear-gradient(160deg, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-ball-col__hdr--col3 { background: linear-gradient(160deg, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-ball-col__hdr--col4 { background: linear-gradient(160deg, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }

/* Number cells — bajas y planas, sin aspect-ratio cuadrado */
.ctn-ball-cell {
  display: flex; align-items: center; justify-content: center;
  height: 16px; border-radius: 4px;
  font-size: 9px; font-weight: 700; line-height: 1;
  color: rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.025);
  border: 1px solid rgba(255,255,255,0.04);
  transition: background 0.15s ease, color 0.15s ease;
  user-select: none; cursor: default;
}

/* Sorteada — celda sólida de color, plana (sin relieve) */
.ctn-ball-cell--hit {
  color: #fff; font-weight: 800;
  border-color: rgba(255,255,255,0.16);
}
.ctn-ball-cell--hit.ctn-ball-cell--col0 { background: var(--bc0-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col1 { background: var(--bc1-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col2 { background: var(--bc2-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col3 { background: var(--bc3-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col4 { background: var(--bc4-solid); }

/* Última sorteada — resalte sutil sin animación de escala (evita reflow de altura) */
.ctn-ball-cell--latest {
  box-shadow: 0 0 0 1px rgba(255,255,255,0.55);
}

/* ═══════════════════════════════════════════
   EMPTY
═══════════════════════════════════════════ */
.ctn-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; padding: 60px 20px;
}
.ctn-empty__icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: rgba(80,120,255,0.06); border: 1px solid rgba(80,120,255,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: #2a3860; margin-bottom: 6px;
}
.ctn-empty__text { font-size: 13px; font-weight: 700; color: #3a4e80; margin: 0; }
.ctn-empty__sub  { font-size: 11px; color: #2a3660; margin: 0; }

/* ═══════════════════════════════════════════
   TRANSITIONS
═══════════════════════════════════════════ */
.ctn-pop-enter-active  { transition: opacity 0.3s ease, transform 0.3s ease; }
.ctn-pop-enter-from    { opacity: 0; transform: translateY(12px) scale(0.96); }
.ctn-pop-leave-active  { transition: opacity 0.15s ease; }
.ctn-pop-leave-to      { opacity: 0; }
.ctn-modal-enter-active { transition: opacity 0.2s ease; }
.ctn-modal-enter-from   { opacity: 0; }
.ctn-modal-leave-active { transition: opacity 0.15s ease; }
.ctn-modal-leave-to     { opacity: 0; }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 600px) {
  .ctn-grid--md { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .ctn-grid--sm { grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .ctn-bar { gap: 6px; padding: 7px 10px; }
  .ctn-bar__filters { justify-content: flex-start; }
  .ctn-legend { display: none; }
}
@media (max-width: 380px) {
  .ctn-grid--md, .ctn-grid--sm { grid-template-columns: repeat(2, 1fr); gap: 6px; }
  .ctn-head__l { font-size: 12px; padding: 5px 0 4px; }
  .ctn-num { font-size: 11px; }
  .ctn-ball span { font-size: 9px; }
}
</style>
