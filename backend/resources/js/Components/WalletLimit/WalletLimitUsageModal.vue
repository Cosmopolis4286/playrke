<script setup lang="ts">
  import { computed } from 'vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ProgressBar from '@/js/Components/ui/feedback/ProgressBar.vue'
  import type { WalletLimit, WalletLimitUsage } from '@/js/types/walletLimit'

  const props = defineProps<{
    modelValue: boolean
    limit: WalletLimit | null
    usage: WalletLimitUsage | null
    loading: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  const isOpen = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value)
  })

  const currencyLabel = computed(() => props.limit?.currency?.iso_code ?? 'Todas las monedas')

  const decimals = computed(() => props.limit?.currency?.decimal_places ?? 2)

  function formatAmount(value: number): string {
    return new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: decimals.value,
      maximumFractionDigits: decimals.value
    }).format(value)
  }

  function percentage(used: number, max: number): number {
    return max > 0 ? Math.min(100, Math.max(0, (used / max) * 100)) : 0
  }

  function colorFor(pct: number): 'success' | 'warning' | 'danger' {
    if (pct >= 90) return 'danger'
    if (pct >= 70) return 'warning'
    return 'success'
  }

  const periods = computed(() => {
    if (!props.usage) return []

    return [
      { key: 'daily',   label: 'Hoy',        data: props.usage.daily },
      { key: 'weekly',  label: 'Esta semana', data: props.usage.weekly },
      { key: 'monthly', label: 'Este mes',   data: props.usage.monthly }
    ]
  })
</script>

<template>
  <Modal
    v-model="isOpen"
    title="Uso real de este límite"
    icon="fa-chart-line"
    size="md"
  >
    <div v-if="loading" class="text-center py-4 text-muted">
      <i class="fas fa-spinner fa-spin fa-fw"></i> Cargando consumo real...
    </div>

    <div v-else-if="!usage" class="text-center py-4 text-muted">
      No hay datos de consumo disponibles todavía.
    </div>

    <div v-else>
      <p class="text-muted small mb-3">
        Retiros de <strong>{{ currencyLabel }}</strong> /
        Nivel KYC <strong>{{ limit?.kyc_level }}</strong>
        acumulados por la plataforma frente al tope configurado.
      </p>

      <div v-for="period in periods" :key="period.key" class="mb-3">
        <div class="d-flex justify-content-between align-items-baseline mb-1">
          <span class="fw-semibold">{{ period.label }}</span>
          <span class="small text-muted">
            {{ formatAmount(period.data.used) }} / {{ formatAmount(period.data.max) }}
          </span>
        </div>
        <ProgressBar
          :value="period.data.used"
          :max="period.data.max"
          :color="colorFor(percentage(period.data.used, period.data.max))"
        />
      </div>

      <div class="text-muted small mt-3 pt-2 border-top">
        <i class="fas fa-info-circle fa-fw"></i>
        {{ usage.transaction_count }} retiro(s) contabilizado(s) este mes.
      </div>
    </div>
  </Modal>
</template>
