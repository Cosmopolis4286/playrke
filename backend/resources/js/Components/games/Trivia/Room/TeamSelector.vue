<script setup lang="ts">
import type { Team } from '@js/types/trivia'

defineProps<{
  teams?: Team[]
  gameMode?: 'free_for_all' | 'teams' | 'duel'
  selectedTeamId: string | null
}>()

defineEmits<{
  'update:selectedTeamId': [id: string]
}>()
</script>

<template>
  <div class="ts-panel rounded-2xl shadow-sm border overflow-hidden">
    <div class="h-1" :class="gameMode === 'duel' ? 'bg-gradient-to-r from-red-500 to-blue-500' : 'bg-gradient-to-r from-indigo-400 to-purple-500'" />
    <div class="p-4 space-y-4">
      <div class="text-center">
        <h3 class="text-base font-bold ts-text">
          {{ gameMode === 'duel' ? '⚔️ Elige tu bando' : '👥 Elige tu equipo' }}
        </h3>
        <p class="text-xs ts-text-muted mt-1">
          Selecciona un equipo para unirte
        </p>
      </div>

      <div class="space-y-2">
        <button
          v-for="team in teams"
          :key="team.id"
          @click="$emit('update:selectedTeamId', team.id)"
          class="w-full p-3 rounded-xl border-2 transition-all text-left flex items-center gap-3"
          :class="selectedTeamId === team.id
            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
            : 'ts-team-border hover:border-indigo-300 dark:hover:border-indigo-700'
          "
          :style="team.color ? { borderColor: selectedTeamId === team.id ? team.color : undefined } : {}"
        >
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center text-lg"
            :style="team.color ? { backgroundColor: team.color + '20', color: team.color } : {}"
          >
            {{ team.icon || '🏆' }}
          </div>
          <div class="flex-1">
            <p class="font-medium ts-text">{{ team.name }}</p>
            <p class="text-xs ts-text-muted">
              {{ team.currentPlayers }}/{{ team.maxPlayers }} jugadores
            </p>
          </div>
          <div
            v-if="selectedTeamId === team.id"
            class="w-6 h-6 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm"
          >
            ✓
          </div>
        </button>
      </div>

      <p v-if="!selectedTeamId" class="text-xs text-center text-amber-600 dark:text-amber-400">
        ⚠️ Debes seleccionar un equipo para continuar
      </p>
    </div>
  </div>
</template>

<style scoped>
.ts-panel { background: var(--ds-surface); border-color: var(--ds-border); }
.ts-text { color: var(--ds-text); }
.ts-text-muted { color: var(--ds-text-muted); }
.ts-team-border { border-color: var(--ds-border); }
</style>
