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

  const isShared = computed(() => props.usage?.is_shared_across_currencies ?? false)

  function formatAmount(value: number, decimals: number): string {
    return new Intl.NumberFormat('es-VE', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
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

  const PERIOD_LABELS = { daily: 'Hoy', weekly: 'Esta semana', monthly: 'Este mes' } as const

  /**
   * Límite propio de una sola moneda: un total agregado tiene sentido porque
   * todo el consumo ya es de esa misma unidad.
   */
  const periods = computed(() => {
    if (!props.usage || isShared.value) return []

    return [
      { key: 'daily',   label: PERIOD_LABELS.daily,   data: props.usage.daily,   decimals: props.limit?.currency?.decimal_places ?? 2 },
      { key: 'weekly',  label: PERIOD_LABELS.weekly,  data: props.usage.weekly,  decimals: props.limit?.currency?.decimal_places ?? 2 },
      { key: 'monthly', label: PERIOD_LABELS.monthly, data: props.usage.monthly, decimals: props.limit?.currency?.decimal_places ?? 2 }
    ]
  })

  /**
   * Fallback global: el mismo tope numérico se aplica de forma INDEPENDIENTE
   * a cada moneda (WalletLimitService::assertWithinLimits compara los retiros
   * de UNA wallet, nunca la suma de varias monedas) — así que se desglosa por
   * moneda, cada una comparada contra max_daily/weekly/monthly, en vez de un
   * total que mezclaría USDT con VES como si fueran la misma unidad.
   */
  const byCurrencyPeriods = computed(() => {
    if (!props.usage || !isShared.value) return []

    return props.usage.by_currency.map(c => ({
      iso_code: c.iso_code,
      symbol: c.symbol,
      transaction_count: c.transaction_count,
      periods: [
        { key: 'daily',   label: PERIOD_LABELS.daily,   used: c.daily,   max: props.usage!.daily.max },
        { key: 'weekly',  label: PERIOD_LABELS.weekly,  used: c.weekly,  max: props.usage!.weekly.max },
        { key: 'monthly', label: PERIOD_LABELS.monthly, used: c.monthly, max: props.usage!.monthly.max }
      ].map(p => ({ ...p, decimals: c.decimal_places }))
    }))
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

      <!-- Límite de una sola moneda: total agregado tiene sentido -->
      <template v-if="!isShared">
        <div v-for="period in periods" :key="period.key" class="mb-3">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <span class="fw-semibold">{{ period.label }}</span>
            <span class="small text-muted">
              {{ formatAmount(period.data.used, period.decimals) }} / {{ formatAmount(period.data.max, period.decimals) }}
            </span>
          </div>
          <ProgressBar
            :value="period.data.used"
            :max="period.data.max"
            :color="colorFor(percentage(period.data.used, period.data.max))"
          />
        </div>
      </template>

      <!-- Fallback compartido por varias monedas: el mismo tope se aplica
           independientemente a cada una, así que se desglosa por moneda en
           vez de sumar unidades distintas en un solo número -->
      <template v-else>
        <div class="alert alert-warning py-2 px-3 small mb-3">
          <i class="fas fa-triangle-exclamation fa-fw"></i>
          Este tope aplica <strong>por separado</strong> a cada moneda sin límite propio.
          Sumarlas mezclaría escalas muy distintas — se muestra el consumo de cada una.
        </div>

        <div v-if="!usage.by_currency.length" class="text-center text-muted small py-3">
          Sin retiros registrados este mes en ninguna moneda bajo este tope.
        </div>

        <div v-for="entry in byCurrencyPeriods" :key="entry.iso_code" class="mb-4">
          <div class="fw-semibold mb-2">
            <span class="badge badge-info me-1">{{ entry.iso_code }}</span>
            <span class="small text-muted">{{ entry.transaction_count }} retiro(s) este mes</span>
          </div>

          <div v-for="period in entry.periods" :key="period.key" class="mb-2">
            <div class="d-flex justify-content-between align-items-baseline mb-1">
              <span class="small">{{ period.label }}</span>
              <span class="small text-muted">
                {{ entry.symbol }} {{ formatAmount(period.used, period.decimals) }} / {{ entry.symbol }} {{ formatAmount(period.max, period.decimals) }}
              </span>
            </div>
            <ProgressBar
              :value="period.used"
              :max="period.max"
              size="small"
              :color="colorFor(percentage(period.used, period.max))"
            />
          </div>
        </div>
      </template>

      <div v-if="!isShared" class="text-muted small mt-3 pt-2 border-top">
        <i class="fas fa-info-circle fa-fw"></i>
        {{ usage.transaction_count }} retiro(s) contabilizado(s) este mes.
      </div>
    </div>
  </Modal>
</template>
