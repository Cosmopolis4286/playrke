<script setup lang="ts" name="Button">
  import type { PropType } from 'vue'

  defineProps({
    /** Ícono FontAwesome (ej. "fa-user-shield"). */
    icon: {
      type: String,
      default: '',
    },

    /** Variante visual (mapea a btn-{variant} / btn-outline-{variant}). */
    variant: {
      type: String as PropType<'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'dark'>,
      default: 'primary',
    },

    /** Estilo outline en vez de sólido. */
    outline: {
      type: Boolean,
      default: false,
    },

    /** Tamaño del botón (btn-sm, btn-md). */
    size: {
      type: String as PropType<'sm' | 'md'>,
      default: 'sm',
    },

    disabled: {
      type: Boolean,
      default: false,
    },

    type: {
      type: String as PropType<'button' | 'submit'>,
      default: 'button',
    },
  })

  defineEmits<{ (e: 'click', ev: MouseEvent): void }>()
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="[
      'btn',
      'app-btn',
      outline ? `btn-outline-${variant}` : `btn-${variant}`,
      size === 'sm' ? 'btn-sm' : '',
    ]"
    @click="$emit('click', $event)"
  >
    <i v-if="icon" :class="['fas', icon]"></i>
    <span v-if="$slots.default"><slot /></span>
  </button>
</template>

<style scoped>
.app-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 600;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.app-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}
.app-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: none;
  box-shadow: none;
}
</style>
