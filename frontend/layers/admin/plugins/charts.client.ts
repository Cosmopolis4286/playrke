/**
 * Plugin de Chart.js para visualización de datos
 * Proporciona funcionalidad de gráficos en el panel de admin
 */

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

export default defineNuxtPlugin(() => {
  // Registrar componentes de Chart.js
  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
  )

  // Configuración global de charts
  ChartJS.defaults.responsive = true
  ChartJS.defaults.maintainAspectRatio = false
  ChartJS.defaults.plugins.legend.position = 'bottom'

  return {
    provide: {
      charts: ChartJS
    }
  }
})
