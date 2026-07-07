<template>
  <div :class="containerClass">
    <div class="card">
      <!-- Header del card -->
      <CardHeader :subtitle="subtitle" :btnCardHeader="{
        route: getRoute('configuration', {}),
        icon: 'fa-solid fa-gear'
      }" />

      <div class="card-body">
        <CurrencyTabs :currencies="currencies" :currency="selectedCurrencyId" :routeWithCurrency="{
          route: 'bingo.byCurrency', // Aquí se podría reemplazar con getRoute si existe en bingoRoutes
          parameters: { room: 42 }
        }" />

        <BtnGroup :buttons="btnGroupButtons" name="room" :dataId="room.id" @action="handleButtonAction" />

        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active">
            <TableRooms :rooms="rooms" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import CardHeader from '@js/components/utils/CardHeader.vue'
import CurrencyTabs from '@js/components/utils/CurrencyTabs.vue'
import BtnGroup from '@js/components/utils/BtnGroup.vue'
import TableRooms from '@js/components/games/bingo/views/summary/TableRooms.vue'
import { BingoService } from '@js/services/bingoService'
import type { BingoRoom } from '@js/services/bingoService'
import { getRoute } from '@js/routes/bingoRoutes'

// Props
interface Props {
  layout: string
  subtitle?: string
  customer?: object | null
}

const props = defineProps<Props>()
const { subtitle = '', customer = null } = props

// Estado reactivo
const rooms = ref<BingoRoom[]>([])
const currencies = ref([
  { id: 'USD', name: 'USD', symbol: '$', encrypted: 'USD' },
  { id: 'EUR', name: 'EUR', symbol: '€', encrypted: 'EUR' },
  { id: 'COP', name: 'COP', symbol: '$', encrypted: 'COP' }
])
const selectedCurrencyId = ref('USD')
const loading = ref(false)
const room = ref({ id: null }) // Para compatibilidad con BtnGroup

// Botones del grupo
const btnGroupButtons = ref([
  { color: 'btn-primary', icon: 'fa fa-copy', dataType: 'copy', title: 'Copiar' },
  { color: 'btn-danger', icon: 'fa fa-trash', dataType: 'delete', title: 'Eliminar' }
])

/**
 * Cargar salas de bingo
 */
async function loadRooms() {
  loading.value = true
  try {
    const response = await BingoService.listRooms({ limit: 50, offset: 0 })
    if (response.success && response.data) {
      rooms.value = response.data.rooms || []
    }
  } catch {
    // silencioso — tabla queda vacía
  } finally {
    loading.value = false
  }
}

/**
 * Manejar acciones de botones
 */
async function handleButtonAction(action: { type: string; id: string | number }) {
  if (action.type === 'copy') {
    // TODO: implementar copia de sala
  } else if (action.type === 'delete') {
    try {
      const response = await BingoService.closeRoom(action.id.toString())
      if (response.success) {
        await loadRooms()
      }
    } catch {
      // silencioso — la UI no cambia si falla el delete
    }
  }
}

// Computed para clase del contenedor
const containerClass = computed(() => (customer ? 'col-12 p-2' : 'col-12'))

// Cargar datos al montar
onMounted(() => {
  loadRooms()
})
</script>

<style scoped>
/* Puedes agregar estilos personalizados aquí */
</style>
