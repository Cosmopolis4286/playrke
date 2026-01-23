<script setup lang="ts">
  /**
   * Modal.vue
   * -----------------------------------------------------------------------------
   * Componente Modal reusable inspirado en Bootstrap 4, adaptado a Vue 3.
   *
   * Features:
   * - Control por v-model
   * - Submit async con validación
   * - Cierre solo si submit es exitoso
   * - Animaciones con Transition
   * - Focus trap + restauración de foco
   * - Bloqueo de scroll
   * - Escape / backdrop controlado por `static`
   * - Efecto “bounce” idéntico a Bootstrap 4 cuando static es true
   */

  import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

  defineOptions({ name: 'Modal' })

  const props = defineProps<{
    modelValue: boolean
    id?: string
    title: string
    size?: 'sm' | 'md' | 'lg' | 'xl'
    static?: boolean
    showConfirm?: boolean
    confirmText?: string
    confirmDisabled?: boolean

    /**
     * Función opcional para submit del formulario.
     * Debe retornar:
     * - true → cerrar modal
     * - false | throw → mantener abierto
     */
    onSubmit?: () => boolean | Promise<boolean>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  const modalRef = ref<HTMLElement | null>(null)
  const submitting = ref(false)
  let lastFocused: Element | null = null

  const isBouncing = ref(false)

  /**
   * Dispara la animación bounce estilo Bootstrap 4 en el div.modal-dialog
   */
  const triggerStaticBounce = () => {
    if (!modalRef.value || isBouncing.value) return
    const dialog = modalRef.value.querySelector<HTMLElement>('.modal-dialog')
    if (!dialog) return

    isBouncing.value = true
    dialog.classList.add('modal-static')

    const onAnimationEnd = () => {
      dialog.classList.remove('modal-static')
      isBouncing.value = false
      dialog.removeEventListener('animationend', onAnimationEnd)
    }

    dialog.addEventListener('animationend', onAnimationEnd)
  }

  /**
   * Cierra el modal
   * Si static es true, solo cierra desde botones explícitos
   */
  const close = () => {
    emit('update:modelValue', false)
  }

  /**
   * Confirmación con cierre condicional
   */
  const confirm = async () => {
    if (!props.onSubmit || submitting.value) return

    submitting.value = true

    try {
      const result = await props.onSubmit()
      if (result === true) close()
    } catch {
      // mantener abierto
    } finally {
      submitting.value = false
    }
  }

  /**
   * Controla scroll y foco al abrir/cerrar modal
   */
  watch(
    () => props.modelValue,
    async open => {
      document.body.classList.toggle('modal-open', open)

      if (open) {
        lastFocused = document.activeElement
        await nextTick()

        const focusable = modalRef.value?.querySelector<HTMLElement>(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )
        focusable?.focus()
      } else {
        ;(lastFocused as HTMLElement | null)?.focus()
      }
    }
  )

  /**
   * Mantiene el foco dentro del modal con Tab y Shift+Tab
   */
  const trapFocus = (e: KeyboardEvent) => {
    if (e.key !== 'Tab' || !modalRef.value) return

    const focusable = modalRef.value.querySelectorAll<HTMLElement>(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )

    if (!focusable.length) return

    const first = focusable[0]
    const last = focusable[focusable.length - 1]

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault()
      last.focus()
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault()
      first.focus()
    }
  }

  /**
   * Maneja Escape con cierre o animación bounce según static
   */
  const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.modelValue) {
      if (props.static) {
        triggerStaticBounce()
      } else {
        close()
      }
    }
  }

  /**
   * Clic en backdrop:
   * - Si static: animación bounce
   * - Si no static: cierra modal
   */
  const onBackdropClick = () => {
    if (props.static) {
      triggerStaticBounce()
    } else {
      close()
    }
  }

  onMounted(() => {
    window.addEventListener('keydown', trapFocus)
    window.addEventListener('keydown', onKeydown)
  })

  onUnmounted(() => {
    document.body.classList.remove('modal-open')
    window.removeEventListener('keydown', trapFocus)
    window.removeEventListener('keydown', onKeydown)
  })
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="modelValue"
        ref="modalRef"
        class="modal d-block"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="id ? `${id}-label` : undefined"
        @click.self="onBackdropClick"
      >
        <div class="modal-dialog" :class="size ? `modal-${size}` : ''" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" :id="id ? `${id}-label` : undefined">{{ title }}</h5>
              <!-- Cierre permitido siempre -->
              <button type="button" class="close" @click="close" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>

            <div class="modal-body">
              <slot />
            </div>

            <div class="modal-footer">
              <slot name="footer">
                <!-- Cierre permitido siempre -->
                <button class="btn btn-sm btn-secondary" @click="close">Cerrar</button>

                <button
                  v-if="showConfirm"
                  class="btn btn-sm btn-primary"
                  :disabled="submitting || confirmDisabled"
                  @click="confirm"
                >
                  {{ submitting ? 'Guardando...' : (confirmText ?? 'Guardar') }}
                </button>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div v-if="modelValue" class="modal-backdrop show"></div>
    </Transition>
  </Teleport>
</template>

<style scoped>
  /* Animaciones */
  .modal-fade-enter-active,
  .modal-fade-leave-active {
    transition:
      opacity 0.25s ease,
      transform 0.25s ease;
  }

  .modal-fade-enter-from,
  .modal-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }

  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.2s ease;
  }

  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }

  /* Fondo */
  .modal {
    background: rgba(0, 0, 0, 0.5);
  }

  /* Animación bounce estilo Bootstrap 4 para modal estático */
  @keyframes static-bounce {
    0%,
    100% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.05);
    }
  }

  .modal-static {
    animation: static-bounce 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: center;
  }
</style>
