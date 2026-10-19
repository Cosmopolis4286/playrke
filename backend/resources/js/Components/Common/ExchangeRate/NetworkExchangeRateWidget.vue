<script setup lang="ts" name="NetworkExchangeRateWidget">
  /**
   * "Tasa de cambio de tu red": la tasa con la que se venden hoy las salas de
   * la comercializadora del usuario (su tasa propia si la fijó, si no la
   * oficial) — ver NetworkExchangeRateService. Se usa en los tableros de
   * comercializadora, agencia, vendedor y cliente, y arriba de la página de
   * tasas de la comercializadora (comparación propia vs. oficial).
   *
   * Se carga sola (GET /exchange-rates/network); si el usuario no tiene el
   * permiso, el widget no se muestra.
   */
  import { computed, onMounted } from 'vue'
  import { Link } from '@inertiajs/vue3'

  import DashboardSection from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
  import ExchangeRateTrendChart from '@/js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'

  import { useNetworkExchangeRate, type NetworkExchangeRate } from '@/js/composables/exchange-rate/useNetworkExchangeRate'

  const props = withDefaults(defineProps<{
    /** Oculta el acceso a la página de tasas (ej. cuando ya se está en ella). */
    hideManageLink?: boolean
  }>(), {
    hideManageLink: false,
  })

  const { data, loading, error, load } = useNetworkExchangeRate()
  onMounted(load)

  /** 403 → sin permiso: no se muestra nada. */
  const visible = computed(() => loading.value || !!error.value || !!data.value)

  const subtitle = computed(() => {
    if (!data.value) return 'Valores por 1 USD'
    return data.value.owner.type === 'commerce'
      ? `Salas de ${data.value.owner.name ?? 'tu comercializadora'} · por 1 USD`
      : 'Tasa oficial de la plataforma · por 1 USD'
  })

  const manageLabel = computed(() => (data.value?.can_manage ? 'Gestionar mi tasa' : 'Ver tasas'))
  const showManageLink = computed(() =>
    !props.hideManageLink && !!data.value && (data.value.can_manage || ['admin', 'superadmin', 'treasurer'].includes(data.value.role ?? ''))
  )

  function formatRate(value: number | null | undefined): string {
    if (value === null || value === undefined) return '—'
    return value.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 })
  }

  function signed(value: number): string {
    return `${value > 0 ? '+' : ''}${value.toLocaleString('es-VE', { maximumFractionDigits: 2 })}%`
  }

  function updatedAt(rate: NetworkExchangeRate): string | null {
    return rate.source === 'own' ? rate.own?.updated_at ?? null : rate.official?.updated_at ?? null
  }

  function trendClass(value: number | null | undefined): string {
    if (!value) return ''
    return value > 0 ? 'ner-trend--up' : 'ner-trend--down'
  }
</script>

<template>
  <DashboardSection
    v-if="visible"
    title="Tasa de cambio"
    icon="fas fa-money-bill-trend-up"
    :subtitle="subtitle"
    :loading="loading && !data"
    skeleton-variant="list"
  >
    <template v-if="showManageLink" #actions>
      <Link href="/exchange-rate" class="btn btn-sm btn-outline-primary text-nowrap">
        <i class="fas fa-pen-to-square mr-1"></i>{{ manageLabel }}
      </Link>
    </template>

    <DashboardEmptyState
      v-if="error"
      icon="fas fa-triangle-exclamation"
      :title="error"
      description="Vuelve a intentarlo en unos minutos."
      compact
    />

    <DashboardEmptyState
      v-else-if="data && !data.rates.length"
      icon="fas fa-money-bill-transfer"
      title="Sin tasas de cambio configuradas"
      description="Las ventas en moneda local necesitan una tasa activa."
      compact
    />

    <ul v-else-if="data" class="ner-list">
      <li v-for="rate in data.rates" :key="rate.currency_id" class="ner-item">
        <div class="ner-item__main">
          <div class="ner-item__head">
            <span class="ner-item__code">{{ rate.currency_code }}</span>
            <span
              class="badge ner-badge"
              :class="rate.source === 'own' ? 'ner-badge--own' : 'ner-badge--official'"
              :title="rate.source === 'own' ? 'Tasa fijada por la comercializadora' : 'Tasa oficial de la plataforma'"
            >
              <i class="fas mr-1" :class="rate.source === 'own' ? 'fa-store' : 'fa-building-columns'"></i>
              {{ rate.source === 'own' ? 'Propia' : 'Oficial' }}
            </span>
          </div>

          <div class="ner-item__value">
            <span class="ner-item__usd">1 USD =</span>
            {{ rate.symbol }} {{ formatRate(rate.effective) }}
          </div>

          <div class="ner-item__meta">
            <span v-if="rate.source === 'official' && rate.official?.percentage" class="ner-trend" :class="trendClass(rate.official.percentage)">
              <i class="fas" :class="rate.official.percentage > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'"></i>
              {{ signed(rate.official.percentage) }}
            </span>
            <span v-if="rate.source === 'own' && rate.official" class="ner-compare">
              Oficial {{ rate.symbol }} {{ formatRate(rate.official.amount) }}
              <span v-if="rate.deviation_pct" class="ner-trend" :class="trendClass(rate.deviation_pct)">
                ({{ signed(rate.deviation_pct) }})
              </span>
            </span>
            <span class="ner-updated">
              <RelativeTime :value="updatedAt(rate)" />
              <i v-if="rate.source === 'official' && rate.official?.source === 'system'" class="fas fa-robot ml-1" title="Actualización automática"></i>
            </span>
          </div>
        </div>

        <div v-if="rate.official && rate.official.history.length > 1" class="ner-item__spark" :title="`Tasa oficial ${rate.currency_code}: últimas actualizaciones`">
          <ExchangeRateTrendChart :data="rate.official.history" :height="40" compact />
        </div>
      </li>
    </ul>
  </DashboardSection>
</template>

<style scoped>
.ner-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.6rem; }

.ner-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.85rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
}
.ner-item__main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.ner-item__head { display: flex; align-items: center; gap: 0.5rem; }
.ner-item__code { font-size: 0.78rem; font-weight: 700; letter-spacing: 0.04em; color: var(--ds-text-2); }

.ner-badge { font-size: 0.65rem; font-weight: 600; }
.ner-badge--own { background: rgba(37,99,235,.15); color: #3b82f6; }
.ner-badge--official { background: var(--ds-badge-bg, rgba(106,134,172,.15)); color: var(--ds-text-2); }

.ner-item__value { font-size: 1.2rem; font-weight: 700; color: var(--ds-text); line-height: 1.25; }
.ner-item__usd { font-size: 0.78rem; font-weight: 500; color: var(--ds-text-muted); margin-right: 0.15rem; }

.ner-item__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem 0.6rem; font-size: 0.75rem; color: var(--ds-text-muted); }
.ner-trend { font-weight: 600; }
.ner-trend--up { color: #dc2626; }
.ner-trend--down { color: #16a34a; }
.ner-updated { display: inline-flex; align-items: center; }

.ner-item__spark { width: 110px; flex-shrink: 0; }
@media (max-width: 576px) {
  .ner-item__spark { display: none; }
}
</style>
