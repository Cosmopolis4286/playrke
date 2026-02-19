<script setup lang="ts" name="FormSwitch">
  import { computed } from 'vue'

  const props = defineProps({
    /** ID explícito del input */
    id: { type: String, default: null },

    /** Mensaje de error */
    error: { type: String, default: null },

    /** Texto del label */
    label: { type: String, required: true },

    /** Name del campo */
    name: { type: String, required: true },

    /** v-model */
    modelValue: { type: Boolean, default: false },

    /** Deshabilitar */
    disabled: { type: Boolean, default: false },

    /** Layout */
    col: { type: String, default: 'col-12' },
    className: { type: String, default: '' }
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  const inputId = computed(() => props.id ?? props.name)

  const value = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
  })
</script>

<template>
  <div :class="['form-group', col, className]">
    <div class="custom-control custom-switch">
      <input
        :id="inputId"
        :name="name"
        type="checkbox"
        class="custom-control-input"
        :class="{ 'is-invalid': !!error }"
        :disabled="disabled"
        v-model="value"
      />
      <label class="custom-control-label" :for="inputId">
        {{ label }}
      </label>
    </div>

    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
  </div>
</template>
