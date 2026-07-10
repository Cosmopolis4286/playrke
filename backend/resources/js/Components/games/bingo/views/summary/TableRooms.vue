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
            <a v-if="(room.status as number) > 1" :href="generateRoomRoute(room)" class="badge badge-dark" target="_blank"
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
            <span :class="`badge bg-${statusColor(room.status as number)}`">
              {{ getRoomStatusText(room.status) }}
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

interface SummaryRoom {
  id: string
  encrypted?: string
  status?: number | string
  roomTypeDescription?: string
  publicationDate?: Date | string | null
  cardPrice?: number
  currentPlayers?: number
  currentCards?: number
  prizePool?: number
}

interface Props {
  rooms: SummaryRoom[]
  creator?: { type?: string; id?: string }
  currencySymbol?: string
  currency?: number
  uuidCode?: string
  exchangeRate?: number
}

const props = withDefaults(defineProps<Props>(), {
  currencySymbol: '$',
  exchangeRate: 1,
})

const NUMERIC_STATUS_LABELS: Record<number, string> = {
  1: 'Abierta',
  2: 'En Juego',
  3: 'Pausada',
  4: 'Finalizada',
  5: 'Cancelada',
}

const CODE_STATUS_LABELS: Record<string, string> = {
  ACTIVE: 'Disponible',
  IN_PROGRESS: 'En Juego',
  FINISHED: 'Finalizada',
  REFUND: 'Reembolso',
  OFFLINE: 'Fuera de Línea',
}

function getRoomStatusText(status: number | string | undefined): string {
  if (status === undefined || status === null) return 'Desconocido'
  if (typeof status === 'number') return NUMERIC_STATUS_LABELS[status] ?? 'Desconocido'
  const upper = status.toUpperCase()
  return CODE_STATUS_LABELS[upper] ?? NUMERIC_STATUS_LABELS[Number(status)] ?? 'Desconocido'
}

const formatDate = (date: Date | string | null | undefined): string => {
  if (!date) return 'N/A'
  return dayjs(date).format('DD/MM/YY')
}

const truncate = (n: number): number => Math.floor(n * 100) / 100

const calculatePrice = (room: SummaryRoom): number =>
  truncate((room.cardPrice ?? 0) * props.exchangeRate)

const calculateAllSale = (room: SummaryRoom): string =>
  (((room.currentCards ?? 0) * (room.cardPrice ?? 0)) * props.exchangeRate).toFixed(2)

const calculatePrizePool = (room: SummaryRoom): string =>
  ((room.prizePool ?? 0) * props.exchangeRate).toFixed(2)

const statusColor = (statusId: number): string =>
  ({ 1: 'gray', 2: 'primary', 3: 'navy', 4: 'success', 5: 'black' }[statusId] ?? 'secondary')

const generateRoomRoute = (room: SummaryRoom): string => {
  const params = new URLSearchParams({
    room: room.encrypted ?? '',
    currency: btoa(props.currencySymbol || '$'),
  })

  if (props.creator?.type === 'customer') {
    params.set('customer', props.creator.id ?? '')
    return `/bingo/out-game-customer?${params}`
  }

  params.set('uuidCode', props.uuidCode || '')
  return `/bingo/out-game?${params}`
}

const reportLink = (room: SummaryRoom): string =>
  `/bingo/reports/room/${room.encrypted}`
</script>

<style scoped>
/* Puedes agregar estilos personalizados aquí */
</style>
