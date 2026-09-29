<script setup lang="ts" name="ExchangeRateTrendChart">
  /**
   * Sparkline de tendencia de una tasa de cambio a lo largo del tiempo.
   * Reutilizado por Historical.vue (histórico completo de una moneda) y
   * ExchangeRateSection.vue (widget del dashboard SuperAdmin) — antes cada
   * uno mostraba únicamente el número + badge de %, sin ninguna
   * visualización temporal (ver auditoría del dominio exchange-rate).
   *
   * Mismo patrón que DashboardTrendChart.vue (ApexCharts + CSS vars del
   * design system para seguir el tema claro/oscuro), adaptado a una serie
   * de `amount` en el tiempo en vez de conteos por día.
   */
  import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
  import VueApexCharts from 'vue3-apexcharts'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import type { ApexOptions } from 'apexcharts'

  export interface ExchangeRateTrendPoint {
    /** ISO string o cualquier valor parseable por `new Date()`. */
    date: string
    amount: number
  }

  const props = withDefaults(defineProps<{
    /** Orden cronológico esperado: más antiguo primero. Si el caller trae
     *  datos en orden descendente (ej. getHistoryByCurrency, más reciente
     *  primero), pasar reverse=true en vez de invertir el array a mano. */
    data: ExchangeRateTrendPoint[]
    label?: string
    /** Invierte `data` antes de graficar — usar cuando la fuente viene
     *  ordenada más-reciente-primero (ver getHistoryByCurrency). */
    reverse?: boolean
    height?: number
    /** Sparkline sin ejes/grid/tooltip — para espacios muy reducidos (ej.
     *  una fila de lista, ver ExchangeRateSection.vue). El modo completo
     *  (default) es para Historical.vue, con espacio dedicado. */
    compact?: boolean
  }>(), {
    label: 'Valor',
    reverse: false,
    height: 180,
    compact: false,
  })

  const themeTick = ref(0)
  let observer: MutationObserver | null = null

  onMounted(() => {
    observer = new MutationObserver(() => { themeTick.value++ })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
  })
  onBeforeUnmount(() => observer?.disconnect())

  function cssVar(name: string, fallback: string): string {
    themeTick.value
    if (typeof window === 'undefined') return fallback
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim()
    return value || fallback
  }

  const orderedData = computed(() => props.reverse ? [...props.data].reverse() : props.data)

  const categories = computed(() => orderedData.value.map(d => d.date))
  const series = computed(() => [{ name: props.label, data: orderedData.value.map(d => d.amount) }])

  const isLightTheme = computed(() => {
    themeTick.value
    return typeof document !== 'undefined' && document.documentElement.getAttribute('data-theme') === 'light'
  })

  /**
   * Verde si la tasa subió entre el primer y el último punto visibles, rojo
   * si bajó, neutro (acento por defecto) si no hay cambio o solo hay 1
   * punto — misma semántica de color que el badge de % ya usado en
   * Historical.vue/ExchangeRateSection.vue, ahora también en el trazo.
   */
  const trendDirection = computed<'up' | 'down' | 'flat'>(() => {
    const points = orderedData.value
    if (points.length < 2) return 'flat'
    const first = points[0].amount
    const last = points[points.length - 1].amount
    if (last > first) return 'up'
    if (last < first) return 'down'
    return 'flat'
  })

  const chartOptions = computed<ApexOptions>(() => {
    const textMuted = cssVar('--ds-text-2', '#8a94a6')
    const border = cssVar('--ds-border', '#2a3040')
    const colorByTrend = { up: '#22c55e', down: '#ef4444', flat: '#667eea' }
    const accent = colorByTrend[trendDirection.value]

    if (props.compact) {
      return {
        chart: {
          type: 'area',
          height: props.height,
          toolbar: { show: false },
          zoom: { enabled: false },
          sparkline: { enabled: true },
          animations: { enabled: false },
        },
        colors: [accent],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 1.5 },
        fill: {
          type: 'gradient',
          gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] },
        },
        tooltip: { enabled: false },
      }
    }

    return {
      chart: {
        type: 'area',
        height: props.height,
        toolbar: { show: false },
        zoom: { enabled: false },
        foreColor: textMuted,
        fontFamily: 'inherit',
        sparkline: { enabled: false },
      },
      colors: [accent],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] },
      },
      grid: { borderColor: border, strokeDashArray: 4 },
      xaxis: {
        categories: categories.value,
        type: 'datetime',
        labels: { style: { fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: {
        labels: {
          style: { fontSize: '11px' },
          formatter: (value: number) => value.toLocaleString('es-VE', { maximumFractionDigits: 2 }),
        },
        forceNiceScale: true,
      },
      tooltip: {
        theme: isLightTheme.value ? 'light' : 'dark',
        x: { format: 'dd MMM yyyy HH:mm' },
      },
    }
  })

  const hasData = computed(() => orderedData.value.length > 1)
</script>

<template>
  <VueApexCharts
    v-if="hasData"
    type="area"
    :height="height"
    :options="chartOptions"
    :series="series"
  />
  <DashboardEmptyState
    v-else-if="!compact"
    icon="fas fa-chart-line"
    title="Sin historial suficiente"
    description="La tendencia aparecerá en cuanto haya al menos dos registros de esta tasa."
  />
</template>
