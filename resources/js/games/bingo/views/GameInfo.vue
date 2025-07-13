<template>
  <div class="game-info">
    <h1>Ronda {{ data.round }}</h1>

    <div v-if="currentNumber !== null" class="called-number mt-2">
      <strong>Último número llamado:</strong> {{ currentNumber }}
    </div>

    <BingoBoard :numbers="numbers" />

    <button @click="callNumber" class="btn btn-primary mt-3">
      Llamar número aleatorio
    </button>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import BingoBoard from '../components/BingoBoard.vue'
import echo from '@/echo.js'

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const numbers = ref([...props.data.numbers])
const currentNumber = ref(null)
const roomId = ref(props.data.roomId ?? 1)
let channel = null

const subscribeToChannel = (id) => {
  const channelName = `bingo.room.${id}`
  channel = echo.channel(channelName)

  // Debug conexión Reverb
  if (window.Echo?.connector?.pusher) {
    const pusher = window.Echo.connector.pusher

    pusher.connection.bind('state_change', (states) => {
      console.log('Cambio de estado conexión:', states)
    })

    pusher.bind('pusher:subscription_error', (error) => {
      console.error('❌ Error de subscripción:', error)
    })

    pusher.bind('pusher_internal:subscription_succeeded', (chan) => {
      console.log('✅ Subscripción exitosa al canal:', chan.name)
    })
  }

  // Escuchar evento
  console.log(`🎧 Escuchando evento .BingoNumberCalled en canal ${channelName}`)

  channel.listen('.BingoNumberCalled', (event) => {
    console.log('📡 Evento recibido:', event)
    if (!numbers.value.includes(event.number)) {
      numbers.value.push(event.number)
    }
    currentNumber.value = event.number
  })
}

const unsubscribeFromChannel = (id) => {
  if (!id) return
  const channelName = `bingo.room.${id}`
  echo.leave(channelName)
  channel = null
}

onMounted(() => {
  subscribeToChannel(roomId.value)
})

onBeforeUnmount(() => {
  unsubscribeFromChannel(roomId.value)
})

watch(() => props.data.numbers, (newNumbers) => {
  numbers.value = [...newNumbers]
})

watch(() => props.data.roomId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    unsubscribeFromChannel(oldId)
    roomId.value = newId
    subscribeToChannel(newId)
  }
})

function callNumber() {
  const number = Math.floor(Math.random() * 75) + 1
  console.log('📤 Enviando número al backend:', number)

  axios.post(route('bingo.call-number', roomId.value), { number })
    .then((res) => {
      console.log('✅ Número enviado con éxito:', res.data)
    })
    .catch((err) => {
      console.error('❌ Error al enviar número', err)
    })
}
</script>

<style scoped>
.game-info {
  padding: 1rem;
}

.called-number {
  font-size: 1.5rem;
  color: #007bff;
}
</style>
