<script setup lang="ts">
defineProps<{
  modelValue: string
  min: string
  error?: string
  serverError?: string
  disabled?: boolean
  showClearButton?: boolean
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div class="saf-border-t pt-6">
    <h3 class="text-lg font-medium saf-text mb-4">
      📅 Fecha y Hora de Inicio
    </h3>

    <div>
      <label for="scheduled_at" class="block text-sm font-medium saf-text-secondary">
        Inicio Automático
      </label>
      <input
        id="scheduled_at"
        :value="modelValue"
        type="datetime-local"
        :min="min"
        :disabled="disabled"
        :aria-invalid="Boolean(error || serverError)"
        class="saf-input mt-1 block w-full rounded-md focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
        :class="(error || serverError) ? 'border-red-300 dark:border-red-700 focus:border-red-500' : 'saf-input-border focus:border-indigo-500'"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <p class="mt-1 text-xs saf-text-muted">Deja vacío para inicio manual. Si especificas fecha y hora, la sala se iniciará automáticamente.</p>
      <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
      <p v-else-if="serverError" class="mt-1 text-sm text-red-600">{{ serverError }}</p>
      <button
        v-if="showClearButton && modelValue"
        type="button"
        @click="$emit('update:modelValue', '')"
        class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
      >
        Quitar fecha (inicio manual)
      </button>
    </div>
  </div>
</template>

<style scoped>
.saf-border-t { border-top: 1px solid var(--ds-border); }
.saf-text { color: var(--ds-text); }
.saf-text-secondary { color: var(--ds-text-2); }
.saf-text-muted { color: var(--ds-text-muted); }
.saf-input { background: var(--ds-surface); color: var(--ds-text-2); }
.saf-input-border { border-color: var(--ds-border); }
</style>
