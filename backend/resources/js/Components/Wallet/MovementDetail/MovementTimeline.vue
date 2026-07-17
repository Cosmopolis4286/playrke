<script setup lang="ts">
import type { TimelineStep } from '@js/composables/wallet/useMovementTimeline'
import { formatOperationDate } from '@js/composables/wallet/useWalletOperationFormatters'

defineProps<{
  steps: TimelineStep[]
}>()
</script>

<template>
  <div class="mv-detail__timeline">
    <p class="mv-detail__timeline-title">Progreso</p>
    <ul class="mv-timeline">
      <li
        v-for="(step, i) in steps"
        :key="i"
        class="mv-timeline__item"
        :class="{
          'mv-timeline__item--done':     step.done && !step.rejected,
          'mv-timeline__item--active':   step.active,
          'mv-timeline__item--rejected': step.rejected,
        }"
      >
        <div class="mv-timeline__dot">
          <i :class="'fas ' + step.icon"></i>
        </div>
        <div class="mv-timeline__body">
          <p class="mv-timeline__label">{{ step.label }}</p>
          <p v-if="step.sub" class="mv-timeline__sub">{{ step.sub }}</p>
          <p v-if="step.date" class="mv-timeline__date">
            <i class="fas fa-clock mv-timeline__date-icon"></i>{{ formatOperationDate(step.date) }}
          </p>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.mv-detail__timeline { margin-bottom: 1rem; }
.mv-detail__timeline-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ds-text-muted);
  margin: 0 0 0.75rem;
}
.mv-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.mv-timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: var(--ds-border);
}
.mv-timeline__item {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 0 0 1.1rem 0;
  position: relative;
}
.mv-timeline__item:last-child { padding-bottom: 0; }

.mv-timeline__dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  color: var(--ds-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  border: 2px solid var(--ds-border);
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.mv-timeline__item--done .mv-timeline__dot {
  background: rgba(34, 197, 94, .18);
  color: #16a34a;
  border-color: rgba(34, 197, 94, .4);
}
.mv-timeline__item--active .mv-timeline__dot {
  background: rgba(124, 58, 237, .18);
  color: #7c3aed;
  border-color: rgba(124, 58, 237, .4);
  animation: mv-pulse-dot 1.6s ease-in-out infinite;
}
.mv-timeline__item--rejected .mv-timeline__dot {
  background: rgba(220, 38, 38, .18);
  color: #dc2626;
  border-color: rgba(220, 38, 38, .4);
}
@keyframes mv-pulse-dot {
  0%, 100% { box-shadow: 0 0 0 0 rgba(124,58,237,0.25); }
  50%       { box-shadow: 0 0 0 6px rgba(124,58,237,0); }
}

.mv-timeline__body { padding-top: 4px; flex: 1; }
.mv-timeline__label {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 0.15rem;
  line-height: 1.3;
}
.mv-timeline__item--active .mv-timeline__label { color: #7c3aed; }
.mv-timeline__item--rejected .mv-timeline__label { color: #dc2626; }

.mv-timeline__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0 0 0.2rem;
  line-height: 1.4;
}
.mv-timeline__date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}
.mv-timeline__date-icon { font-size: 0.62rem; }
</style>
