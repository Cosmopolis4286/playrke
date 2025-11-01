<template>
  <div class="relative h-80">
    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="h-80 flex items-center justify-center"
    >
      <LoadingSpinner
        type="spinner"
        size="lg"
        color="primary"
        text="Cargando datos del gráfico..."
      />
    </div>

    <!-- Chart -->
    <canvas
      v-else-if="hasData"
      ref="chartCanvas"
      class="w-full h-full"
      role="img"
      :aria-label="chartAriaLabel"
    />

    <!-- No Data State -->
    <div
      v-else
      class="absolute inset-0 flex flex-col items-center justify-center text-center"
    >
      <SharedIcon
        name="chart-bar"
        size="2xl"
        color="muted"
        class="mb-4"
      />
      <h4 class="heading-6 text-secondary-600 mb-2">
        Sin datos disponibles
      </h4>
      <p class="text-sm text-secondary-500">
        No hay información de ingresos para el período seleccionado
      </p>
    </div>

    <!-- Error State -->
    <div
      v-if="error"
      class="absolute bottom-4 right-4 bg-red-50 border border-red-200 rounded-md p-2"
    >
      <p class="text-sm text-red-600">
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview ChartCanvas - Componente de gráfico refactorizado
 * @description Canvas simplificado con separación de responsabilidades
 * @version 3.0.0 - Refactorizado con complejidad <10
 */

import { Chart, registerables } from 'chart.js'
import type { ChartDataPoint } from '~/types/chart'
import { getChartConfig } from '~/utils/chartConfig'

// Define types locally to avoid import resolution issues
interface ChartProps {
  chartData: ChartDataPoint[]
  selectedChartType: 'line' | 'bar'
  height?: number
  showLegend?: boolean
  showTooltip?: boolean
}

interface ChartEmits {
  (e: 'chart-ready', chart: unknown): void
  (e: 'chart-error', error: Error): void
  (e: 'data-point-click', data: ChartDataPoint): void
}

// Registrar componentes de Chart.js
Chart.register(...registerables)

// Props con tipos estrictos
const props = withDefaults(defineProps<ChartProps>(), {
  height: 320,
  showLegend: true,
  showTooltip: true,
})

// Emits definidos
const emit = defineEmits<ChartEmits>()

// Referencias
const chartCanvas = ref<HTMLCanvasElement | null>(null)
const chartInstance = ref<Chart | null>(null)

// Composable con lógica extraída
const {
  isLoading,
  error,
  hasData,
  chartAriaLabel,
  validateCanvasElement,
  validateChartData,
  setLoading,
  setError,
  clearError,
  withRetry,
} = useChart(
  toRef(props, 'chartData'),
  toRef(props, 'selectedChartType'),
)

// Función simplificada para crear gráfico
const createChartInstance = (): Promise<void> => new Promise((resolve, reject) => {
  try {
    if (!chartCanvas.value) {
      throw new Error('Canvas no disponible')
    }

    const validation = validateChartData()
    if (!validation.isValid) {
      throw new Error(validation.errors.join(', '))
    }

    if (!validateCanvasElement(chartCanvas.value)) {
      throw new Error('Canvas inválido')
    }

    // Destruir instancia anterior
    if (chartInstance.value) {
      chartInstance.value.destroy()
      chartInstance.value = null
    }

    // Crear nueva instancia
    const config = getChartConfig(props.selectedChartType, props.chartData)
    chartInstance.value = new Chart(chartCanvas.value, config)

    emit('chart-ready', chartInstance.value)
    resolve()
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Error desconocido'
    setError(errorMessage)
    emit('chart-error', err instanceof Error ? err : new Error(errorMessage))
    reject(err)
  }
})

// Función principal de inicialización
const initializeChart = async (): Promise<void> => {
  if (!hasData.value) return

  setLoading(true)
  clearError()

  await withRetry(createChartInstance)

  setLoading(false)
}

// Watchers
watch(() => props.chartData, initializeChart, { deep: true })
watch(() => props.selectedChartType, initializeChart)

// Lifecycle
onMounted(async () => {
  await nextTick()
  await initializeChart()
})

onBeforeUnmount(() => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
})
</script>
