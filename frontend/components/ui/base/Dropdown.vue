<template>
  <div
    ref="dropdownRef"
    class="relative inline-block text-left"
  >
    <div
      class="cursor-pointer"
      @click="toggleDropdown"
    >
      <slot name="trigger" />
    </div>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :class="[
          'absolute z-50 mt-1 min-w-48 bg-white border border-gray-200 rounded-md shadow-lg',
          placementClasses,
        ]"
      >
        <div class="py-1">
          <slot name="content" />
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onClickOutside, onKeyStroke } from '@vueuse/core'

defineOptions({
  name: 'BaseDropdown',
})

interface Props {
  placement?: 'bottom-start' | 'bottom-end' | 'top-start' | 'top-end'
  modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placement: 'bottom-start',
  modelValue: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const isOpen = ref(props.modelValue)
const dropdownRef = ref<HTMLElement>()

const placementClasses = computed(() => {
  switch (props.placement) {
    case 'bottom-end':
      return 'right-0 origin-top-right'
    case 'top-start':
      return 'bottom-full mb-1 left-0 origin-bottom-left'
    case 'top-end':
      return 'bottom-full mb-1 right-0 origin-bottom-right'
    case 'bottom-start':
    default:
      return 'left-0 origin-top-left'
  }
})

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  emit('update:modelValue', isOpen.value)
}

const closeDropdown = () => {
  isOpen.value = false
  emit('update:modelValue', false)
}

// Close dropdown when clicking outside
onClickOutside(dropdownRef, closeDropdown)

// Close dropdown on escape key
onKeyStroke('Escape', closeDropdown)

watch(() => props.modelValue, (value) => {
  isOpen.value = value
})
</script>
