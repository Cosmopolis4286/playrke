<script setup lang="ts">
interface Props {
  show: boolean
  secondsLeft: number
}

defineProps<Props>()
</script>

<template>
  <Teleport to="body">
    <Transition name="pdm">
      <div v-if="show" class="pdm-overlay">
        <div class="pdm-box">
          <div class="pdm-particles">
            <span v-for="i in 12" :key="i" class="pdm-particle" :style="{ '--i': i }" />
          </div>

          <div class="pdm-ball">
            <span class="pdm-ball__letter">🎱</span>
          </div>

          <div class="pdm-title">¡El sorteo comienza!</div>
          <div class="pdm-subtitle">Prepara tus cartones</div>

          <div class="pdm-countdown">
            <svg class="pdm-countdown__ring" viewBox="0 0 64 64">
              <circle class="pdm-countdown__track" cx="32" cy="32" r="28" />
              <circle
                class="pdm-countdown__fill"
                cx="32" cy="32" r="28"
                :style="{ strokeDashoffset: 175.9 - (175.9 * (10 - secondsLeft) / 10) }"
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
