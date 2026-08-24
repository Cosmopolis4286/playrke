<script setup lang="ts">
  import { ref, computed } from 'vue'

  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import GamesTab from '@/js/Components/games/GamesTab.vue'
  import GameModesTab from '@/js/Components/games/GameModesTab.vue'
  import RoomTypesTab from '@/js/Components/games/RoomTypesTab.vue'
  import PrizePoolTypesTab from '@/js/Components/games/PrizePoolTypesTab.vue'
  import JackpotTypesTab from '@/js/Components/games/JackpotTypesTab.vue'

  import type { GamePageProps } from '@js/types/game'
  import type { GameModeGameOption } from '@js/types/gameMode'

  const props = defineProps<GamePageProps>()

  // Cada juego expuesto como opción {id, name, status} para los selects de
  // los catálogos hijos (Modos de Juego, Tipos de Bote).
  const gameOptions = computed<GameModeGameOption[]>(() =>
    props.games.map(g => ({ id: g.id, name: g.name, status: g.status }))
  )

  type TabKey = 'games' | 'gameModes' | 'roomTypes' | 'prizePoolTypes' | 'jackpotTypes'

  const TABS: { key: TabKey; label: string; icon: string }[] = [
    { key: 'games',          label: 'Juegos',          icon: 'fas fa-gamepad' },
    { key: 'gameModes',      label: 'Modos de Juego',  icon: 'fas fa-sliders-h' },
    { key: 'roomTypes',      label: 'Tipos de Sala',   icon: 'fas fa-door-open' },
    { key: 'prizePoolTypes', label: 'Tipos de Bote',   icon: 'fas fa-coins' },
    { key: 'jackpotTypes',   label: 'Jackpots',        icon: 'fas fa-trophy' },
  ]

  const activeTab = ref<TabKey>('games')

  // Cada tab monta su propio componente (con su propio useCrudEventListener)
  // solo cuando está activo — evita que los listeners de eventos CRUD
  // globales (window 'table:crud') de una tabla inactiva reaccionen a
  // acciones de la tabla visible, ya que el evento no distingue por tabla.
</script>

<template>
  <PageHeader
    icon="fa-gamepad"
    title="Catálogo de Juegos"
    subtitle="Juegos, modos de juego, tipos de sala, tipos de bote y jackpots de la plataforma."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Catálogo de Juegos' },
    ]"
  />

  <section class="content">
    <div class="container-fluid">
      <ul class="nav nav-tabs mb-3">
        <li v-for="tab in TABS" :key="tab.key" class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === tab.key }"
            @click="activeTab = tab.key"
          >
            <i :class="tab.icon" class="mr-1"></i>{{ tab.label }}
          </button>
        </li>
      </ul>

      <GamesTab
        v-if="activeTab === 'games'"
        :games="props.games"
        :role-user="props.roleUser"
      />

      <GameModesTab
        v-else-if="activeTab === 'gameModes'"
        :game-modes="props.gameModes"
        :games="gameOptions"
      />

      <RoomTypesTab
        v-else-if="activeTab === 'roomTypes'"
        :room-types="props.roomTypes"
      />

      <PrizePoolTypesTab
        v-else-if="activeTab === 'prizePoolTypes'"
        :prize-pool-types="props.prizePoolTypes"
        :games="gameOptions"
      />

      <JackpotTypesTab
        v-else-if="activeTab === 'jackpotTypes'"
        :jackpot-types="props.jackpotTypes"
        :games="gameOptions"
        :currencies="props.currencies"
      />
    </div>
  </section>
</template>
