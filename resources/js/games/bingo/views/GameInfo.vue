<!-- BingoBoard.vue -->
<template>
  <div class="game-info">
    <div class="container">
      <div class="row">
        <div
          class="col-12 my-2 d-flex flex-column justify-content-center align-items-center"
        >
          <h1>Ronda {{ data.round }}</h1>
          <div v-if="currentNumber !== null" class="called-number">
            <strong>Último número llamado:</strong> {{ currentNumber }}
          </div>
        </div>

        <BingoBoard :calledNumbers="numbers" :currentNumber="currentNumber" />

        <div class="col-12 d-flex justify-content-center">
          <NumberCaller
            :calledNumbers="numbers"
            @call-number="callNumberFromInput"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import BingoBoard from "../components/game-info/BingoBoard.vue";
import NumberCaller from "../components/game-info/NumberCaller.vue";
import echo from "@/echo.js";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const numbers = ref([...props.data.numbers]);
const currentNumber = ref(null);
const roomId = ref(props.data.roomId ?? 1);
let channel = null;

// Función para suscribirse al canal WebSocket de la sala
const subscribeToChannel = (id) => {
  const channelName = `bingo.room.${id}`;
  channel = echo.channel(channelName);

  if (window.Echo?.connector?.pusher) {
    const pusher = window.Echo.connector.pusher;

    pusher.connection.bind("state_change", (states) => {
      console.log("Cambio de estado conexión:", states);
    });

    pusher.bind("pusher:subscription_error", (error) => {
      console.error("❌ Error de subscripción:", error);
    });

    pusher.bind("pusher_internal:subscription_succeeded", (chan) => {
      console.log("✅ Subscripción exitosa al canal:", chan.name);
    });
  }

  console.log(
    `🎧 Escuchando evento .BingoNumberCalled en canal ${channelName}`
  );

  channel.listen(".BingoNumberCalled", (event) => {
    if (!numbers.value.includes(event.number)) {
      numbers.value.push(event.number);
    }
    currentNumber.value = event.number;
  });
};

// Función para dejar de escuchar el canal
const unsubscribeFromChannel = (id) => {
  if (!id) return;
  echo.leave(`bingo.room.${id}`);
  channel = null;
};

onMounted(async () => {
  try {
    // 1. Obtener los números previamente llamados
    const res = await axios.get(route("bingo.called-numbers", roomId.value));
    numbers.value = res.data ?? [];
  } catch (error) {
    console.error("❌ Error al cargar números anteriores:", error);
  }

  // 2. Suscribirse al canal WebSocket
  subscribeToChannel(roomId.value);
});

onBeforeUnmount(() => {
  unsubscribeFromChannel(roomId.value);
});

// Reactividad para cambios en las props
watch(
  () => props.data.numbers,
  (newNumbers) => {
    numbers.value = [...newNumbers];
  }
);

watch(
  () => props.data.roomId,
  (newId, oldId) => {
    if (newId && newId !== oldId) {
      unsubscribeFromChannel(oldId);
      roomId.value = newId;
      subscribeToChannel(newId);
    }
  }
);

// Función que maneja el evento emitido por NumberCaller.vue
function callNumberFromInput(number) {
  console.log("📤 Enviando número al backend:", number);

  axios
    .post(route("bingo.call-number", roomId.value), { number })
    .then((res) => {
      console.log("✅ Número enviado con éxito:", res.data);
    })
    .catch((err) => {
      console.error("❌ Error al enviar número", err);
    });
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
