<template>
  <div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
      <thead>
        <tr>
          <th>Sala</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>CTN</th>
          <th>#PJ</th>
          <th>Venta</th>
          <th>Bote</th>
          <th>Status</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="room in rooms" :key="room.id">
          <td class="align-middle">
            <small>Tipo: {{ room.roomTypeDescription || 'N/A' }}</small>
            <a v-if="room.status > 1" :href="generateRoomRoute(room)" class="badge badge-dark" target="_blank"
              title="Ver Sala">
              <i class="fa-regular fa-eye"></i>
            </a>
            <br />
            <a class="badge bg-navy" :href="reportLink(room)" title="Ver Reporte">
              {{ room.encrypted }}
            </a>
          </td>

          <td class="align-middle">{{ formatDate(room.publicationDate) }}</td>
          <td class="align-middle">{{ formatDate(room.publicationDate) }}</td>
          <td class="align-middle">{{ currencySymbol }} {{ calculatePrice(room) }}</td>

          <td class="align-middle">
            <i class="fa-solid fa-users mr-1"></i> {{ room.currentPlayers ?? 0 }}<br />
            <i class="fa-solid fa-credit-card mr-1"></i> {{ room.currentCards ?? 0 }}
          </td>

          <td class="align-middle">{{ currencySymbol }} {{ calculateAllSale(room) }}</td>

          <td class="align-middle">
            <small>Bote</small><br />
            {{ currencySymbol }} {{ calculatePrizePool(room) }}
          </td>

          <td class="align-middle">
            <span :class="`badge bg-${statusColor(room.status)}`">
              {{ BingoService.getRoomStatusText(room.status) }}
            </span>
          </td>

          <td class="align-middle">
            <div class="btn-group" role="group">
              <button class="btn btn-sm bg-navy" title="Copiar">
                <i class="fa-solid fa-copy"></i>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import dayjs from 'dayjs'
import { BingoService, type BingoRoom } from '@js/services/bingoService'

interface Props {
  rooms: BingoRoom[]
  creator?: any
  currencySymbol?: string
  currency?: number
  uuidCode?: string
  exchangeRate?: number
}

const props = withDefaults(defineProps<Props>(), {
  currencySymbol: '$',
  exchangeRate: 1,
})

// Formatea fechas con dayjs
const formatDate = (date: Date | string | null | undefined): string => {
  if (!date) return 'N/A'
  return dayjs(date).format('DD/MM/YY')
}

const truncate = (n: number): number => Math.floor(n * 100) / 100

const calculatePrice = (room: BingoRoom): number => {
  const price = room.cardPrice * props.exchangeRate
  return truncate(price)
}

const calculateAllSale = (room: BingoRoom): string => {
  // Calculamos la venta total basada en jugadores actuales y precio de cartón
  const allSale = (room.currentCards * room.cardPrice) * props.exchangeRate
  return allSale.toFixed(2)
}

const calculatePrizePool = (room: BingoRoom): string => {
  const pool = room.prizePool * props.exchangeRate
  return pool.toFixed(2)
}

const statusColor = (statusId: number): string => {
  return {
    1: 'gray',    // Abierta
    2: 'primary', // En juego
    3: 'navy',    // Pausada
    4: 'success', // Finalizada
    5: 'black',   // Cancelada
  }[statusId] ?? 'secondary'
}

const generateRoomRoute = (room: BingoRoom): string => {
  const params = new URLSearchParams({
    room: room.encrypted,
    currency: btoa(props.currencySymbol || '$'),
  })

  if (props.creator?.type === 'customer') {
    params.set('customer', props.creator.id)
    return `/bingo/out-game-customer?${params}`
  } else {
    params.set('uuidCode', props.uuidCode || '')
    return `/bingo/out-game?${params}`
  }
}

const reportLink = (room: BingoRoom): string => {
  // Para el reporte, necesitaríamos definir la ruta apropiada en el frontend
  return `/bingo/reports/room/${room.encrypted}`
}

</script>

<style scoped>
/* Puedes agregar estilos personalizados aquí */
</style>
