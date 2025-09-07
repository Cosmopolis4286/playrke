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
        <tr v-for="room in rooms" :key="room.encrypted">
          <td class="align-middle">
            <small>Modo: {{ room.Config?.GameMode?.description || 'N/A' }}</small>
            <a v-if="room.status > 1" :href="generateRoomRoute(room)" class="badge badge-dark" target="_blank"
              title="Ver Sala">
              <i class="fa-regular fa-eye"></i>
            </a>
            <br />
            <a class="badge bg-navy" :href="reportLink(room)" title="Ver Reporte">
              {{ room.encrypted }}
            </a>
          </td>

          <td class="align-middle">{{ formatDate(room.Config?.date) }}</td>
          <td class="align-middle">{{ room.Config?.time || 'N/A' }} H</td>
          <td class="align-middle">{{ coinSym }} {{ calculatePrice(room) }}</td>

          <td class="align-middle">
            <i class="fa-solid fa-users mr-1"></i> {{ room.players ?? 0 }}<br />
            <i class="fa-solid fa-trophy mr-1"></i> {{ room.winners ?? 0 }}
          </td>

          <td class="align-middle">{{ coinSym }} {{ calculateAllSale(room) }}</td>

          <td class="align-middle">
            <small>{{ room.Config?.PrizePoolType?.description || 'N/A' }}</small><br />
            {{ coinSym }} {{ calculatePrizePool(room) }}
          </td>

          <td class="align-middle">
            <span :class="`badge bg-${statusColor(room.Status?.id)}`">
              {{ room.Status?.description || 'N/A' }}
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

<script setup>
import dayjs from 'dayjs'

const props = defineProps({
  rooms: {
    type: Array,
    required: true,
  },
  creator: Object,
  coinSym: {
    type: String,
    default: '',
  },
  currency: Number,
  uuidCode: String,
  exchangeRate: {
    type: Number,
    default: 1,
  },
})

// Formatea fechas con dayjs
const formatDate = (date) => {
  if (!date) return 'N/A'
  return dayjs(date).format('DD/MM/YY')
}

const truncate = (n) => Math.floor(n * 100) / 100

const calculatePrice = (room) => {
  const rate = room.GameExchangeRate?.[0]?.exchange_rate ?? props.exchangeRate
  const price = (room.Config?.ctn_price ?? 0) * rate
  return truncate(price)
}

const calculateAllSale = (room) => {
  const rate = room.GameExchangeRate?.[0]?.exchange_rate ?? props.exchangeRate
  const allSale = (room.all_sale ?? 0) * rate
  return allSale.toFixed(2)
}

const calculatePrizePool = (room) => {
  const rate = room.GameExchangeRate?.[0]?.exchange_rate ?? props.exchangeRate
  const pool = (room.prize_pool ?? 0) * rate
  return pool.toFixed(2)
}

const statusColor = (statusId) => {
  return {
    1: 'gray',
    2: 'primary',
    3: 'navy',
    4: 'success',
    5: 'black',
  }[statusId] ?? 'secondary'
}

const generateRoomRoute = (room) => {
  const params = new URLSearchParams({
    room: room.encrypted,
    currency: btoa(props.coinSym),
  })

  if (props.creator?.type === 'customer') {
    params.set('customer', props.creator.id)
    return `/bingo/out-game-customer?${params}`
  } else {
    params.set('uuidCode', props.uuidCode)
    return `/bingo/out-game?${params}`
  }
}

const reportLink = (room) => {
  return route('bingo.reportSalesByRoom', {
    room: room.encrypted,
    currency: btoa(1),
  })
}

</script>

<style scoped>
/* Puedes agregar estilos personalizados aquí */
</style>
