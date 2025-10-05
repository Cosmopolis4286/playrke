<template>
  <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 shadow-2xl border border-slate-700">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-2xl font-bold text-white">Tablero de Números</h3>
      <div class="text-right">
        <p class="text-gray-400 text-sm">Números cantados</p>
        <p class="text-3xl font-bold text-green-400">{{ calledNumbers.length }}/75</p>
      </div>
    </div>

    <!-- Último número cantado -->
    <div v-if="currentNumber" class="mb-6 p-6 bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl text-center">
      <p class="text-white/80 text-sm mb-2">ÚLTIMO NÚMERO</p>
      <div class="text-7xl font-bold text-white animate-pulse">
        {{ currentNumber }}
      </div>
      <p class="text-white/80 text-lg mt-2">{{ getNumberLetter(currentNumber) }}</p>
    </div>

    <!-- Grid de todos los números (1-75) -->
    <div class="grid grid-cols-5 gap-2">
      <!-- Columna B (1-15) -->
      <div>
        <div class="text-center text-white font-bold text-xl mb-2 bg-purple-600 rounded p-2">
          B
        </div>
        <div class="space-y-1">
          <div
            v-for="num in getBColumn"
            :key="num"
            :class="getNumberClasses(num)"
          >
            {{ num }}
          </div>
        </div>
      </div>

      <!-- Columna I (16-30) -->
      <div>
        <div class="text-center text-white font-bold text-xl mb-2 bg-blue-600 rounded p-2">
          I
        </div>
        <div class="space-y-1">
          <div
            v-for="num in getIColumn"
            :key="num"
            :class="getNumberClasses(num)"
          >
            {{ num }}
          </div>
        </div>
      </div>

      <!-- Columna N (31-45) -->
      <div>
        <div class="text-center text-white font-bold text-xl mb-2 bg-green-600 rounded p-2">
          N
        </div>
        <div class="space-y-1">
          <div
            v-for="num in getNColumn"
            :key="num"
            :class="getNumberClasses(num)"
          >
            {{ num }}
          </div>
        </div>
      </div>

      <!-- Columna G (46-60) -->
      <div>
        <div class="text-center text-white font-bold text-xl mb-2 bg-yellow-600 rounded p-2">
          G
        </div>
        <div class="space-y-1">
          <div
            v-for="num in getGColumn"
            :key="num"
            :class="getNumberClasses(num)"
          >
            {{ num }}
          </div>
        </div>
      </div>

      <!-- Columna O (61-75) -->
      <div>
        <div class="text-center text-white font-bold text-xl mb-2 bg-red-600 rounded p-2">
          O
        </div>
        <div class="space-y-1">
          <div
            v-for="num in getOColumn"
            :key="num"
            :class="getNumberClasses(num)"
          >
            {{ num }}
          </div>
        </div>
      </div>
    </div>

    <!-- Últimos 5 números llamados -->
    <div v-if="lastFiveCalled.length > 0" class="mt-6 p-4 bg-slate-800/50 rounded-lg">
      <p class="text-gray-400 text-sm mb-3">Últimos números cantados</p>
      <div class="flex justify-center space-x-3">
        <div
          v-for="num in lastFiveCalled"
          :key="num"
          class="w-12 h-12 flex items-center justify-center bg-gradient-to-br from-green-500 to-emerald-600 text-white font-bold text-lg rounded-lg shadow-lg"
        >
          {{ num }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Componente BingoBoard
 *
 * Tablero que muestra todos los números de bingo (1-75)
 * organizados por columnas B-I-N-G-O, destacando los números llamados.
 */

interface Props {
  calledNumbers: number[]
  currentNumber?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  currentNumber: null
})

// Columnas de números
const getBColumn = computed(() => Array.from({ length: 15 }, (_, i) => i + 1))
const getIColumn = computed(() => Array.from({ length: 15 }, (_, i) => i + 16))
const getNColumn = computed(() => Array.from({ length: 15 }, (_, i) => i + 31))
const getGColumn = computed(() => Array.from({ length: 15 }, (_, i) => i + 46))
const getOColumn = computed(() => Array.from({ length: 15 }, (_, i) => i + 61))

// Últimos 5 números llamados
const lastFiveCalled = computed(() => {
  return props.calledNumbers.slice(-5).reverse()
})

// Obtener letra del número
const getNumberLetter = (num: number): string => {
  if (num >= 1 && num <= 15) return 'B'
  if (num >= 16 && num <= 30) return 'I'
  if (num >= 31 && num <= 45) return 'N'
  if (num >= 46 && num <= 60) return 'G'
  if (num >= 61 && num <= 75) return 'O'
  return ''
}

// Clases del número
const getNumberClasses = (num: number) => {
  const isCalled = props.calledNumbers.includes(num)
  const isCurrent = num === props.currentNumber

  const classes = [
    'text-center py-2 rounded font-semibold transition-all duration-300'
  ]

  if (isCurrent) {
    classes.push('bg-gradient-to-r from-purple-600 to-blue-600 text-white animate-pulse scale-110 shadow-lg')
  } else if (isCalled) {
    classes.push('bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-md')
  } else {
    classes.push('bg-slate-700/50 text-gray-400')
  }

  return classes.join(' ')
}
</script>
