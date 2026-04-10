<script setup lang="ts">
interface Props {
  options: string[]
  selected: number | null
  disabled: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  select: [index: number]
}>()

const OPTION_LABELS = ['A', 'B', 'C', 'D']

function getOptionStyle(index: number) {
  if (props.selected === null) {
    return 'bg-white hover:bg-blue-50 border-gray-300'
  }
  
  if (props.selected === index) {
    return 'bg-blue-600 text-white border-blue-600'
  }
  
  return 'bg-gray-100 text-gray-400 border-gray-200'
}

function handleSelect(index: number) {
  if (!props.disabled) {
    emit('select', index)
  }
}
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <button
      v-for="(option, index) in options"
      :key="index"
      :disabled="disabled"
      @click="handleSelect(index)"
      class="relative p-6 rounded-xl border-2 text-left transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed"
      :class="getOptionStyle(index)"
    >
      <!-- Option Label -->
      <span
        class="absolute top-4 left-4 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm border-2"
        :class="[
          selected === index 
            ? 'bg-white text-blue-600 border-blue-600' 
            : 'bg-gray-200 text-gray-600 border-gray-300'
        ]"
      >
        {{ OPTION_LABELS[index] }}
      </span>

      <!-- Option Text -->
      <span class="block ml-12 text-lg font-medium">
        {{ option }}
      </span>

      <!-- Selected Indicator -->
      <span
        v-if="selected === index"
        class="absolute top-4 right-4"
      >
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
      </span>
    </button>
  </div>

  <!-- Answer Confirmation -->
  <div
    v-if="selected !== null && disabled"
    class="mt-4 p-4 bg-blue-100 rounded-lg text-center"
  >
    <p class="text-blue-800 font-medium">
      ✓ Respuesta enviada
    </p>
    <p class="text-blue-600 text-sm mt-1">
      Esperando a los demás jugadores...
    </p>
  </div>
</template>
