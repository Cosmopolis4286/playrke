<script setup lang="ts">
  import { computed, useSlots } from 'vue'
  import { IMaskDirective } from 'vue-imask'
  import type { FactoryArg } from 'imask'

  const vImask = IMaskDirective

  const props = defineProps({
    container: { type: Boolean, default: false },
    id: { type: String, default: null },
    label: { type: String, default: null },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    minlength: { type: [String, Number], default: null },
    maxlength: { type: [String, Number], default: null },
    min: { type: [String, Number], default: null },
    max: { type: [String, Number], default: null },
    step: { type: [String, Number], default: null },
    placeholder: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false },
    mask: { type: Object as () => FactoryArg, default: null }
  })

  const emit = defineEmits(['update:modelValue'])
  const slots = useSlots()

  const inputId = computed(() => props.id ?? props.name)
  const containerId = computed(() => (props.container ? `container-${inputId.value}` : undefined))

  function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value)
  }

  function onAccept(event: CustomEvent) {
    emit('update:modelValue', event.detail.value)
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
      <div v-if="slots.prepend" class="input-group-prepend">
        <slot name="prepend" />
      </div>

      <input
        v-if="mask"
        v-imask="mask"
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        class="form-control"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        @accept="onAccept"
      />
      <input
        v-else
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        class="form-control"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        @input="onInput"
      />

      <div v-if="slots.append" class="input-group-append">
        <slot name="append" />
      </div>
    </div>

    <!-- INPUT CON MÁSCARA -->
    <input
      v-else-if="mask"
      v-imask="mask"
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      class="form-control"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      @accept="onAccept"
    />

    <!-- INPUT SIMPLE -->
    <input
      v-else
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      class="form-control"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      @input="onInput"
    />
  </div>
</template>
