<script setup lang="ts" name="DashboardCommissionPanel">
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

  defineProps<{
    commission?: number | string | null
    /** A quién reporta el vendedor (Commerce directo o Agencia/Subsidiary) — opcional, solo aplica a Seller. */
    reportsTo?: { name: string; type: 'commerce' | 'subsidiary'; commerce_name: string } | null
  }>()
</script>

<template>
  <div v-if="commission" class="dcp-panel">
    <div class="dcp-rate">
      <span class="dcp-value">{{ commission }}<small>%</small></span>
      <span class="dcp-label">Tasa de comisión configurada</span>
    </div>
    <p class="dcp-hint">
      <i class="fas fa-info-circle me-1"></i>
      Tu comisión se calcula sobre el total de cartones vendidos cada período.
    </p>
    <p v-if="reportsTo" class="dcp-reports-to">
      <i :class="reportsTo.type === 'subsidiary' ? 'fas fa-code-branch' : 'fas fa-building'" class="me-1"></i>
      Reportas a <strong>{{ reportsTo.name }}</strong>
      <template v-if="reportsTo.type === 'subsidiary'"> (agencia de {{ reportsTo.commerce_name }})</template>
    </p>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-percentage"
    title="Información de comisión no disponible"
    description="Contacta a tu comercio o administrador para configurar tu tasa."
  />
</template>

<style scoped>
.dcp-panel {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.dcp-rate {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.25rem 1rem;
  background: linear-gradient(135deg, #e7f0ff 0%, #f0fff4 100%);
  border-radius: 10px;
  gap: 0.25rem;
}

.dcp-value {
  font-size: 2.8rem;
  font-weight: 800;
  color: #0d6efd;
  line-height: 1;
}

.dcp-value small {
  font-size: 1.4rem;
  font-weight: 700;
  color: #198754;
  margin-left: 2px;
}

.dcp-label {
  font-size: 0.75rem;
  color: var(--ds-text-2);
  font-weight: 500;
  text-align: center;
}

.dcp-hint {
  font-size: 0.76rem;
  color: var(--ds-text-2);
  margin: 0;
  padding: 0.5rem 0.75rem;
  background: var(--ds-surface-2);
  border-radius: 6px;
  border-left: 3px solid #0dcaf0;
}

.dcp-reports-to {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
  text-align: center;
}
.dcp-reports-to strong { color: var(--ds-text); }
</style>
