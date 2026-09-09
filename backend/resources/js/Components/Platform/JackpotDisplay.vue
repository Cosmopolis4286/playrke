<script setup lang="ts">
import { ref, reactive, watch, onMounted, onUnmounted } from "vue";
import { axios } from "@js/plugins/axios";

export interface JackpotEntry {
  code: string;
  label: string;
  periodicity: "PER_ROOM" | "WEEKLY" | "BIWEEKLY" | "MONTHLY";
  amount: number;
  currency: { iso_code: string | null; symbol: string | null };
}

const props = defineProps<{ jackpots: JackpotEntry[] }>();

const PERIODICITY_LABELS: Record<JackpotEntry["periodicity"], string> = {
  PER_ROOM: "Por sala",
  WEEKLY: "Semanal",
  BIWEEKLY: "Quincenal",
  MONTHLY: "Mensual",
};

// ── Polling propio: mismo TTL que el backend (15s), encapsulado aquí para
// que Landing y GameRoomsShowcase lo reutilicen sin duplicar setInterval +
// axios.get cada uno (mismo criterio que ya usa Landing.vue para stats). ──
const jackpots = ref<JackpotEntry[]>(props.jackpots ?? []);
let pollTimer: ReturnType<typeof setInterval> | null = null;

async function refresh() {
  try {
    const { data } = await axios.get("/api/jackpots");
    if (Array.isArray(data?.jackpots)) jackpots.value = data.jackpots;
  } catch {
    // Silencioso: se mantiene el último valor bueno conocido.
  }
}

onMounted(() => {
  pollTimer = setInterval(refresh, 15_000);
});
onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
  for (const state of Object.values(displayState)) {
    if (state.rafId !== null) cancelAnimationFrame(state.rafId);
  }
});

// ── Conteo animado por card — cada jackpot interpola su propio valor
// anterior→nuevo en ~700ms (ease-out) vía requestAnimationFrame, y dispara
// un pulso de glow en su card cuando el monto sube. Estado indexado por
// `code` (una entrada por card), sin dependencia externa. ──
interface DisplayState {
  amount: number;
  pulsing: boolean;
  rafId: number | null;
}
const displayState = reactive<Record<string, DisplayState>>({});

function animateEntry(entry: JackpotEntry, previous: number | undefined) {
  const state = displayState[entry.code] ??= { amount: entry.amount, pulsing: false, rafId: null };
  const start = previous ?? entry.amount;
  const target = entry.amount;
  const delta = target - start;

  if (delta === 0) {
    state.amount = target;
    return;
  }
  if (delta > 0 && previous !== undefined) {
    state.pulsing = true;
    setTimeout(() => { state.pulsing = false; }, 900);
  }

  const durationMs = 700;
  const startTime = performance.now();
  if (state.rafId !== null) cancelAnimationFrame(state.rafId);

  function step(now: number) {
    const elapsed = now - startTime;
    const t = Math.min(1, elapsed / durationMs);
    const eased = 1 - Math.pow(1 - t, 3); // ease-out cubic
    state.amount = start + delta * eased;
    if (t < 1) {
      state.rafId = requestAnimationFrame(step);
    } else {
      state.amount = target;
      state.rafId = null;
    }
  }
  state.rafId = requestAnimationFrame(step);
}

watch(
  jackpots,
  (list, previousList) => {
    const previousByCode = new Map((previousList ?? []).map((j) => [j.code, j.amount]));
    for (const entry of list) {
      animateEntry(entry, previousByCode.get(entry.code));
    }
  },
  { immediate: true, deep: true }
);

// El símbolo puede coincidir con el código (ej. USDT: symbol="USDT",
// iso_code="USDT") — mostrar ambos duplicaría "USDT 0,00 USDT". Solo se
// antepone el símbolo cuando aporta algo distinto del código (ej. "$"/"Bs").
function formattedAmount(entry: JackpotEntry): string {
  const amount = displayState[entry.code]?.amount ?? entry.amount;
  const formatted = amount.toLocaleString("es", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const { symbol, iso_code } = entry.currency;
  const prefix = symbol && symbol !== iso_code ? `${symbol} ` : "";
  return `${prefix}${formatted}`;
}
</script>

<template>
  <div v-if="jackpots.length" class="jackpot-display">
    <div
      v-for="(j, i) in jackpots"
      :key="j.code"
      class="jp-card"
      :class="{ 'jp-card--pulse': displayState[j.code]?.pulsing }"
      :style="{ animationDelay: `${i * 70}ms` }"
    >
      <span class="jp-card__period">{{ PERIODICITY_LABELS[j.periodicity] }}</span>
      <span class="jp-card__label">{{ j.label }}</span>
      <span class="jp-card__amount num">{{ formattedAmount(j) }}</span>
      <span class="jp-card__code">{{ j.currency.iso_code }}</span>
    </div>
  </div>
</template>

<style scoped>
.jackpot-display {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 168px));
  justify-content: center;
  gap: 12px;
  width: 100%;
}

.jp-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 16px 14px;
  border-radius: 14px;
  text-align: center;
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.07);
  box-shadow: 0 0 0 rgba(42, 245, 152, 0);
  transition: border-color 0.35s ease, box-shadow 0.35s ease, transform 0.35s ease;
  animation: jp-card-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes jp-card-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.jp-card--pulse {
  border-color: rgba(42, 245, 152, 0.45);
  box-shadow: 0 0 28px rgba(42, 245, 152, 0.18);
  transform: translateY(-2px);
}

.jp-card__period {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  color: #4a6698;
}

.jp-card__label {
  font-size: 11px;
  font-weight: 600;
  color: #7a96c8;
  margin-bottom: 2px;
}

.jp-card__amount {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(1.15rem, 2.4vw, 1.4rem);
  font-weight: 800;
  letter-spacing: 0.2px;
  font-variant-numeric: tabular-nums;
  color: #eef2ff;
  transition: color 0.35s ease;
}
.jp-card--pulse .jp-card__amount { color: #2af598; }

.jp-card__code {
  font-size: 10px;
  font-weight: 600;
  color: #7a96c8;
  letter-spacing: 0.3px;
}

@media (max-width: 480px) {
  .jackpot-display { grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px; }
  .jp-card { padding: 12px 10px; }
}

@media (prefers-reduced-motion: reduce) {
  .jp-card { animation: none; transition: none; }
  .jp-card--pulse { transform: none; }
}
</style>
