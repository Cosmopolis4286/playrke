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
import Swal from 'sweetalert2'
import { SWAL_DARK } from '@js/utils/swal'
import CardHeader from '@js/Components/utils/CardHeader.vue'
import CurrencyTabs from '@js/Components/utils/CurrencyTabs.vue'
import BtnGroup from '@js/Components/utils/BtnGroup.vue'
import TableRooms from '@js/Components/games/bingo/views/summary/TableRooms.vue'
import { getRoute } from '@js/routes/bingoRoutes'
import backendClient from '@js/plugins/axios'
import { showToast } from '@js/utils/toast'

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
    Swal.fire({
      title: 'Error al cargar salas',
      text: 'No se pudo obtener la lista de salas. Intenta recargar la página.',
      icon: 'error',
      confirmButtonText: 'Aceptar',
      ...SWAL_DARK,
    })
  } finally {
    loading.value = false
  }
}

async function handleButtonAction(action: { type: string; id: string | number }) {
  if (action.type === 'copy') {
    // TODO: implementar copia de sala
  } else if (action.type === 'delete') {
    const result = await Swal.fire({
      title: 'Eliminar sala',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      reverseButtons: true,
      ...SWAL_DARK,
    })
    if (!result.isConfirmed) return

    try {
      await backendClient.delete(`/bingo-rooms/${action.id}/delete`)
      showToast('Sala eliminada correctamente.', 'success')
      await loadRooms()
    } catch (e: any) {
      showToast(e?.response?.data?.message ?? 'No se pudo eliminar la sala.', 'error')
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
