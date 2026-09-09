<script setup lang="ts">
import JackpotDisplay from "@js/Components/Platform/JackpotDisplay.vue";
import type { JackpotEntry } from "@js/Components/Platform/JackpotDisplay.vue";

/**
 * Sección completa del jackpot (eyebrow + título + subtítulo + JackpotDisplay),
 * reutilizada por Landing.vue y GameRoomsShowcase.vue — antes cada página
 * repetía el mismo markup con copy propio. Se mantiene como sección
 * independiente (no incrustada en el header de salas) para no competir
 * visualmente con los KPIs/filtros que le siguen.
 */
withDefaults(
  defineProps<{
    jackpots: JackpotEntry[];
    eyebrow?: string;
    title?: string;
    subtitle?: string;
    id?: string;
    /** Bordes superior/inferior — útil sobre un fondo continuo (Showcase) para
     * que la sección se lea como un "momento" propio; la Landing ya tiene
     * separación natural entre .band, así que no los necesita. */
    bordered?: boolean;
  }>(),
  {
    eyebrow: "Pozo acumulado",
    title: "El jackpot no para de crecer",
    subtitle: "Cada cartón vendido en cualquier sala suma al pozo. Así de simple.",
    id: "jackpot",
    bordered: false,
  }
);
</script>

<template>
  <section v-if="jackpots.length" :id="id" class="jackpot-section" :class="{ 'jackpot-section--bordered': bordered }">
    <span class="jackpot-section__eyebrow">{{ eyebrow }}</span>
    <h2 class="jackpot-section__title">{{ title }}</h2>
    <p class="jackpot-section__subtitle">{{ subtitle }}</p>
    <JackpotDisplay :jackpots="jackpots" />
  </section>
</template>

<style scoped>
.jackpot-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 40px 20px 44px;
  text-align: center;
}
.jackpot-section--bordered {
  margin: 40px auto 44px;
  border-top: 1px solid rgba(255,255,255,.06);
  border-bottom: 1px solid rgba(255,255,255,.06);
}

.jackpot-section__eyebrow {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.6px;
  text-transform: uppercase;
  color: #2af598;
}

.jackpot-section__title {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(22px, 3.4vw, 30px);
  font-weight: 800;
  color: #eef2ff;
  margin: 10px 0 8px;
}

.jackpot-section__subtitle {
  color: #7a96c8;
  font-size: 13.5px;
  line-height: 1.6;
  margin: 0 0 22px;
}
</style>
