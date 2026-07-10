<script setup lang="ts" name="DashboardCommissionPanel">
  defineProps<{
    commission?: number | string | null
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
  </div>
  <div v-else class="dcp-empty">
    <i class="fas fa-percentage"></i>
    <p class="mb-0">Información de comisión no disponible.</p>
  </div>
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
  color: #6c757d;
  font-weight: 500;
  text-align: center;
}

.dcp-hint {
  font-size: 0.76rem;
  color: #8a9bb0;
  margin: 0;
  padding: 0.5rem 0.75rem;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #0dcaf0;
}

.dcp-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: #adb5bd;
  font-size: 0.85rem;
  text-align: center;
}
.dcp-empty i { font-size: 2rem; }
</style>
