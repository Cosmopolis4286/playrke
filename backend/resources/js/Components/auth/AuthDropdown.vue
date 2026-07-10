<template>
  <div ref="root" class="auth-dropdown">
    <button type="button" class="auth-dropdown__trigger" :disabled="disabled" @click="toggle">
      {{ selectedLabel || placeholder }}
      <i class="auth-dropdown__chevron fa-solid fa-chevron-down"></i>
    </button>

    <transition name="auth-dropdown">
      <ul v-show="open" class="auth-dropdown__list">
        <li
          v-for="option in options"
          :key="option.value"
          class="auth-dropdown__item"
          @mousedown.prevent="select(option)"
        >
          {{ option.label }}
        </li>
      </ul>
    </transition>

    <input type="hidden" :value="modelValue" />
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

  interface Option {
    value: string | number
    label: string
  }

  const props = defineProps<{
    modelValue: string | number | null
    options: Option[]
    placeholder?: string
    disabled?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void
  }>()

  const root = ref<HTMLElement | null>(null)
  const open = ref(false)

  const selectedLabel = computed(() => {
    return props.options.find(o => o.value === props.modelValue)?.label ?? ''
  })

  const toggle = () => {
    if (props.disabled) return
    open.value = !open.value
  }

  const close = () => {
    open.value = false
  }

  const select = (option: Option) => {
    emit('update:modelValue', option.value)
    close()
  }

  /* Click outside */
  const onClickOutside = (event: MouseEvent) => {
    if (!root.value) return
    if (!root.value.contains(event.target as Node)) {
      close()
    }
  }

  onMounted(() => {
    document.addEventListener('mousedown', onClickOutside)
  })

  onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onClickOutside)
  })
</script>
