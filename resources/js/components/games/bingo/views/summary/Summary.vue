<template>
  <div :class="containerClass">
    <div class="card">
      <!-- Incluye el header del card con variables claras -->
      <CardHeader :subtitle="subtitle" :btnCardHeader="{
        route: $route('bingo.configuration'),
        icon: 'fa-solid fa-gear'
      }" />
      <div class="card-body">
        <CoinTabs :currencies="currencies" :currency="selectedCurrencyId" :routeWithCurrency="{
          route: 'bingo.byCurrency',
          parameters: { room: 42 }
        }" />
        <BtnGroup :buttons="[
          { color: 'btn-primary', icon: 'fa fa-copy', dataType: 'copy', title: 'Copiar' },
          { color: 'btn-danger', icon: 'fa fa-trash', dataType: 'delete', title: 'Eliminar' }
        ]" name="room" :dataId="room.id" @action="handleButtonAction" />
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
import CardHeader from '@/Components/Utils/CardHeader.vue'
import CoinTabs from '@/Components/Utils/CoinTabs.vue'
import BtnGroup from '@/Components/Utils/BtnGroup.vue'
import TableRooms from '@/Components/Games/bingo/views/Summary/TableRooms.vue'
import { computed, ref, onMounted } from 'vue'
import { BingoService } from '@/services/bingoService'
import type { BingoRoom, RoomListResponse } from '@/services/bingoService'

// Props recibidos
const props = defineProps({
  layout: { type: String, required: true },
  subtitle: { type: String, default: '' },
  customer: { type: Object, default: null },
})

// Estado reactivo
const rooms = ref<BingoRoom[]>([])
const currencies = ref([
  { id: 'USD', name: 'USD', symbol: '$' },
  { id: 'EUR', name: 'EUR', symbol: '€' },
  { id: 'COP', name: 'COP', symbol: '$' },
])
const selectedCurrencyId = ref('USD')
const loading = ref(false)
const room = ref({ id: null }) // Para compatibilidad

/**
 * Cargar salas de bingo
 */
async function loadRooms() {
  loading.value = true
  try {
    const response = await BingoService.listRooms({
      limit: 50,
      offset: 0
    })
    
    if (response.success && response.data) {
      rooms.value = response.data.rooms || []
    }
  } catch (error) {
    console.error('Error cargando salas:', error)
  } finally {
    loading.value = false
  }
}

/**
 * Manejar acciones de botones
 */
async function handleButtonAction(action: { type: string, id: string | number }) {
  if (action.type === 'copy') {
    console.log('Copiando sala:', action.id)
    // Implementar lógica de copia
  } else if (action.type === 'delete') {
    console.log('Eliminando sala:', action.id)
    try {
      const response = await BingoService.closeRoom(action.id.toString())
      if (response.success) {
        await loadRooms() // Recargar lista
      }
    } catch (error) {
      console.error('Error eliminando sala:', error)
    }
  }
}

// Computed para la clase del contenedor según si existe customer
const containerClass = computed(() => (props.customer ? 'col-12 p-2' : 'col-12'))

// Cargar datos al montar el componente
onMounted(() => {
  loadRooms()
})
</script>

<style scoped>
/* Aquí puedes agregar estilos si necesitas */
</style>
