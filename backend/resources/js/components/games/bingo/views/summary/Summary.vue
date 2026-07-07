<template>
  <div :class="containerClass">
    <div class="card">
      <CardHeader :subtitle="subtitle" :btnCardHeader="{
        route: getRoute('configuration', {}),
        icon: 'fa-solid fa-gear'
      }" />

      <div class="card-body">
        <CurrencyTabs :currencies="currencies" :currency="selectedCurrencyId" :routeWithCurrency="{
          route: 'bingo.byCurrency',
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
import { getRoute } from '@js/routes/bingoRoutes'
import backendClient from '@js/plugins/axios'

interface SummaryRoom {
  id: string
  [key: string]: unknown
}

interface Props {
  layout: string
  subtitle?: string
  customer?: object | null
}

const props = defineProps<Props>()
const { subtitle = '', customer = null } = props

const rooms = ref<SummaryRoom[]>([])
const currencies = ref([
  { id: 'USD', name: 'USD', symbol: '$', encrypted: 'USD' },
  { id: 'EUR', name: 'EUR', symbol: '€', encrypted: 'EUR' },
  { id: 'COP', name: 'COP', symbol: '$', encrypted: 'COP' }
])
const selectedCurrencyId = ref('USD')
const loading = ref(false)
const room = ref({ id: null })

const btnGroupButtons = ref([
  { color: 'btn-primary', icon: 'fa fa-copy', dataType: 'copy', title: 'Copiar' },
  { color: 'btn-danger', icon: 'fa fa-trash', dataType: 'delete', title: 'Eliminar' }
])

async function loadRooms() {
  loading.value = true
  try {
    const { data } = await backendClient.get<{ data: SummaryRoom[] }>('/bingo-rooms/fetch-list')
    rooms.value = data.data ?? []
  } catch {
    // silencioso — tabla queda vacía
  } finally {
    loading.value = false
  }
}

async function handleButtonAction(action: { type: string; id: string | number }) {
  if (action.type === 'copy') {
    // TODO: implementar copia de sala
  } else if (action.type === 'delete') {
    try {
      await backendClient.delete(`/bingo-rooms/${action.id}/delete`)
      await loadRooms()
    } catch {
      // silencioso — la UI no cambia si falla el delete
    }
  }
}

const containerClass = computed(() => (customer ? 'col-12 p-2' : 'col-12'))

onMounted(() => {
  loadRooms()
})
</script>

<style scoped>
/* Puedes agregar estilos personalizados aquí */
</style>
