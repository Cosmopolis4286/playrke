<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

import BingoGameRoomCard from "@js/Components/games/bingo/Room/GameRooms/BingoGameRoomCard.vue";
import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";

import type { BingoGameRoomsPageProps } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

/**
 * Vitrina pública de salas (sin sesión) — mismo catálogo/métricas que el
 * lobby autenticado (Pages/Games/Bingo/Room/GameRooms.vue), pero de solo
 * lectura: el click en cualquier card lleva a login/registro en vez de abrir
 * el modal de compra. Ver App\Http\Controllers\PublicController::
 * gameRoomsShowcase() y la decisión de producto de no exponer compra sin
 * cuenta (riesgo de KYC/fraude — visitante sin identidad persistente).
 *
 * Estilos propios y autocontenidos (no reutiliza el <style scoped> de
 * GameRooms.vue, que vive dentro de ese componente y no es un CSS
 * compartido) — mismo lenguaje visual (fondo oscuro, grid centrado) pero
 * más simple, sin el fondo animado de estrellas de la página autenticada.
 */
const props = defineProps<BingoGameRoomsPageProps>();

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(serverTimestampMs);

onMounted(() => {
  initCountdown(props.rooms);
});

// ── Modal "Ver Top de Compras" ──
const topBuyersRoomId = ref<string | null>(null);
const topBuyersRoomUuidCode = computed(() => {
  const room = props.rooms.find((r) => r.id === topBuyersRoomId.value);
  return room?.uuid_code ?? null;
});
function openTopBuyers(roomId: string) {
  topBuyersRoomId.value = roomId;
}
function closeTopBuyers() {
  topBuyersRoomId.value = null;
}

function goToRegister() {
  window.location.href = "/register";
}
</script>

<template>
  <div class="showcase-page">
    <!-- ── Encabezado ── -->
    <div class="showcase-page__header">
      <h1 class="showcase-page__title">Salas de Bingo en vivo</h1>
      <p class="showcase-page__subtitle">
        Regístrate para comprar tus cartones y participar
      </p>
      <button type="button" class="showcase-page__cta" @click="goToRegister">
        <i class="fas fa-user-plus" /> Crear cuenta gratis
      </button>
    </div>

    <!-- ── Sin salas ── -->
    <div v-if="!props.rooms.length" class="showcase-page__empty">
      <i class="fas fa-door-closed" />
      <p>No hay salas activas en este momento</p>
    </div>

    <!-- ── Grid de tarjetas ── -->
    <div v-else class="showcase-page__grid">
      <div v-for="(room, index) in props.rooms" :key="room.id" class="showcase-page__cell">
        <BingoGameRoomCard
          :room="room"
          :exchange-rate="1"
          :currency-sym="'USD'"
          :countdown="getCountdown(room.id)"
          :index="index"
          @select="goToRegister"
          @show-top-buyers="openTopBuyers"
        />
      </div>
    </div>

    <p class="showcase-page__footer">
      ¿Ya tienes cuenta?
      <Link href="/login" class="showcase-page__footer-link">Inicia sesión</Link>
    </p>
  </div>

  <!-- ══ Modal "Ver Top de Compras" ══ -->
  <RoomTopBuyersModal
    :room-id="topBuyersRoomId"
    :room-uuid-code="topBuyersRoomUuidCode"
    endpoint-base="/game-rooms"
    @close="closeTopBuyers"
  />
</template>

<style scoped>
.showcase-page {
  min-height: 100vh;
  padding: 32px 12px 56px;
  background: linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}

.showcase-page__header {
  text-align: center;
  margin-bottom: 28px;
}
.showcase-page__title {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(20px, 5vw, 30px);
  font-weight: 800;
  letter-spacing: 1.5px;
  margin: 0 0 6px;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.showcase-page__subtitle {
  font-size: 12px;
  color: #7a96c8;
  margin: 0 0 16px;
}
.showcase-page__cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%);
  color: #06121a;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.3px;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(42, 245, 152, 0.25);
}
.showcase-page__cta:hover {
  filter: brightness(1.06);
}

.showcase-page__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 320px;
  margin: 40px auto;
  padding: 28px 20px;
  border-radius: 20px;
  text-align: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #9fb3da;
  font-size: 13.5px;
  font-weight: 600;
}
.showcase-page__empty i {
  font-size: 26px;
  color: #4a6698;
}

.showcase-page__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 220px));
  justify-content: center;
  gap: 18px;
  max-width: 1180px;
  margin: 0 auto;
}
.showcase-page__cell {
  display: flex;
}

.showcase-page__footer {
  text-align: center;
  margin: 32px 0 8px;
  font-size: 12px;
  color: #7a96c8;
}
.showcase-page__footer-link {
  color: #2af598;
  font-weight: 700;
  margin-left: 4px;
}
</style>
