<script setup lang="ts">
interface Props {
  show: boolean
  secondsLeft: number
  totalSeconds?: number
}

const props = withDefaults(defineProps<Props>(), {
  totalSeconds: 10,
})
</script>

<template>
  <Teleport to="body">
    <Transition name="pdm">
      <div v-if="show" class="pdm-overlay" role="dialog" aria-modal="true" aria-labelledby="pdm-title-text">
        <div class="pdm-box">
          <div class="pdm-particles">
            <span v-for="i in 12" :key="i" class="pdm-particle" :style="{ '--i': i }" />
          </div>

          <div class="pdm-ball">
            <span class="pdm-ball__letter">🎱</span>
          </div>

          <div id="pdm-title-text" class="pdm-title">¡El sorteo comienza!</div>
          <div class="pdm-subtitle">Prepara tus cartones</div>

          <div class="pdm-countdown">
            <svg class="pdm-countdown__ring" viewBox="0 0 64 64">
              <circle class="pdm-countdown__track" cx="32" cy="32" r="28" />
              <circle
                class="pdm-countdown__fill"
                cx="32" cy="32" r="28"
                :style="{ strokeDashoffset: 175.9 - (175.9 * (totalSeconds - secondsLeft) / totalSeconds) }"
              />
            </svg>
            <span class="pdm-countdown__num">{{ secondsLeft }}</span>
          </div>

          <div class="pdm-hint">Las fichas aparecerán automáticamente</div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style>
/* Non-scoped: PreDrawModal uses Teleport to <body>, so scoped hashes won't reach the DOM */
.pdm-overlay {
  position: fixed; inset: 0; z-index: 1500;
  display: flex; align-items: center; justify-content: center;
  background: rgba(4,6,20,0.88); backdrop-filter: blur(16px);
}
.pdm-box {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  background: linear-gradient(160deg, rgba(11,18,48,0.98) 0%, rgba(6,10,30,0.98) 100%);
  border: 1px solid rgba(80,120,255,0.2); border-radius: 28px; padding: 40px 48px;
  box-shadow: 0 32px 100px rgba(0,0,0,0.8), 0 0 80px rgba(80,120,255,0.08);
  position: relative; overflow: hidden; max-width: 340px; width: 90%;
}

/* Particle ring */
.pdm-particles { position: absolute; inset: 0; pointer-events: none; }
.pdm-particle {
  position: absolute; width: 4px; height: 4px; border-radius: 50%;
  background: rgba(80,140,255,0.5);
  top: 50%; left: 50%;
  transform-origin: 0 0;
  animation: pdm-orbit calc(2s + var(--i, 1) * 0.15s) linear infinite;
  opacity: 0;
}
@keyframes pdm-orbit {
  0%   { transform: rotate(calc(var(--i, 1) * 30deg)) translateX(110px) scale(0); opacity: 0; }
  20%  { opacity: 0.8; transform: rotate(calc(var(--i, 1) * 30deg)) translateX(110px) scale(1); }
  80%  { opacity: 0.8; }
  100% { opacity: 0; transform: rotate(calc(360deg + var(--i, 1) * 30deg)) translateX(110px) scale(0); }
}

.pdm-ball {
  width: 72px; height: 72px; border-radius: 50%;
  background: radial-gradient(circle at 35% 30%, rgba(255,255,255,0.18) 0%, rgba(40,80,200,0.3) 50%, rgba(10,18,60,0.9) 100%);
  border: 2px solid rgba(80,140,255,0.35);
  display: flex; align-items: center; justify-content: center; font-size: 36px;
  box-shadow: 0 0 40px rgba(80,120,255,0.3), 0 4px 20px rgba(0,0,0,0.5);
  animation: pdm-ball-pulse 1.8s ease-in-out infinite;
}
@keyframes pdm-ball-pulse { 0%,100% { transform: scale(1); box-shadow: 0 0 40px rgba(80,120,255,0.3); } 50% { transform: scale(1.05); box-shadow: 0 0 60px rgba(80,120,255,0.5); } }

.pdm-title { font-family: 'Anton', sans-serif; font-size: 26px; color: #e0eaff; text-align: center; letter-spacing: 0.5px; }
.pdm-subtitle { font-size: 12px; color: #4a68a8; text-align: center; margin-top: -8px; }

.pdm-countdown { position: relative; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; }
.pdm-countdown__ring { width: 64px; height: 64px; transform: rotate(-90deg); position: absolute; }
.pdm-countdown__track { fill: none; stroke: rgba(80,120,255,0.1); stroke-width: 4; }
.pdm-countdown__fill {
  fill: none; stroke: #4a7fff; stroke-width: 4; stroke-linecap: round;
  stroke-dasharray: 175.9;
  transition: stroke-dashoffset 1s linear;
  filter: drop-shadow(0 0 6px rgba(74,127,255,0.7));
}
.pdm-countdown__num { font-family: 'Anton', sans-serif; font-size: 28px; color: #7c9dff; position: relative; z-index: 1; }

.pdm-hint { font-size: 10px; color: #2a3860; text-align: center; }

/* Transitions */
.pdm-enter-active { transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175,0.885,0.32,1.275); }
.pdm-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.pdm-enter-from  { opacity: 0; transform: scale(0.92); }
.pdm-leave-to    { opacity: 0; transform: scale(1.04); }

@media (max-width: 380px) {
  .pdm-box { padding: 30px 24px; }
  .pdm-title { font-size: 22px; }
}
</style>
