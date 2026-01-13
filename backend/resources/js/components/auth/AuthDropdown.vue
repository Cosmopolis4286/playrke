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
<style scoped>
  /* ======================================================
   DROPDOWN
====================================================== */

  .auth-dropdown {
    position: relative;
    width: 100%;
  }

  /* Trigger */
  .auth-dropdown__trigger {
    width: 100%;
    height: var(--input-height);
    padding: 0 42px 0 36px;
    font-size: 0.9em;
    font-weight: var(--light);
    color: var(--gray-3);
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: var(--radius-md);
    text-align: left;
    cursor: pointer;
    transition: border-color 0.3s;
  }

  .auth-dropdown__trigger:hover {
    border-color: var(--input-border-hover);
  }

  .auth-dropdown__trigger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  /* Chevron */
  .auth-dropdown__chevron {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 0.8em;
  }

  /* List */
  .auth-dropdown__list {
    list-style: none; /* quita viñetas */
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 1000;
    width: 100%;
    max-height: 132px;
    overflow-y: auto;
    background-color: var(--input-bg);
    border: 1.5px solid #260053;
    border-radius: var(--radius-md);
  }

  /* Scroll */
  .auth-dropdown__list::-webkit-scrollbar {
    width: 10px;
    background-color: #1d0041;
  }

  .auth-dropdown__list::-webkit-scrollbar-thumb {
    background-color: rgb(0, 0, 124);
    border-radius: 8px;
  }

  /* Item */
  .auth-dropdown__item {
    line-height: 1.1;
    cursor: pointer;
  }

  .auth-dropdown__item:hover {
    background-color: #280157;
  }
  /* ======================================================
   DROPDOWN ANIMATION
====================================================== */

  .auth-dropdown-enter-active,
  .auth-dropdown-leave-active {
    transition:
      opacity 0.15s ease,
      transform 0.15s ease;
  }

  .auth-dropdown-enter-from,
  .auth-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
  }
</style>
