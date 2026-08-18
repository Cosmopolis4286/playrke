<script setup lang="ts">
import { watch, ref } from 'vue'

const props = defineProps<{
  totalCards: number
  minCardsToStart: number | null
  maxCardsToStart?: number | null
  progressPct: number
  ready: boolean
}>()

const showConfetti = ref(false)

watch(() => props.ready, (isReady, wasReady) => {
  if (isReady && wasReady === false) {
    showConfetti.value = true
    setTimeout(() => { showConfetti.value = false }, 2800)
  }
})
</script>

<template>
  <div class="lby-progress-panel" :class="{ 'lby-progress-panel--ready': ready }">
    <Transition name="lby-confetti-fade">
      <div v-if="showConfetti" class="lby-confetti" aria-hidden="true">
        <span v-for="i in 18" :key="i" class="lby-confetti__piece" :style="{ '--i': i }" />
      </div>
    </Transition>
    <div class="lby-progress-panel__head">
      <span class="lby-progress-panel__title">
        <i class="fas fa-layer-group" />
        {{ ready ? '¡Listos para iniciar!' : 'Cartones para iniciar' }}
      </span>
      <span
        class="lby-progress-panel__count"
        :title="maxCardsToStart ? `Tope de cartones vendibles en la sala: ${maxCardsToStart}` : undefined"
      >
        <span class="lby-progress-panel__cur">{{ totalCards }}</span>
        <span class="lby-progress-panel__sep">/</span>
        <span class="lby-progress-panel__max">{{ minCardsToStart }}</span>
        <span v-if="maxCardsToStart" class="lby-progress-panel__cap">(tope {{ maxCardsToStart }})</span>
      </span>
    </div>

    <div class="lby-progress-panel__track">
      <div class="lby-progress-panel__fill" :style="{ width: progressPct + '%' }" />
      <div v-if="!ready" class="lby-progress-panel__remaining" :style="{ width: (100 - progressPct) + '%' }" />
    </div>

    <div v-if="!ready" class="lby-progress-panel__hint">
      <i class="fas fa-hourglass-half" />
      Faltan <strong>{{ (props.minCardsToStart ?? 0) - props.totalCards }}</strong> cartones para iniciar
    </div>
  </div>
</template>

<style scoped>
.lby-progress-panel {
  position: relative; overflow: hidden;
  background: rgba(255,255,255,.022); border: 1px solid rgba(255,255,255,.07);
  border-radius: 13px; padding: 9px 14px;
  display: flex; flex-direction: column; gap: 6px;
  backdrop-filter: blur(6px); box-shadow: 0 4px 20px rgba(0,0,0,.4);
  transition: border-color .3s;
}
.lby-progress-panel--ready {
  border-color: rgba(42,245,152,.22);
  box-shadow: 0 4px 20px rgba(0,0,0,.4), 0 0 20px rgba(42,245,152,.06);
}
.lby-progress-panel__head {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.lby-progress-panel__title {
  font-size: 9.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
  color: #3a5068; display: inline-flex; align-items: center; gap: 6px;
}
.lby-progress-panel__title i { font-size: 9px; }
.lby-progress-panel--ready .lby-progress-panel__title { color: #2af598; }
.lby-progress-panel__count { display: flex; align-items: baseline; gap: 2px; flex-shrink: 0; }
.lby-progress-panel__cur   { font-family: 'Anton', sans-serif; font-size: 15px; color: #c8d8f0; transition: color .3s; }
.lby-progress-panel--ready .lby-progress-panel__cur { color: #2af598; }
.lby-progress-panel__sep   { font-size: 11px; color: #2a3f58; margin: 0 1px; }
.lby-progress-panel__max   { font-family: 'Anton', sans-serif; font-size: 12px; color: #3a5068; }
.lby-progress-panel__cap   { font-size: 8px; color: #2a3f58; margin-left: 3px; }
.lby-progress-panel__track {
  height: 6px; border-radius: 20px; overflow: hidden;
  background: rgba(255,255,255,.05); display: flex;
}
.lby-progress-panel__fill {
  height: 100%; border-radius: 20px 0 0 20px;
  background: linear-gradient(90deg, #1450b8 0%, #2af598 100%);
  box-shadow: 0 0 10px rgba(42,245,152,.3);
  transition: width .7s cubic-bezier(.22,1,.36,1);
}
.lby-progress-panel--ready .lby-progress-panel__fill {
  border-radius: 20px;
  background: linear-gradient(90deg, #2af598 0%, #00ffd0 100%);
  animation: lbyCpPulse 1.8s ease-in-out infinite;
}
.lby-progress-panel__remaining {
  height: 100%; border-radius: 0 20px 20px 0; background: rgba(255,255,255,.03);
}
@keyframes lbyCpPulse {
  0%,100% { box-shadow: 0 0 6px rgba(42,245,152,.35); }
  50%     { box-shadow: 0 0 16px rgba(42,245,152,.7); }
}
.lby-progress-panel__hint {
  font-size: 9px; color: #3a5068; display: flex; align-items: center; gap: 5px;
}
.lby-progress-panel__hint i      { font-size: 8px; opacity: .6; }
.lby-progress-panel__hint strong { color: #9ecaf0; font-weight: 700; }

.lby-confetti {
  position: absolute; inset: 0; pointer-events: none; overflow: hidden; border-radius: inherit;
}
.lby-confetti__piece {
  position: absolute; top: -8px;
  left: calc(var(--i) * 5.5% + 2%);
  width: 5px; height: 9px; border-radius: 2px;
  background: hsl(calc(var(--i) * 20deg), 80%, 60%);
  animation: lbyConfettiFall calc(.9s + var(--i) * .07s) ease-in calc(var(--i) * .04s) both;
}
@keyframes lbyConfettiFall {
  0%   { transform: translateY(0) rotate(0deg) scaleX(1);  opacity: 1; }
  60%  { transform: translateY(60px) rotate(200deg) scaleX(.6); opacity: .9; }
  100% { transform: translateY(110px) rotate(400deg) scaleX(.4); opacity: 0; }
}
.lby-confetti-fade-leave-active { transition: opacity .3s ease; }
.lby-confetti-fade-leave-to     { opacity: 0; }

@media (max-width: 480px) {
  .lby-progress-panel { padding: 10px 12px; }
}
</style>
