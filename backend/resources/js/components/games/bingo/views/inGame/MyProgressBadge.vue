<script setup lang="ts">
interface Props {
  missing: number | null
}

defineProps<Props>()
</script>

<template>
  <Transition name="mp-fade">
    <div
      v-if="missing !== null && missing >= 0"
      class="mp-badge"
      :class="{ 'mp-badge--bingo': missing === 0, 'mp-badge--hot': missing > 0 && missing <= 2, 'mp-badge--warn': missing > 2 && missing <= 5 }"
    >
      <i class="fas fa-bullseye mp-badge__icon" />
      <span v-if="missing === 0" class="mp-badge__text">¡Tienes BINGO! Presiona tu cartón</span>
      <span v-else class="mp-badge__text">
        Te falta<span v-if="missing !== 1">n</span>
        <strong class="mp-badge__num">{{ missing }}</strong>
        ficha<span v-if="missing !== 1">s</span> para BINGO
      </span>
    </div>
  </Transition>
</template>

<style scoped>
.mp-badge {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; border-radius: 10px;
  background: rgba(80,120,255,0.06); border: 1px solid rgba(80,120,255,0.16);
  color: #8ca8e0; font-size: 11px; font-weight: 700;
  transition: background 0.25s ease, border-color 0.25s ease, color 0.25s ease;
}
.mp-badge__icon { font-size: 11px; flex-shrink: 0; }
.mp-badge__num {
  display: inline-block; margin: 0 3px;
  font-family: 'Courier New', monospace; font-size: 13px; font-weight: 900;
}

.mp-badge--warn {
  background: rgba(255,160,0,0.08); border-color: rgba(255,160,0,0.25); color: #ffb060;
}
.mp-badge--bingo {
  background: rgba(42,245,152,0.12); border-color: rgba(42,245,152,0.45); color: #2af598;
  animation: mp-pulse-bingo 0.8s ease-in-out infinite alternate;
}
@keyframes mp-pulse-bingo {
  from { box-shadow: 0 0 0 0 rgba(42,245,152,0.15); }
  to   { box-shadow: 0 0 16px 3px rgba(42,245,152,0.35); }
}
.mp-badge--hot {
  background: rgba(255,50,50,0.1); border-color: rgba(255,50,50,0.35); color: #ff8080;
  animation: mp-pulse 1s ease-in-out infinite alternate;
}
@keyframes mp-pulse {
  from { box-shadow: 0 0 0 0 rgba(255,50,50,0.15); }
  to   { box-shadow: 0 0 12px 2px rgba(255,50,50,0.25); }
}

.mp-fade-enter-active,
.mp-fade-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.mp-fade-enter-from,
.mp-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
