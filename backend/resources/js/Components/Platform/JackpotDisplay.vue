<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
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
  if (rafId !== null) cancelAnimationFrame(rafId);
});

// Destacado = mayor amount (comparación numérica directa, sin conversión de
// moneda — no hay tasa confiable USDT/VES en este contrato).
const featured = computed<JackpotEntry | null>(() => {
  if (!jackpots.value.length) return null;
  return [...jackpots.value].sort((a, b) => b.amount - a.amount)[0];
});
const others = computed(() => jackpots.value.filter((j) => j.code !== featured.value?.code));

// ── Animación de conteo — interpola el número mostrado desde el valor
// anterior al nuevo en ~800ms cada vez que amount cambia, vía
// requestAnimationFrame (sin dependencia externa). ──
const displayedAmount = ref(featured.value?.amount ?? 0);
let rafId: number | null = null;
const isPulsing = ref(false);

function animateTo(target: number, wasIncrease: boolean) {
  const start = displayedAmount.value;
  const delta = target - start;
  if (delta === 0) return;

  if (wasIncrease) {
    isPulsing.value = true;
    setTimeout(() => { isPulsing.value = false; }, 900);
  }

  const durationMs = 800;
  const startTime = performance.now();
  if (rafId !== null) cancelAnimationFrame(rafId);

  function step(now: number) {
    const elapsed = now - startTime;
    const t = Math.min(1, elapsed / durationMs);
    const eased = 1 - Math.pow(1 - t, 3); // ease-out cubic
    displayedAmount.value = start + delta * eased;
    if (t < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      displayedAmount.value = target;
      rafId = null;
    }
  }
  rafId = requestAnimationFrame(step);
}

watch(
  () => featured.value?.amount,
  (newAmount, oldAmount) => {
    if (newAmount == null) return;
    const previous = oldAmount ?? newAmount;
    animateTo(newAmount, newAmount > previous);
  },
  { immediate: true }
);

const formattedAmount = computed(() =>
  displayedAmount.value.toLocaleString("es", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
);

// El símbolo puede coincidir con el código (ej. USDT: symbol="USDT",
// iso_code="USDT") — mostrar ambos duplicaría "USDT 0,00 USDT". Solo se
// antepone el símbolo cuando aporta algo distinto del código (ej. "$"/"Bs").
function formatAmount(entry: JackpotEntry, amount: number): string {
  const formatted = amount.toLocaleString("es", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const { symbol, iso_code } = entry.currency;
  const prefix = symbol && symbol !== iso_code ? `${symbol} ` : "";
  return `${prefix}${formatted}`;
}
</script>

<template>
  <div class="jackpot-display">
    <div v-if="featured" class="jackpot-display__hero" :class="{ 'jackpot-display__hero--pulse': isPulsing }">
      <span class="jackpot-display__hero-label">{{ featured.label }}</span>
      <div class="jackpot-display__hero-amount">
        <span v-if="featured.currency.symbol && featured.currency.symbol !== featured.currency.iso_code" class="jackpot-display__symbol">{{ featured.currency.symbol }}</span>
        <span class="jackpot-display__number">{{ formattedAmount }}</span>
        <span class="jackpot-display__code">{{ featured.currency.iso_code }}</span>
      </div>
      <span class="jackpot-display__periodicity">{{ PERIODICITY_LABELS[featured.periodicity] }}</span>
    </div>

    <div v-if="others.length" class="jackpot-display__others">
      <div v-for="j in others" :key="j.code" class="jackpot-display__chip">
        <span class="jackpot-display__chip-label">{{ j.label }}</span>
        <b class="jackpot-display__chip-value">{{ formatAmount(j, j.amount) }} {{ j.currency.iso_code }}</b>
      </div>
    </div>
  </div>
</template>

<style scoped>
.jackpot-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.jackpot-display__hero {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 22px 36px;
  border-radius: 18px;
  background: linear-gradient(160deg, rgba(123,79,255,0.05) 0%, rgba(10,15,36,0.7) 100%);
  border: 1px solid rgba(42, 245, 152, 0.18);
  box-shadow: 0 0 40px rgba(42, 245, 152, 0.08);
  transition: box-shadow 0.4s ease, border-color 0.4s ease;
}

.jackpot-display__hero--pulse {
  border-color: rgba(42, 245, 152, 0.5);
  box-shadow: 0 0 55px rgba(42, 245, 152, 0.22);
}

.jackpot-display__hero-label {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 1.1px;
  text-transform: uppercase;
  color: #7a96c8;
}

.jackpot-display__hero-amount {
  display: flex;
  align-items: baseline;
  gap: 5px;
  font-family: "Orbitron", "Anton", sans-serif;
}
.jackpot-display__symbol {
  font-size: clamp(1.1rem, 2.6vw, 1.5rem);
  font-weight: 700;
  color: #2af598;
}
.jackpot-display__number {
  font-size: clamp(1.7rem, 5vw, 2.8rem);
  font-weight: 800;
  letter-spacing: 0.5px;
  font-variant-numeric: tabular-nums;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 45%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.jackpot-display__code {
  font-size: clamp(0.72rem, 1.8vw, 0.85rem);
  font-weight: 700;
  color: #7a96c8;
  letter-spacing: 0.4px;
}

.jackpot-display__periodicity {
  font-size: 9.5px;
  font-weight: 600;
  letter-spacing: 0.3px;
  color: #4a6698;
  text-transform: uppercase;
}

.jackpot-display__others {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}
.jackpot-display__chip {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
}
.jackpot-display__chip-label {
  font-size: 10.5px;
  color: #7a96c8;
}
.jackpot-display__chip-value {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: 11px;
  font-weight: 700;
  color: #c7d2f0;
}

@media (max-width: 480px) {
  .jackpot-display__hero { padding: 18px 22px; }
}

@media (prefers-reduced-motion: reduce) {
  .jackpot-display__hero { transition: none; }
}
</style>
