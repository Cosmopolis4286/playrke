<script setup lang="ts" name="DashboardTrendChart">
  import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
  import VueApexCharts from 'vue3-apexcharts'
  import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'
  import type { ApexOptions } from 'apexcharts'

  const props = withDefaults(defineProps<{
    data: { date: string; count: number }[]
    label?: string
  }>(), {
    label: 'Cartones vendidos',
  })

  // Lee las CSS vars del design system para que el gráfico siga el theme
  // claro/oscuro activo (controlado vía [data-theme] en <html>) sin duplicar
  // la paleta en JS.
  const themeTick = ref(0)
  let observer: MutationObserver | null = null

  onMounted(() => {
    observer = new MutationObserver(() => { themeTick.value++ })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
  })
  onBeforeUnmount(() => observer?.disconnect())

  function cssVar(name: string, fallback: string): string {
    themeTick.value // dependencia reactiva
    if (typeof window === 'undefined') return fallback
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim()
    return value || fallback
  }

  const categories = computed(() => props.data.map((d) => d.date))
  const series = computed(() => [{ name: props.label, data: props.data.map((d) => d.count) }])

  const isLightTheme = computed(() => {
    themeTick.value // dependencia reactiva
    return typeof document !== 'undefined' && document.documentElement.getAttribute('data-theme') === 'light'
  })

  const chartOptions = computed<ApexOptions>(() => {
    const textMuted = cssVar('--ds-text-2', '#8a94a6')
    const border = cssVar('--ds-border', '#2a3040')
    const accent = '#667eea'

    return {
      chart: {
        type: 'area',
        height: 220,
        toolbar: { show: false },
        zoom: { enabled: false },
        foreColor: textMuted,
        fontFamily: 'inherit',
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
        labels: {
          style: { fontSize: '11px' },
          formatter: (value: string) => value?.slice(5) ?? value, // MM-DD
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: {
        labels: { style: { fontSize: '11px' } },
        min: 0,
        forceNiceScale: true,
      },
      tooltip: { theme: isLightTheme.value ? 'light' : 'dark' },
    }
  })

  /**
   * "Sin datos" no es que el array venga vacío — buildSalesTrend() siempre
   * rellena los N días del período con count:0 (ver backend) — sino que
   * ningún día del período tuvo ventas. Un gráfico plano en cero no aporta
   * nada frente a un mensaje claro.
   */
  const hasData = computed(() => props.data.some((d) => d.count > 0))
</script>

<template>
  <VueApexCharts
    v-if="hasData"
    type="area"
    height="220"
    :options="chartOptions"
    :series="series"
  />
  <DashboardEmptyState
    v-else
    icon="fas fa-chart-area"
    title="Sin datos de tendencia todavía"
    description="El gráfico aparecerá en cuanto haya ventas registradas en el período."
  />
</template>
