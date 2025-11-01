<template>
  <div
    class="image-container"
    :class="containerClasses"
  >
    <!-- Main Image -->
    <img
      v-if="!hasError && shouldLoad"
      ref="imgRef"
      :src="computedSrc"
      :alt="alt"
      :loading="eager ? 'eager' : 'lazy'"
      :decoding="decoding"
      :sizes="sizes"
      :srcset="srcset"
      :class="imageClasses"
      :style="imageStyles"
      @load="handleLoad"
      @error="handleError"
      @click="handleClick"
    >

    <!-- Placeholder/Loading State -->
    <div
      v-else-if="!hasError && !isLoaded"
      :class="placeholderClasses"
      :style="placeholderStyles"
    >
      <slot name="placeholder">
        <div
          v-if="showPlaceholder"
          class="placeholder-content"
        >
          <!-- Loading Animation -->
          <div
            v-if="loading && showLoader"
            class="placeholder-loader"
          >
            <div
              class="animate-pulse bg-gray-300 rounded"
              :style="placeholderDimensions"
            />
          </div>

          <!-- Custom Placeholder -->
          <div
            v-else-if="placeholder"
            class="placeholder-custom"
          >
            <img
              :src="placeholder"
              :alt="`${alt} placeholder`"
              :class="imageClasses"
              :style="imageStyles"
            >
          </div>

          <!-- Default Icon Placeholder -->
          <div
            v-else
            class="placeholder-default"
          >
            <SharedIcon
              :name="placeholderIcon"
              :class="placeholderIconClasses"
            />
            <span
              v-if="placeholderText"
              class="placeholder-text"
            >
              {{ placeholderText }}
            </span>
          </div>
        </div>
      </slot>
    </div>

    <!-- Error State -->
    <div
      v-else-if="hasError"
      :class="errorClasses"
      :style="errorStyles"
    >
      <slot name="error">
        <div class="error-content">
          <SharedIcon
            name="warning"
            class="w-8 h-8 text-red-400 mb-2"
          />
          <span class="text-sm text-red-600">
            {{ errorText || 'Error al cargar imagen' }}
          </span>
          <button
            v-if="retryable"
            class="@apply mt-2 text-xs text-red-500 hover:text-red-700 underline"
            @click="retry"
          >
            Reintentar
          </button>
        </div>
      </slot>
    </div>

    <!-- Overlay Content -->
    <div
      v-if="$slots.overlay && (isLoaded || hasError)"
      class="image-overlay"
      :class="overlayClasses"
    >
      <slot
        name="overlay"
        :loaded="isLoaded"
        :error="hasError"
      />
    </div>

    <!-- Caption -->
    <figcaption
      v-if="caption && (isLoaded || hasError)"
      class="image-caption"
      :class="captionClasses"
    >
      {{ caption }}
    </figcaption>

    <!-- Progressive Enhancement Layer -->
    <div
      v-if="progressive && isLoading"
      class="image-progressive"
      :style="progressiveStyles"
    />
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview Image - Componente de imagen optimizada
 * @description Imagen inteligente con lazy loading, placeholders, manejo de errores y optimizaciones avanzadas
 */

import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

interface Props {
  // Core
  src: string
  alt: string
  width?: number | string
  height?: number | string

  // Loading
  lazy?: boolean
  eager?: boolean
  loading?: boolean
  decoding?: 'async' | 'sync' | 'auto'

  // Responsive
  sizes?: string
  srcset?: string
  breakpoints?: Record<string, string>

  // Placeholder
  placeholder?: string
  placeholderIcon?: string
  placeholderText?: string
  showPlaceholder?: boolean
  showLoader?: boolean

  // Error handling
  fallback?: string
  errorText?: string
  retryable?: boolean
  maxRetries?: number

  // Styling
  fit?: 'contain' | 'cover' | 'fill' | 'none' | 'scale-down'
  position?: string
  rounded?: boolean | 'sm' | 'md' | 'lg' | 'xl' | 'full'
  border?: boolean
  shadow?: boolean | 'sm' | 'md' | 'lg' | 'xl'

  // Behavior
  clickable?: boolean
  zoomOnHover?: boolean
  grayscale?: boolean
  blur?: boolean | number

  // Advanced
  progressive?: boolean
  quality?: number
  format?: 'webp' | 'avif' | 'jpg' | 'png'

  // Caption
  caption?: string

  // Intersection Observer
  rootMargin?: string
  threshold?: number

  // CDN Integration
  cdnProvider?: 'cloudinary' | 'imagekit' | 'custom'
  cdnTransformations?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  lazy: true,
  eager: false,
  loading: false,
  decoding: 'async',
  showPlaceholder: true,
  showLoader: true,
  placeholderIcon: 'PhotoIcon',
  retryable: true,
  maxRetries: 3,
  fit: 'cover',
  position: 'center',
  rounded: false,
  border: false,
  shadow: false,
  clickable: false,
  zoomOnHover: false,
  grayscale: false,
  blur: false,
  progressive: false,
  quality: 80,
  format: 'webp',
  rootMargin: '50px',
  threshold: 0.1,
  cdnProvider: 'custom',
})

const emit = defineEmits<{
  load: [event: Event]
  error: [event: Event]
  click: [event: Event]
  retry: []
}>()

// State
const imgRef = ref<HTMLImageElement>()
const isLoaded = ref(false)
const isLoading = ref(false)
const hasError = ref(false)
const shouldLoad = ref(!props.lazy)
const retryCount = ref(0)
const observer = ref<IntersectionObserver>()

// Computed
const computedSrc = computed(() => {
  if (!props.src) { return '' }

  // Apply CDN transformations
  if (props.cdnProvider && props.cdnTransformations) {
    return applyCdnTransformations(props.src)
  }

  return props.src
})

const imageClasses = computed(() => {
  const classes = ['image']

  // Fit
  classes.push(`object-${props.fit}`)

  // Position
  if (props.position && props.position !== 'center') {
    classes.push(`object-${props.position}`)
  } else {
    classes.push('object-center')
  }

  // Rounded
  if (props.rounded) {
    if (typeof props.rounded === 'boolean') {
      classes.push('rounded')
    } else {
      classes.push(`rounded-${props.rounded}`)
    }
  }

  // Border
  if (props.border) {
    classes.push('border border-gray-200')
  }

  // Shadow
  if (props.shadow) {
    if (typeof props.shadow === 'boolean') {
      classes.push('shadow')
    } else {
      classes.push(`shadow-${props.shadow}`)
    }
  }

  // Interactive
  if (props.clickable) {
    classes.push('cursor-pointer transition-transform duration-200 hover:scale-105')
  }

  if (props.zoomOnHover) {
    classes.push('transition-transform duration-300 hover:scale-110')
  }

  // Filters
  if (props.grayscale) {
    classes.push('grayscale hover:grayscale-0 transition-all duration-300')
  }

  if (props.blur) {
    const blurValue = typeof props.blur === 'number' ? props.blur : 4
    classes.push(`blur-${blurValue}`)
  }

  return classes
})

const imageStyles = computed(() => {
  const styles: Record<string, string> = {}

  if (props.width) {
    styles.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }

  if (props.height) {
    styles.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }

  return styles
})

const containerClasses = computed(() => [
  'image-container',
  'relative',
  'overflow-hidden',
  {
    'inline-block': !props.width && !props.height,
    'block': props.width || props.height,
  },
])

const placeholderClasses = computed(() => [
  'placeholder',
  'flex items-center justify-center',
  'bg-gray-100',
  'text-gray-400',
  ...imageClasses.value,
])

const placeholderStyles = computed(() => ({
  ...imageStyles.value,
  minHeight: props.height ? (typeof props.height === 'number' ? `${props.height}px` : props.height) : '200px',
}))

const placeholderDimensions = computed(() => ({
  width: '100%',
  height: '100%',
  minHeight: '100px',
}))

const placeholderIconClasses = computed(() => {
  const size = props.width && props.height ? 'w-8 h-8' : 'w-12 h-12'
  return [size, 'text-gray-300'].join(' ')
})

const errorClasses = computed(() => [
  'error-state',
  'flex flex-col items-center justify-center',
  'bg-red-50 text-center',
  ...imageClasses.value,
])

const errorStyles = computed(() => ({
  ...imageStyles.value,
  minHeight: props.height ? (typeof props.height === 'number' ? `${props.height}px` : props.height) : '200px',
}))

const overlayClasses = computed(() => [
  'absolute inset-0',
  'flex items-center justify-center',
  'bg-black bg-opacity-50',
  'opacity-0 hover:opacity-100',
  'transition-opacity duration-300',
])

const captionClasses = computed(() => [
  'mt-2 text-sm text-gray-600 text-center',
])

const progressiveStyles = computed(() => ({
  position: 'absolute' as const,
  top: '0px',
  left: '0px',
  width: '100%',
  height: '100%',
  background: 'linear-gradient(45deg, rgba(0,0,0,0.1) 25%, transparent 25%, transparent 75%, rgba(0,0,0,0.1) 75%)',
  backgroundSize: '20px 20px',
  animation: 'progressive-loading 1s linear infinite',
}))

// Methods
const applyCdnTransformations = (src: string): string => {
  if (!props.cdnTransformations || Object.keys(props.cdnTransformations).length === 0) {
    return src
  }

  // Example for Cloudinary
  if (props.cdnProvider === 'cloudinary') {
    const transformations = []

    if (props.width) { transformations.push(`w_${props.width}`) }
    if (props.height) { transformations.push(`h_${props.height}`) }
    if (props.quality) { transformations.push(`q_${props.quality}`) }
    if (props.format) { transformations.push(`f_${props.format}`) }

    // Custom transformations
    Object.entries(props.cdnTransformations).forEach(([key, value]) => {
      transformations.push(`${key}_${value}`)
    })

    // Insert transformations into Cloudinary URL
    if (transformations.length > 0) {
      return src.replace('/upload/', `/upload/${transformations.join(',')}/`)
    }
  }

  return src
}

const setupIntersectionObserver = () => {
  if (!props.lazy || typeof window === 'undefined') { return }

  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          shouldLoad.value = true
          observer.value?.disconnect()
        }
      })
    },
    {
      rootMargin: props.rootMargin,
      threshold: props.threshold,
    },
  )

  nextTick(() => {
    if (imgRef.value?.parentElement) {
      observer.value?.observe(imgRef.value.parentElement)
    }
  })
}

const handleLoad = (event: Event) => {
  isLoaded.value = true
  isLoading.value = false
  hasError.value = false
  emit('load', event)
}

const handleError = (event: Event) => {
  isLoading.value = false

  if (props.fallback && retryCount.value === 0) {
    // Try fallback image first
    retryCount.value++
    nextTick(() => {
      if (imgRef.value) {
        imgRef.value.src = props.fallback!
      }
    })
    return
  }

  hasError.value = true
  emit('error', event)
}

const handleClick = (event: Event) => {
  if (props.clickable) {
    emit('click', event)
  }
}

const retry = () => {
  if (retryCount.value >= props.maxRetries) { return }

  retryCount.value++
  hasError.value = false
  isLoading.value = true

  emit('retry')

  nextTick(() => {
    if (imgRef.value) {
      imgRef.value.src = computedSrc.value
    }
  })
}

// Lifecycle
onMounted(() => {
  if (props.lazy) {
    setupIntersectionObserver()
  } else {
    isLoading.value = true
  }
})

onBeforeUnmount(() => {
  observer.value?.disconnect()
})

// Watchers
watch(() => props.src, () => {
  isLoaded.value = false
  hasError.value = false
  retryCount.value = 0

  if (!props.lazy) {
    isLoading.value = true
  }
})

watch(shouldLoad, (newValue) => {
  if (newValue) {
    isLoading.value = true
  }
})
</script>

<style scoped>
.image-container {
  @apply relative;
}

.image {
  @apply w-full h-full;
}

.placeholder {
  @apply select-none;
}

.placeholder-content {
  @apply flex flex-col items-center justify-center w-full h-full;
}

.placeholder-text {
  @apply mt-2 text-xs text-gray-500;
}

.error-content {
  @apply flex flex-col items-center justify-center w-full h-full p-4;
}

.image-overlay {
  @apply pointer-events-none;
}

.image-overlay > * {
  @apply pointer-events-auto;
}

.image-caption {
  @apply block;
}

/* Progressive loading animation */
@keyframes progressive-loading {
  0% {
    background-position: 0 0;
  }
  100% {
    background-position: 20px 20px;
  }
}

.image-progressive {
  pointer-events: none;
}

/* Responsive behavior */
@media (max-width: 640px) {
  .image-container {
    @apply w-full;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .image,
  .image-overlay,
  .placeholder {
    @apply transition-none;
  }

  .image-progressive {
    @apply hidden;
  }
}

/* Print styles */
@media print {
  .image-overlay,
  .placeholder {
    @apply hidden;
  }
}
</style>
