<template>
  <div>
    <!-- Main Item -->
    <component
      :is="item.href ? 'a' : 'button'"
      :href="item.href"
      :class="[
        'w-full flex items-center gap-3 p-3 text-left rounded-xl transition-all duration-200 group',
        active
          ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg'
          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
      ]"
      @click="handleClick"
    >
      <!-- Icon -->
      <div
        :class="[
          'w-6 h-6 flex items-center justify-center rounded-lg transition-all duration-200',
          active
            ? 'bg-white/20 text-white'
            : 'text-gray-400 group-hover:text-gray-600'
        ]"
      >
        <component :is="getIcon(item.icon)" class="w-5 h-5" />
      </div>

      <!-- Label -->
      <span
        :class="[
          'flex-1 font-medium transition-all duration-200',
          active ? 'text-white' : 'text-gray-700 group-hover:text-gray-900'
        ]"
      >
        {{ item.name }}
      </span>

      <!-- Badge -->
      <span
        v-if="item.badge"
        :class="[
          'px-2 py-1 text-xs font-medium rounded-full',
          active
            ? 'bg-white/20 text-white'
            : 'bg-gray-100 text-gray-600 group-hover:bg-gray-200'
        ]"
      >
        {{ item.badge }}
      </span>

      <!-- Expand Arrow -->
      <ChevronDownIcon
        v-if="hasChildren"
        :class="[
          'w-4 h-4 transition-transform duration-200',
          expanded ? 'rotate-180' : '',
          active ? 'text-white' : 'text-gray-400 group-hover:text-gray-600'
        ]"
      />
    </component>

    <!-- Children -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div
        v-if="hasChildren && expanded"
        class="mt-2 ml-6 space-y-1 overflow-hidden"
      >
        <a
          v-for="child in item.children"
          :key="child.name"
          :href="child.href"
          :class="[
            'block p-3 text-sm rounded-lg transition-all duration-200',
            isChildActive(child.href)
              ? 'bg-blue-50 text-blue-700 font-medium'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
          ]"
        >
          <div class="flex items-center gap-3">
            <div class="w-1.5 h-1.5 bg-current rounded-full opacity-60"></div>
            {{ child.name }}
            <span
              v-if="child.badge"
              class="ml-auto px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded-full"
            >
              {{ child.badge }}
            </span>
          </div>
        </a>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import {
  HomeIcon,
  UserIcon,
  CogIcon,
  CreditCardIcon,
  GameController2Icon
} from '@heroicons/vue/24/outline'

interface NavItem {
  name: string
  href?: string
  icon?: string
  badge?: string | number
  children?: Array<{
    name: string
    href: string
    badge?: string | number
  }>
}

interface Props {
  item: NavItem
  active?: boolean
}

const props = defineProps<Props>()
const page = usePage()

const expanded = ref(false)

const hasChildren = computed(() => {
  return props.item.children && props.item.children.length > 0
})

const isChildActive = (href: string) => {
  return page.url.startsWith(href)
}

const getIcon = (iconName?: string) => {
  const icons: Record<string, any> = {
    HomeIcon,
    UserIcon,
    CogIcon,
    CreditCardIcon,
    GameController2Icon
  }
  
  return icons[iconName || 'HomeIcon'] || HomeIcon
}

const handleClick = (event: Event) => {
  if (hasChildren.value) {
    event.preventDefault()
    expanded.value = !expanded.value
  }
}
</script>