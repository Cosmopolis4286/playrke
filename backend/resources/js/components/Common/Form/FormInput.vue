<script setup lang="ts">
  import { computed, useSlots } from 'vue'

  const props = defineProps({
    container: { type: Boolean, default: false },
    id: { type: String, default: null },
    label: { type: String, default: null },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    minlength: { type: [String, Number], default: null },
    maxlength: { type: [String, Number], default: null },
    placeholder: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false }
  })

  const emit = defineEmits(['update:modelValue'])
  const slots = useSlots()

  const inputId = computed(() => props.id ?? props.name)
  const containerId = computed(() => (props.container ? `container-${inputId.value}` : undefined))

  function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value)
  }
</script>

<template>
  <div :id="containerId" :class="['form-group', col, className]">
    <!-- LABEL (igual que Blade) -->
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <!-- INPUT GROUP -->
    <div v-if="inputGroup" class="input-group">
      <input
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        class="form-control"
        :readonly="readonly"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        @input="onInput"
      />

      <div class="input-group-append" v-if="slots.default">
        <slot />
      </div>
    </div>

    <!-- INPUT SIMPLE -->
    <input
      v-else
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      class="form-control"
      :readonly="readonly"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      @input="onInput"
    />
  </div>
</template>
