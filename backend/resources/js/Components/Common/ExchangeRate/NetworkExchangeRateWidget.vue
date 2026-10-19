<script setup lang="ts" name="NetworkExchangeRateWidget">
  /**
   * "Tasa de cambio de tu red": la tasa con la que se venden hoy las salas de
   * la comercializadora del usuario (su tasa propia si la fijó, si no la
   * oficial) — ver NetworkExchangeRateService. Se usa en los tableros de
   * comercializadora, agencia, vendedor y cliente, y en la página de tasas
   * (modo `bare`, sin tarjeta propia).
   *
   * Cuadrícula de mosaicos compactos: una moneda por mosaico, que se reparten
   * solos según el ancho disponible. Se carga sola (GET /exchange-rates/network);
   * si el usuario no tiene el permiso, el widget no se muestra.
   */
  import { computed, onMounted } from 'vue'
  import { Link } from '@inertiajs/vue3'

  import DashboardSection from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import ExchangeRateTrendChart from '@/js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'

  import { useNetworkExchangeRate, type NetworkExchangeRate } from '@/js/composables/exchange-rate/useNetworkExchangeRate'

  const props = withDefaults(defineProps<{
    /** Oculta el acceso a la página de tasas (ej. cuando ya se está en ella). */
    hideManageLink?: boolean
    /** Solo la cuadrícula, sin la tarjeta DashboardSection (para incrustar). */
    bare?: boolean
  }>(), {
    hideManageLink: false,
    bare: false,
  })

  const { data, loading, error, load } = useNetworkExchangeRate()
  onMounted(load)

  defineExpose({ reload: load })

  /** 403 → sin permiso: no se muestra nada. */
  const visible = computed(() => loading.value || !!error.value || !!data.value)

  const subtitle = computed(() => {
    if (!data.value) return 'Por 1 USD'
    if (data.value.owner.type === 'customer') return 'Tus salas · por 1 USD'
    return data.value.owner.type === 'commerce'
      ? `Salas de ${data.value.owner.name ?? 'tu comercializadora'} · por 1 USD`
      : 'Oficial de la plataforma · por 1 USD'
  })

  const manageLabel = computed(() => (data.value?.can_manage ? 'Gestionar' : 'Ver tasas'))
  const showManageLink = computed(() =>
    !props.hideManageLink && !!data.value && (data.value.can_manage || ['admin', 'superadmin', 'treasurer'].includes(data.value.role ?? ''))
  )

  function formatRate(value: number | null | undefined): string {
    if (value === null || value === undefined) return '—'
    return value.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: value >= 100 ? 2 : 4 })
  }

  function signed(value: number): string {
    return `${value > 0 ? '+' : ''}${value.toLocaleString('es-VE', { maximumFractionDigits: 2 })}%`
  }

  /** "2 d", "16 h", "5 min" — versión corta para el mosaico. */
  function ago(iso: string | null | undefined): string {
    if (!iso) return '—'
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60_000))
    if (minutes < 60) return `${minutes} min`
    const hours = Math.round(minutes / 60)
    return hours < 48 ? `${hours} h` : `${Math.round(hours / 24)} d`
  }

  function updatedAt(rate: NetworkExchangeRate): string | null {
    return rate.source === 'own' ? rate.own?.updated_at ?? null : rate.official?.updated_at ?? null
  }

  /** Variación a mostrar: la de la oficial, o el desvío de la propia. */
  function delta(rate: NetworkExchangeRate): number | null {
    if (rate.source === 'own') return rate.deviation_pct
    return rate.official?.percentage || null
  }

  function deltaTitle(rate: NetworkExchangeRate): string {
    return rate.source === 'own' ? 'Diferencia con la tasa oficial' : 'Variación respecto a la anterior'
  }
</script>

<template>
  <component
    :is="bare ? 'div' : DashboardSection"
    v-if="visible"
    v-bind="bare ? {} : {
      title: 'Tasa de cambio',
      icon: 'fas fa-money-bill-trend-up',
      subtitle,
      loading: loading && !data,
      skeletonVariant: 'list',
    }"
  >
    <template v-if="!bare && showManageLink" #actions>
      <Link href="/exchange-rate" class="btn btn-xs btn-outline-primary text-nowrap ner-manage">
        <i class="fas fa-pen-to-square mr-1"></i>{{ manageLabel }}
      </Link>
    </template>

    <div v-if="bare && loading && !data" class="ner-grid">
      <div v-for="i in 3" :key="i" class="ner-tile ner-tile--skeleton skeleton-block"></div>
    </div>

    <DashboardEmptyState
      v-else-if="error"
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

    <div v-else-if="data" class="ner-grid">
      <div
        v-for="rate in data.rates"
        :key="rate.currency_id"
        class="ner-tile"
        :class="{ 'ner-tile--own': rate.source === 'own', 'ner-tile--warn': rate.stale || rate.deviation_alert }"
      >
        <div class="ner-tile__top">
          <span class="ner-tile__code" :title="rate.currency_name">{{ rate.currency_code }}</span>
          <span
            class="ner-chip"
            :class="rate.source === 'own' ? 'ner-chip--own' : 'ner-chip--official'"
            :title="rate.source === 'own' ? 'Tasa propia del dueño de las salas' : 'Tasa oficial de la plataforma'"
          >{{ rate.source === 'own' ? 'Propia' : 'Oficial' }}</span>
          <i v-if="rate.stale" class="fas fa-clock ner-warn" title="Tasa propia desactualizada"></i>
          <i v-if="rate.deviation_alert" class="fas fa-triangle-exclamation ner-warn" title="Se aleja mucho de la oficial"></i>
          <span
            v-if="rate.official && rate.official.history.length > 1"
            class="ner-tile__spark"
            :title="`Tasa oficial ${rate.currency_code}: últimas actualizaciones`"
          >
            <ExchangeRateTrendChart :data="rate.official.history" :height="22" compact />
          </span>
        </div>

        <div class="ner-tile__value">
          <span class="ner-tile__symbol">{{ rate.symbol }}</span>{{ formatRate(rate.effective) }}
        </div>

        <div class="ner-tile__meta">
          <span
            v-if="delta(rate)"
            class="ner-delta"
            :class="(delta(rate) ?? 0) > 0 ? 'ner-delta--up' : 'ner-delta--down'"
            :title="deltaTitle(rate)"
          >{{ signed(delta(rate) ?? 0) }}</span>
          <span v-if="rate.source === 'own' && rate.official" :title="'Oficial: ' + rate.symbol + ' ' + formatRate(rate.official.amount)">
            of. {{ formatRate(rate.official.amount) }}
          </span>
          <span class="ner-tile__time" :title="updatedAt(rate) ? new Date(updatedAt(rate) as string).toLocaleString('es-VE') : ''">
            <i v-if="rate.source === 'official' && rate.official?.source === 'system'" class="fas fa-robot" title="Actualización automática"></i>
            {{ ago(updatedAt(rate)) }}
          </span>
        </div>

        <div
          v-if="data.can_manage && rate.allowed_range"
          class="ner-tile__range"
          :title="`Tope del administrador: ±${rate.allowed_range.max_pct}% de la oficial`"
        >
          Permitido {{ formatRate(rate.allowed_range.min) }} – {{ formatRate(rate.allowed_range.max) }}
        </div>
      </div>
    </div>
  </component>
</template>

<style scoped>
.ner-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 0.5rem;
}

.ner-tile {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
}
.ner-tile--own { border-left: 3px solid #3b82f6; }
.ner-tile--warn { border-color: rgba(217,119,6,.55); }
.ner-tile--skeleton { height: 74px; }

.ner-tile__top { display: flex; align-items: center; gap: 0.35rem; min-height: 22px; }
.ner-tile__code { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; color: var(--ds-text-2); }
.ner-tile__spark { margin-left: auto; width: 56px; flex-shrink: 0; }

.ner-chip { font-size: 0.6rem; font-weight: 600; line-height: 1; padding: 0.15rem 0.35rem; border-radius: 999px; }
.ner-chip--own { background: rgba(59,130,246,.15); color: #3b82f6; }
.ner-chip--official { background: rgba(106,134,172,.15); color: var(--ds-text-muted); }
.ner-warn { font-size: 0.7rem; color: #d97706; }

.ner-tile__value {
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.25;
  color: var(--ds-text);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ner-tile__symbol { font-size: 0.75em; font-weight: 600; color: var(--ds-text-muted); margin-right: 0.2rem; }

.ner-tile__meta { display: flex; align-items: center; gap: 0.45rem; font-size: 0.68rem; color: var(--ds-text-muted); white-space: nowrap; }
.ner-tile__time { margin-left: auto; display: inline-flex; align-items: center; gap: 0.2rem; }
.ner-delta { font-weight: 700; }
.ner-delta--up { color: #dc2626; }
.ner-delta--down { color: #16a34a; }

.ner-tile__range { font-size: 0.64rem; color: var(--ds-text-muted); border-top: 1px dashed var(--ds-border); margin-top: 0.2rem; padding-top: 0.2rem; }

.ner-manage { font-size: 0.72rem; padding: 0.15rem 0.5rem; }
</style>
