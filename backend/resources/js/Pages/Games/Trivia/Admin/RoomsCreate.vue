<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { getRoute } from '@js/routes/triviaRoutes'
import { localInputToUtc } from '@js/composables/useTimezone'
import { useTriviaFeeByCurrency } from '@js/composables/games/trivia/admin/useTriviaFeeByCurrency'
import { useTriviaPrizePayouts } from '@js/composables/games/trivia/admin/useTriviaPrizePayouts'
import { useTriviaPrizeRules } from '@js/composables/games/trivia/admin/useTriviaPrizeRules'
import GameModeSection from '@js/Components/Games/Trivia/Admin/RoomForm/GameModeSection.vue'
import GameSettingsSection from '@js/Components/Games/Trivia/Admin/RoomForm/GameSettingsSection.vue'
import FeeByCurrencyEditor from '@js/Components/Games/Trivia/Admin/RoomForm/FeeByCurrencyEditor.vue'
import PrizeConfigSection from '@js/Components/Games/Trivia/Admin/RoomForm/PrizeConfigSection.vue'
import ScheduledAtField from '@js/Components/Games/Trivia/Admin/RoomForm/ScheduledAtField.vue'
import AdvancedSettingsSection from '@js/Components/Games/Trivia/Admin/RoomForm/AdvancedSettingsSection.vue'

interface Currency { id: string; symbol: string; name: string; iso_code: string }
interface GameMode { value: string; label: string; description: string }

const props = defineProps({
  defaultSettings: Object,
  gameModes: { type: Array as () => GameMode[], default: () => [] },
  categories: Array as () => string[],
  currencies: { type: Array as () => Currency[], default: () => [] },
  errors: Object,
})

const { feeByCurrency, toggleCurrency, getCurrencyById, setFee } = useTriviaFeeByCurrency(() => props.currencies)
const { prizePayouts, addPrizePayout, removePrizePayout } = useTriviaPrizePayouts()

const form = useForm({
  title: '',
  description: '',
  category: '',
  game_mode: 'free_for_all' as 'free_for_all' | 'teams' | 'duel',
  teams_config: {
    team_count: 2,
    team_size: null as number | null,
    duel_type: '2vs2' as '2vs2' | '1vsX',
    team_names: ['', ''] as string[],
  },
  max_players: props.defaultSettings?.max_players || 100,
  question_count: props.defaultSettings?.question_count || 10,
  time_per_question: props.defaultSettings?.time_per_question || 15,
  entry_fee: 0,
  entry_currency_id: '' as string,
  prize_type: 'accumulated' as 'accumulated' | 'fixed',
  prize_amount: 0,
  prize_currency_id: '' as string,
  prize_model: 'guaranteed' as 'guaranteed' | 'pool',
  sponsor_prize: 0,
  platform_rake_percent: 0,
  scheduled_at: '' as string,
  settings: {
    allow_late_join: false,
    show_leaderboard: true,
    public_room: true,
  },
})

const { minScheduledAt, scheduledAtError, sponsorPrizeError, platformRakeError, prizePayoutsError } =
  useTriviaPrizeRules(form, prizePayouts)

// Calcular tiempo estimado del juego
const estimatedTime = computed(() => {
  const seconds = form.question_count * form.time_per_question
  const minutes = Math.ceil(seconds / 60)
  return minutes
})

// Actualizar team_names cuando cambia team_count
function onTeamCountChange() {
  const newCount = form.teams_config.team_count
  const names = form.teams_config.team_names
  if (newCount > names.length) {
    for (let i = names.length; i < newCount; i++) {
      names.push(i < 2 ? ['Equipo Rojo', 'Equipo Azul'][i] : `Equipo ${i + 1}`)
    }
  } else if (newCount < names.length) {
    names.splice(newCount)
  }
  form.teams_config.team_names = names
}

function onGameModeChange(mode: string) {
  form.game_mode = mode as 'free_for_all' | 'teams' | 'duel'
  if (mode === 'duel') {
    form.teams_config.team_count = 2
    onTeamCountChange()
  }
}

const submit = () => {
  if (scheduledAtError.value || sponsorPrizeError.value || platformRakeError.value || prizePayoutsError.value) return

  form.transform((data) => ({
    ...data,
    scheduled_at: localInputToUtc(data.scheduled_at),
    fee_by_currency: feeByCurrency.value,
    prize_payouts: data.prize_model === 'pool' ? prizePayouts.value : null,
  }))

  form.post(getRoute('roomsStore'), {
    onSuccess: () => {
      form.reset()
      feeByCurrency.value = {}
    },
  })
}
</script>

<template>
  <Head title="Crear Sala - Trivia" />

  <div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="getRoute('roomsIndex')"
          class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-2 inline-flex items-center"
        >
          ← Volver al listado
        </Link>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          🎮 Crear Nueva Sala de Trivia
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Configura los parámetros de la sala. El código se generará automáticamente.
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="p-6 space-y-6">
          <!-- Título -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Título de la Sala <span class="text-red-500">*</span>
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              maxlength="255"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Ej: Trivia de Ciencias"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <!-- Descripción -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Descripción
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              maxlength="1000"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Describe el tema o reglas de la sala..."
            />
            <p class="mt-1 text-xs text-gray-500">Máximo 1000 caracteres</p>
          </div>

          <!-- Categoría -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Categoría
            </label>
            <input
              id="category"
              v-model="form.category"
              type="text"
              list="category-list"
              maxlength="50"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Ej: Ciencias, Historia, Deportes..."
            />
            <datalist id="category-list">
              <option v-for="cat in categories" :key="cat" :value="cat" />
            </datalist>
            <p class="mt-1 text-xs text-gray-500">Opcional. Escribe o elige una categoría existente.</p>
          </div>

          <GameModeSection
            :game-modes="gameModes"
            :game-mode="form.game_mode"
            :teams-config="form.teams_config"
            :max-players="form.max_players"
            @update:game-mode="onGameModeChange"
          />

          <GameSettingsSection
            :max-players="form.max_players"
            :question-count="form.question_count"
            :time-per-question="form.time_per_question"
            :estimated-time="estimatedTime"
            @update:max-players="form.max_players = $event"
            @update:question-count="form.question_count = $event"
            @update:time-per-question="form.time_per_question = $event"
          />

          <!-- Entrada y Premio -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
              💳 Entrada y Premio
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              Selecciona las monedas aceptadas y el precio en cada una. Sin selección = sala gratuita.
            </p>

            <div class="space-y-4">
              <FeeByCurrencyEditor
                :currencies="currencies"
                :fee-by-currency="feeByCurrency"
                :get-currency-by-id="getCurrencyById"
                @toggle="toggleCurrency"
                @update:fee="setFee"
              />

              <!-- Premio -->
              <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Premio
                </label>
                <div class="flex gap-2">
                  <input
                    v-model.number="form.prize_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="block w-28 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="0"
                  />
                  <select
                    v-model="form.prize_currency_id"
                    class="block flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="">— Sin moneda —</option>
                    <option v-for="c in currencies" :key="c.id" :value="c.id">
                      {{ c.symbol }} — {{ c.name }}
                    </option>
                  </select>
                </div>
                <div class="mt-2 flex gap-4 text-sm">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="accumulated" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Acumulado</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="fixed" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Fijo</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <PrizeConfigSection
            :prize-model="form.prize_model"
            :sponsor-prize="form.sponsor_prize"
            :platform-rake-percent="form.platform_rake_percent"
            :prize-payouts="prizePayouts"
            :sponsor-prize-error="sponsorPrizeError"
            :sponsor-prize-server-error="form.errors.sponsor_prize"
            :platform-rake-error="platformRakeError"
            :platform-rake-server-error="form.errors.platform_rake_percent"
            :prize-payouts-error="prizePayoutsError"
            :prize-payouts-server-error="form.errors.prize_payouts"
            @update:prize-model="form.prize_model = $event"
            @update:sponsor-prize="form.sponsor_prize = $event"
            @update:platform-rake-percent="form.platform_rake_percent = $event"
            @add-payout="addPrizePayout"
            @remove-payout="removePrizePayout"
          />

          <ScheduledAtField
            :model-value="form.scheduled_at"
            :min="minScheduledAt"
            :error="scheduledAtError"
            :server-error="form.errors.scheduled_at"
            @update:model-value="form.scheduled_at = $event"
          />

          <AdvancedSettingsSection
            :allow-late-join="form.settings.allow_late_join"
            :show-leaderboard="form.settings.show_leaderboard"
            :public-room="form.settings.public_room"
            @update:allow-late-join="form.settings.allow_late_join = $event"
            @update:show-leaderboard="form.settings.show_leaderboard = $event"
            @update:public-room="form.settings.public_room = $event"
          />
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex items-center justify-end gap-3">
          <Link
            :href="getRoute('roomsIndex')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
          >
            <span v-if="form.processing">Creando...</span>
            <span v-else>Crear Sala</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
