<script setup lang="ts">
interface GameMode { value: string; label: string; description: string }
interface TeamsConfig {
  team_count: number
  team_size: number | null
  duel_type: '2vs2' | '1vsX'
  team_names: string[]
}

const props = defineProps<{
  gameModes: GameMode[]
  gameMode: 'free_for_all' | 'teams' | 'duel'
  teamsConfig: TeamsConfig
  maxPlayers: number
}>()

defineEmits<{
  'update:gameMode': [value: string]
}>()

const showTeamConfig = () => props.gameMode !== 'free_for_all'
const isDuelMode = () => props.gameMode === 'duel'
</script>

<template>
  <div class="gms-border-t pt-6">
    <h3 class="text-lg font-medium gms-text mb-4">
      🎮 Modo de Juego
    </h3>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium gms-text-secondary mb-2">
          Selecciona el modo <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <label
            v-for="mode in gameModes"
            :key="mode.value"
            class="gms-mode-option relative flex cursor-pointer rounded-lg border p-4"
            :class="gameMode === mode.value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'gms-mode-border'"
          >
            <input
              :checked="gameMode === mode.value"
              type="radio"
              :value="mode.value"
              class="sr-only"
              @change="$emit('update:gameMode', mode.value)"
            />
            <div class="flex flex-col">
              <span class="font-medium gms-text">{{ mode.label }}</span>
              <span class="text-xs gms-text-muted">{{ mode.description }}</span>
            </div>
            <div
              v-if="gameMode === mode.value"
              class="absolute top-2 right-2 text-indigo-600"
            >
              ✓
            </div>
          </label>
        </div>
      </div>

      <!-- Configuración de Equipos (visible si no es free_for_all) -->
      <div v-if="showTeamConfig()" class="gms-subpanel mt-4 p-4 rounded-lg">
        <h4 class="font-medium gms-text mb-3">
          {{ isDuelMode() ? '⚔️ Configuración del Duelo' : '👥 Configuración de Equipos' }}
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tipo de Duelo -->
          <div v-if="isDuelMode()">
            <label class="block text-sm font-medium gms-text-secondary mb-2">
              Tipo de duelo
            </label>
            <select
              v-model="teamsConfig.duel_type"
              class="gms-input block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="2vs2">2 vs 2 (Igual número)</option>
              <option value="1vsX">1 vs Todos (Uno contra todos)</option>
            </select>
            <p class="mt-1 text-xs gms-text-muted">
              {{ teamsConfig.duel_type === '1vsX' ? 'Equipo 1: 1 jugador | Equipo 2: Todos los demás' : 'Ambos equipos con igual número de jugadores' }}
            </p>
          </div>

          <!-- Número de equipos -->
          <div v-else>
            <label class="block text-sm font-medium gms-text-secondary mb-2">
              Número de equipos
            </label>
            <input
              v-model.number="teamsConfig.team_count"
              type="number"
              min="2"
              max="10"
              class="gms-input block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>

          <!-- Tamaño máximo por equipo -->
          <div>
            <label class="block text-sm font-medium gms-text-secondary mb-2">
              Jugadores por equipo
            </label>
            <input
              v-model.number="teamsConfig.team_size"
              type="number"
              :placeholder="Math.ceil(maxPlayers / teamsConfig.team_count).toString()"
              min="1"
              class="gms-input block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            <p class="mt-1 text-xs gms-text-muted">Deja vacío para distribución automática</p>
          </div>
        </div>

        <!-- Nombres de equipos -->
        <div class="mt-4">
          <label class="block text-sm font-medium gms-text-secondary mb-2">
            Nombres de los equipos
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <input
              v-for="(name, index) in teamsConfig.team_names"
              :key="index"
              v-model="teamsConfig.team_names[index]"
              type="text"
              :placeholder="'Equipo ' + (index + 1)"
              class="gms-input block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.gms-border-t { border-top: 1px solid var(--ds-border); }
.gms-text { color: var(--ds-text); }
.gms-text-secondary { color: var(--ds-text-2); }
.gms-text-muted { color: var(--ds-text-muted); }
.gms-mode-option:hover { background: var(--ds-surface-2); }
.gms-mode-border { border-color: var(--ds-border); }
.gms-subpanel { background: var(--ds-surface-2); }
.gms-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text-2); }
</style>
