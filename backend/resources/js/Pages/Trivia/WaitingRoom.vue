<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import RoomHeader from '@js/Components/Trivia/Room/RoomHeader.vue'
import PlayerList from '@js/Components/Trivia/Room/PlayerList.vue'
import JoinForm from '@js/Components/Trivia/Room/JoinForm.vue'
import ConnectionStatus from '@js/Components/Trivia/Shared/ConnectionStatus.vue'
import LoadingOverlay from '@js/Components/Trivia/Shared/LoadingOverlay.vue'
import type { Room, Player } from '@js/types/trivia'

interface Props {
  room: Room
  initialPlayers: Player[]
}

const props = defineProps<Props>()

const players = ref<Player[]>(props.initialPlayers)
const hasJoined = ref(false)
const currentPlayer = ref<Player | null>(null)
const isLoading = ref(false)
const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('connected')

let ws: WebSocket | null = null

function connectWebSocket() {
  const wsUrl = `${import.meta.env.VITE_WEBSOCKET_URL}/trivia/room/${props.room.code}`
  ws = new WebSocket(wsUrl)

  ws.onopen = () => {
    connectionStatus.value = 'connected'
  }

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    handleWebSocketMessage(data)
  }

  ws.onclose = () => {
    connectionStatus.value = 'disconnected'
    setTimeout(connectWebSocket, 3000)
  }

  ws.onerror = () => {
    connectionStatus.value = 'reconnecting'
  }
}

function handleWebSocketMessage(data: any) {
  switch (data.type) {
    case 'player.joined':
      players.value.push(data.player)
      break
    case 'player.left':
      players.value = players.value.filter(p => p.id !== data.playerId)
      break
    case 'game.started':
      window.location.href = `/trivia/game/${props.room.code}`
      break
    case 'room.updated':
      break
  }
}

function handleJoin(playerName: string) {
  isLoading.value = true

  axios.post(`/api/trivia/rooms/${props.room.code}/join`, { name: playerName })
    .then(response => {
      currentPlayer.value = response.data.player
      hasJoined.value = true
      players.value.push(response.data.player)
    })
    .catch(error => {
      alert(error.response?.data?.message || 'Error al unirse')
    })
    .finally(() => {
      isLoading.value = false
    })
}

onMounted(() => {
  connectWebSocket()
})

onUnmounted(() => {
  ws?.close()
})
</script>

<template>
  <Head :title="`Sala ${room.code} - Trivia`" />

  <div class="min-h-screen bg-gray-100">
    <ConnectionStatus :status="connectionStatus" />

    <LoadingOverlay v-if="isLoading" message="Uniéndose a la sala..." />

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <RoomHeader
        :room="room"
        :player-count="players.length"
        :max-players="room.maxPlayers"
      />

      <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <PlayerList
            :players="players"
            :host-id="room.hostId"
            :current-player-id="currentPlayer?.id"
          />
        </div>

        <div class="lg:col-span-1">
          <JoinForm
            v-if="!hasJoined"
            @join="handleJoin"
          />

          <div
            v-else
            class="bg-white rounded-lg shadow-md p-6 text-center"
          >
            <div class="text-green-500 mb-2">
              <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">¡Te has unido!</h3>
            <p class="text-sm text-gray-500 mt-1">Esperando que el anfitrión inicie el juego...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
