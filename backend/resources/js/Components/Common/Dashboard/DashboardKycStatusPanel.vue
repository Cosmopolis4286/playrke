<script setup lang="ts">
import type { KycStatus } from '@/js/types/roles/player/dashboard/dashboard-player'

defineProps<{
  status: KycStatus | null
}>()

const statusConfig: Record<string, { label: string; icon: string; class: string }> = {
  pending:  { label: 'En revisión', icon: 'fas fa-clock',        class: 'ksp-badge--pending' },
  approved: { label: 'Verificado',  icon: 'fas fa-check-circle', class: 'ksp-badge--approved' },
  rejected: { label: 'Rechazado',   icon: 'fas fa-times-circle', class: 'ksp-badge--rejected' },
}
</script>

<template>
  <div v-if="status" class="ksp-panel">
    <div class="ksp-badge" :class="statusConfig[status.status]?.class ?? 'ksp-badge--pending'">
      <i :class="statusConfig[status.status]?.icon ?? 'fas fa-clock'"></i>
      {{ statusConfig[status.status]?.label ?? status.status }}
    </div>
    <p v-if="status.status === 'rejected' && status.rejection_reason" class="ksp-reason">
      <i class="fas fa-info-circle me-1"></i>{{ status.rejection_reason }}
    </p>
    <a v-if="status.status === 'rejected'" href="/kyc" class="ksp-link">Volver a enviar documento →</a>
  </div>
  <div v-else class="ksp-empty">
    <i class="fas fa-id-card"></i>
    <p class="mb-1 fw-semibold">Sin verificación de identidad</p>
    <p class="mb-2 text-muted small">Verifica tu cuenta para desbloquear todas las funciones.</p>
    <a href="/kyc" class="ksp-link">Verificar ahora →</a>
  </div>
</template>

<style scoped>
.ksp-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  padding: 1rem;
  text-align: center;
}

.ksp-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 700;
}
.ksp-badge--pending  { background: rgba(37,99,235,.15);  color: #60a5fa; }
.ksp-badge--approved { background: rgba(22,163,74,.15);  color: #4ade80; }
.ksp-badge--rejected { background: rgba(239,68,68,.15);  color: #f87171; }

.ksp-reason {
  font-size: 0.8rem;
  color: var(--ds-text-2);
  margin: 0;
}

.ksp-link {
  font-size: 0.8rem;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
}
.ksp-link:hover { text-decoration: underline; }

.ksp-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  padding: 1.5rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  text-align: center;
}
.ksp-empty i { font-size: 2rem; opacity: 0.7; margin-bottom: 0.25rem; }
</style>
