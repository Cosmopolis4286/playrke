<script setup lang="ts">
  import { computed } from 'vue'
  import { IMaskDirective } from 'vue-imask'
  import type { FactoryArg } from 'imask'
  import { usePasswordToggle } from '@js/composables/utils/usePasswordToggle'

  const vImask = IMaskDirective

  const props = withDefaults(defineProps<{
    modelValue: string
    type?: 'text' | 'email' | 'password' | 'tel'
    icon: string
    placeholder: string
    ariaLabel?: string
    maxlength?: number | string
    required?: boolean
    disabled?: boolean
    error?: string | null
    readonly?: boolean
    mask?: FactoryArg | null
  }>(), {
    type: 'text',
    required: false,
    disabled: false,
    readonly: false,
    error: null,
    mask: null,
  })

  const emit = defineEmits<{ 'update:modelValue': [value: string]; input: [] }>()

  const { showPassword, togglePassword } = usePasswordToggle()

  const resolvedType = computed(() => {
    if (props.type !== 'password') return props.type
    return showPassword.value ? 'text' : 'password'
  })

  function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLInputElement).value)
    emit('input')
  }

  // Máscara IMask: el valor emitido es el formateado (con prefijo/literales),
  // igual que FormInput.vue (maskEmitFormatted) — necesario para campos como
  // teléfono, donde el prefijo "+58 " es parte del valor guardado.
  function onAccept(event: CustomEvent) {
    emit('update:modelValue', event.detail.value)
    emit('input')
  }
</script>

<template>
  <fieldset class="auth-form__field">
    <i class="auth-form__icon fa-solid" :class="icon" aria-hidden="true"></i>
    <input
      v-if="mask"
      v-imask="mask"
      :value="modelValue"
      :type="resolvedType"
      class="auth-form__input"
      :placeholder="placeholder"
      :aria-label="ariaLabel || placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      @accept="onAccept"
    />
    <input
      v-else
      :value="modelValue"
      :type="resolvedType"
      class="auth-form__input"
      :placeholder="placeholder"
      :aria-label="ariaLabel || placeholder"
      :maxlength="maxlength"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      @input="onInput"
    />
    <button
      v-if="type === 'password'"
      type="button"
      class="auth-form__toggle-password"
      :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
      @click="togglePassword"
    >
      <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
    </button>
  </fieldset>
  <p v-if="error" class="auth-form__error">{{ error }}</p>
</template>
