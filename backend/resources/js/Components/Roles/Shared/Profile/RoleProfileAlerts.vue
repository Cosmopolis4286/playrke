<script setup lang="ts">
import type { RoleProfileAlert } from '@/js/types/roles/shared/role-profile-common'

/** Avisos del estado de la cuenta (jerarquía inactiva, comisión fuera de tope, red vacía…) — generados en el backend. */
defineProps<{ alerts: RoleProfileAlert[] }>()
</script>

<template>
  <div v-if="alerts.length" class="rp-alerts" role="list">
    <div v-for="alert in alerts" :key="alert.title" :class="['rp-alert', `rp-alert--${alert.level}`]" role="listitem">
      <span class="rp-alert__icon"><i :class="['fas', alert.icon]" aria-hidden="true"></i></span>
      <div class="rp-alert__body">
        <strong class="rp-alert__title">{{ alert.title }}</strong>
        <span class="rp-alert__message">{{ alert.message }}</span>
      </div>
      <a v-if="alert.action" :href="alert.action.href" class="rp-alert__action">
        {{ alert.action.label }} <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</template>

<style scoped>
.rp-alerts { display: flex; flex-direction: column; gap: 0.6rem; }
.rp-alert {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  border: 1px solid transparent;
}
.rp-alert--danger  { background: rgba(225, 29, 72, 0.09);  border-color: rgba(225, 29, 72, 0.3);  --rp-alert-accent: #f43f5e; }
.rp-alert--warning { background: rgba(217, 119, 6, 0.09);  border-color: rgba(217, 119, 6, 0.3);  --rp-alert-accent: #f59e0b; }
.rp-alert--info    { background: rgba(102, 126, 234, 0.08); border-color: rgba(102, 126, 234, 0.28); --rp-alert-accent: #7c8cf0; }
.rp-alert__icon {
  width: 34px; height: 34px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
  color: var(--rp-alert-accent);
  background: color-mix(in srgb, var(--rp-alert-accent) 16%, transparent);
}
.rp-alert__body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
.rp-alert__title { font-size: 0.86rem; color: var(--ds-text); }
.rp-alert__message { font-size: 0.8rem; color: var(--ds-text-2); line-height: 1.4; }
.rp-alert__action {
  flex-shrink: 0;
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 6px 12px; border-radius: 8px;
  font-size: 0.78rem; font-weight: 700;
  color: var(--rp-alert-accent);
  border: 1.5px solid currentColor;
  text-decoration: none;
  white-space: nowrap;
}
.rp-alert__action:hover { background: color-mix(in srgb, var(--rp-alert-accent) 12%, transparent); text-decoration: none; color: var(--rp-alert-accent); }

@media (max-width: 575.98px) {
  .rp-alert { flex-wrap: wrap; }
  .rp-alert__action { margin-left: calc(34px + 0.85rem); }
}
</style>
