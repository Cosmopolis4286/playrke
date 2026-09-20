<script setup lang="ts" name="DashboardMultiTrendChart">
  import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
  import VueApexCharts from 'vue3-apexcharts'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import type { ApexOptions } from 'apexcharts'

  /**
   * Variante multi-serie de DashboardTrendChart.vue (que solo acepta una
   * serie de conteos) — necesaria cuando el gráfico combina series de
   * naturaleza distinta (conteos vs. minutos), con hasta 2 ejes Y. No se
   * extendió DashboardTrendChart.vue directamente para no arriesgar
   * regresión visual en sus 5 consumidores ya estables (dashboards de rol),
   * que no necesitan multi-serie.
   */
  const props = withDefaults(defineProps<{
    categories: string[]
    series: { name: string; data: (number | null)[]; yAxisIndex?: number }[]
    /** Etiqueta del eje Y secundario (ej. "minutos") — solo se muestra si alguna serie usa yAxisIndex: 1. */
    secondaryAxisLabel?: string
  }>(), {
    secondaryAxisLabel: '',
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

  const isLightTheme = computed(() => {
    themeTick.value
    return typeof document !== 'undefined' && document.documentElement.getAttribute('data-theme') === 'light'
  })

  const hasSecondaryAxis = computed(() => props.series.some(s => s.yAxisIndex === 1))

  const PALETTE = ['#4ade80', '#f87171', '#667eea', '#fbbf24', '#60a5fa']

  const chartOptions = computed<ApexOptions>(() => {
    const textMuted = cssVar('--ds-text-2', '#8a94a6')
    const border = cssVar('--ds-border', '#2a3040')

    return {
      chart: {
        type: 'line',
        height: 260,
        toolbar: { show: false },
        zoom: { enabled: false },
        foreColor: textMuted,
        fontFamily: 'inherit',
      },
      colors: PALETTE.slice(0, props.series.length),
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      legend: { position: 'top', fontSize: '12px' },
      grid: { borderColor: border, strokeDashArray: 4 },
      xaxis: {
        categories: props.categories,
        labels: {
          style: { fontSize: '11px' },
          formatter: (value: string) => value?.slice(5) ?? value, // MM-DD
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: hasSecondaryAxis.value ? [
        { title: { text: 'Movimientos', style: { fontSize: '11px' } }, labels: { style: { fontSize: '11px' } }, min: 0, forceNiceScale: true },
        { opposite: true, title: { text: props.secondaryAxisLabel, style: { fontSize: '11px' } }, labels: { style: { fontSize: '11px' } }, min: 0, forceNiceScale: true },
      ] : {
        labels: { style: { fontSize: '11px' } },
        min: 0,
        forceNiceScale: true,
      },
      tooltip: { theme: isLightTheme.value ? 'light' : 'dark' },
    }
  })

  const hasData = computed(() => props.series.some(s => s.data.some(v => (v ?? 0) > 0)))
</script>

<template>
  <VueApexCharts
    v-if="hasData"
    type="line"
    height="260"
    :options="chartOptions"
    :series="series"
  />
  <DashboardEmptyState
    v-else
    icon="fas fa-chart-line"
    title="Sin datos de tendencia todavía"
    description="El gráfico aparecerá en cuanto haya movimientos procesados en el período."
  />
</template>
