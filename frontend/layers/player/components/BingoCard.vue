<template>
  <div :class="cardClasses">
    <!-- Header del cartón -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-3 rounded-t-lg">
      <div class="flex justify-between items-center">
        <h3 class="text-white font-bold text-lg">BINGO</h3>
        <span class="text-white/80 text-sm">Cartón #{{ cardNumber }}</span>
      </div>
    </div>

    <!-- Grid del cartón -->
    <div class="p-4 bg-white">
      <!-- Encabezados B-I-N-G-O -->
      <div class="grid grid-cols-5 gap-1 mb-2">
        <div
          v-for="letter in ['B', 'I', 'N', 'G', 'O']"
          :key="letter"
          class="aspect-square flex items-center justify-center bg-gradient-to-br from-purple-600 to-blue-600 text-white font-bold text-xl rounded"
        >
          {{ letter }}
        </div>
      </div>

      <!-- Números del cartón -->
      <div class="grid grid-cols-5 gap-1">
        <div
          v-for="(number, index) in flatNumbers"
          :key="index"
          :class="getCellClasses(number, index)"
          @click="handleCellClick(number, index)"
        >
          <span v-if="number !== null" class="font-bold">{{ number }}</span>
          <Icon v-else name="lucide:star" class="h-6 w-6" />
        </div>
      </div>
    </div>

    <!-- Footer con estado -->
    <div class="p-3 bg-gray-100 rounded-b-lg">
      <div class="flex justify-between items-center text-sm">
        <span :class="statusClasses">
          {{ statusText }}
        </span>
        <span class="text-gray-600">
          {{ markedCount }}/{{ totalCells }} marcados
        </span>
      </div>

      <!-- Botón Bingo (solo si se puede cantar) -->
      <button
        v-if="canCallBingo"
        :disabled="isCallingBingo"
        class="w-full mt-3 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="callBingo"
      >
        <Icon v-if="isCallingBingo" name="lucide:loader-2" class="inline-block h-5 w-5 mr-2 animate-spin" />
        {{ isCallingBingo ? 'Verificando...' : '¡BINGO!' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Componente BingoCard
 *
 * Cartón de bingo interactivo con:
 * - Grid 5x5 de números
 * - Centro libre (FREE)
 * - Marcar/desmarcar números
 * - Verificación de patrones ganadores
 * - Animaciones
 */

interface Props {
  cardNumber: string
  numbers: number[][]
  calledNumbers?: number[]
  autoMark?: boolean
  disabled?: boolean
  status?: 'pending' | 'active' | 'winner' | 'loser'
  highlightPattern?: number[][]
}

const props = withDefaults(defineProps<Props>(), {
  calledNumbers: () => [],
  autoMark: true,
  disabled: false,
  status: 'active',
  highlightPattern: () => []
})

const emit = defineEmits<{
  mark: [number: number, position: { row: number; col: number }]
  unmark: [number: number, position: { row: number; col: number }]
  bingo: []
}>()

// Estado local
const markedCells = ref<Set<string>>(new Set())
const isCallingBingo = ref(false)

// Aplanar números para renderizado
const flatNumbers = computed(() => {
  const flat: (number | null)[] = []
  props.numbers.forEach((row, rowIndex) => {
    row.forEach((num, colIndex) => {
      // Centro es FREE (posición 2,2)
      if (rowIndex === 2 && colIndex === 2) {
        flat.push(null)
      } else {
        flat.push(num)
      }
    })
  })
  return flat
})

// Total de celdas (excluyendo FREE)
const totalCells = computed(() => 24)

// Contador de marcados
const markedCount = computed(() => markedCells.value.size)

// Verificar si puede cantar bingo
const canCallBingo = computed(() => {
  return props.status === 'active' && markedCount.value >= 5 && !props.disabled
})

// Clases del card
const cardClasses = computed(() => {
  const classes = [
    'rounded-lg shadow-lg overflow-hidden transition-all duration-300'
  ]

  if (props.status === 'winner') {
    classes.push('ring-4 ring-green-500 shadow-green-500/50')
  } else if (props.status === 'loser') {
    classes.push('opacity-60 grayscale')
  } else if (props.status === 'active') {
    classes.push('hover:shadow-xl')
  }

  return classes.join(' ')
})

// Texto de estado
const statusText = computed(() => {
  const statuses = {
    pending: 'Esperando inicio',
    active: 'En juego',
    winner: '¡Ganador!',
    loser: 'Finalizado'
  }
  return statuses[props.status]
})

// Clases de estado
const statusClasses = computed(() => {
  const classes = {
    pending: 'text-yellow-600 font-medium',
    active: 'text-blue-600 font-medium',
    winner: 'text-green-600 font-bold animate-pulse',
    loser: 'text-gray-500'
  }
  return classes[props.status]
})

// Obtener clases de celda
const getCellClasses = (number: number | null, index: number) => {
  const row = Math.floor(index / 5)
  const col = index % 5
  const cellKey = `${row}-${col}`
  const isMarked = markedCells.value.has(cellKey) || number === null // FREE siempre marcado
  const isCalled = number !== null && props.calledNumbers.includes(number)
  const isHighlighted = props.highlightPattern[row]?.[col] === 1

  const classes = [
    'aspect-square flex items-center justify-center rounded cursor-pointer transition-all duration-200 text-lg'
  ]

  // Estado FREE
  if (number === null) {
    classes.push('bg-gradient-to-br from-yellow-400 to-orange-500 text-white')
  }
  // Marcado
  else if (isMarked) {
    if (isCalled) {
      classes.push('bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg')
    } else {
      classes.push('bg-gradient-to-br from-blue-500 to-purple-600 text-white')
    }
  }
  // Llamado pero no marcado
  else if (isCalled) {
    classes.push('bg-yellow-200 text-gray-900 animate-pulse ring-2 ring-yellow-500')
  }
  // Normal
  else {
    classes.push('bg-gray-100 text-gray-900 hover:bg-gray-200')
  }

  // Patrón destacado
  if (isHighlighted) {
    classes.push('ring-2 ring-purple-500')
  }

  // Deshabilitado
  if (props.disabled) {
    classes.push('cursor-not-allowed opacity-60')
  }

  return classes.join(' ')
}

// Manejar click en celda
const handleCellClick = (number: number | null, index: number) => {
  if (props.disabled || number === null) return

  const row = Math.floor(index / 5)
  const col = index % 5
  const cellKey = `${row}-${col}`

  if (markedCells.value.has(cellKey)) {
    markedCells.value.delete(cellKey)
    emit('unmark', number, { row, col })
  } else {
    markedCells.value.add(cellKey)
    emit('mark', number, { row, col })
  }
}

// Cantar BINGO
const callBingo = async () => {
  isCallingBingo.value = true
  try {
    emit('bingo')
  } finally {
    setTimeout(() => {
      isCallingBingo.value = false
    }, 1000)
  }
}

// Auto-marcar números llamados
watch(() => props.calledNumbers, (newCalled) => {
  if (!props.autoMark) return

  flatNumbers.value.forEach((number, index) => {
    if (number === null) return // FREE

    const row = Math.floor(index / 5)
    const col = index % 5
    const cellKey = `${row}-${col}`

    if (newCalled.includes(number) && !markedCells.value.has(cellKey)) {
      markedCells.value.add(cellKey)
    }
  })
}, { deep: true })

// Marcar centro FREE al iniciar
onMounted(() => {
  markedCells.value.add('2-2')
})
</script>
