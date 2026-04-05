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

import { ref, watch, onMounted, onUnmounted, nextTick, computed } from "vue";

defineOptions({ name: "Modal" });

const props = defineProps<{
  modelValue: boolean;
  id?: string;
  title: string;
  size?: "sm" | "md" | "lg" | "xl";
  static?: boolean;
  showConfirm?: boolean;
  confirmText?: string;
  confirmDisabled?: boolean;

  /**
   * Función opcional para submit del formulario.
   * Debe retornar:
   * - true → cerrar modal
   * - false | throw → mantener abierto
   */
  onSubmit?: () => boolean | Promise<boolean>;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
}>();

const modalRef = ref<HTMLElement | null>(null);
const submitting = ref(false);

const buttonText = computed(() => {
  if (submitting.value) {
    return "Guardando...";
  }
  return props.confirmText ?? "Guardar";
});

let lastFocused: Element | null = null;

const isBouncing = ref(false);

/**
 * Dispara la animación bounce estilo Bootstrap 4 en el div.modal-dialog
 */
const triggerStaticBounce = () => {
  if (!modalRef.value || isBouncing.value) return;
  const dialog = modalRef.value.querySelector<HTMLElement>(".modal-dialog");
  if (!dialog) return;

  isBouncing.value = true;
  dialog.classList.add("modal-static");

  const onAnimationEnd = () => {
    dialog.classList.remove("modal-static");
    isBouncing.value = false;
    dialog.removeEventListener("animationend", onAnimationEnd);
  };

  dialog.addEventListener("animationend", onAnimationEnd);
};

/**
 * Cierra el modal
 * Si static es true, solo cierra desde botones explícitos
 */
const close = () => {
  emit("update:modelValue", false);
};

/**
 * Confirmación con cierre condicional
 */
const confirm = async () => {
  if (!props.onSubmit || submitting.value) return;

  submitting.value = true;

  try {
    const result = await props.onSubmit();
    if (result === true) close();
  } catch {
    // mantener abierto
  } finally {
    submitting.value = false;
  }
};

/**
 * Controla scroll y foco al abrir/cerrar modal
 */
watch(
  () => props.modelValue,
  async (open) => {
    document.body.classList.toggle("modal-open", open);

    if (open) {
      lastFocused = document.activeElement;
      await nextTick();

      const focusable = modalRef.value?.querySelector<HTMLElement>(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      focusable?.focus();
    } else {
      (lastFocused as HTMLElement | null)?.focus();
    }
  }
);

/**
 * Mantiene el foco dentro del modal con Tab y Shift+Tab
 */
const trapFocus = (e: KeyboardEvent) => {
  if (e.key !== "Tab" || !modalRef.value) return;

  const focusable = modalRef.value.querySelectorAll<HTMLElement>(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  );

  if (!focusable.length) return;

  const first = focusable[0];
  const last = focusable[focusable.length - 1];

  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
};

/**
 * Maneja Escape con cierre o animación bounce según static
 */
const onKeydown = (e: KeyboardEvent) => {
  if (e.key === "Escape" && props.modelValue) {
    if (props.static) {
      triggerStaticBounce();
    } else {
      close();
    }
  }
};

/**
 * Clic en backdrop:
 * - Si static: animación bounce
 * - Si no static: cierra modal
 */
const onBackdropClick = () => {
  if (props.static) {
    triggerStaticBounce();
  } else {
    close();
  }
};

onMounted(() => {
  window.addEventListener("keydown", trapFocus);
  window.addEventListener("keydown", onKeydown);
});

onUnmounted(() => {
  document.body.classList.remove("modal-open");
  window.removeEventListener("keydown", trapFocus);
  window.removeEventListener("keydown", onKeydown);
});
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
            <!-- Loading Overlay -->
            <div v-if="submitting" class="modal-loading-overlay">
              <div class="modal-loading-spinner">
                <span class="spinner-border" role="status" aria-hidden="true"></span>
                <span class="sr-only">Procesando...</span>
              </div>
            </div>

            <div class="modal-header">
              <h5 class="modal-title" :id="id ? `${id}-label` : undefined">
                {{ title }}
              </h5>
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
                  {{ buttonText }}
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
  transition: opacity 0.25s ease, transform 0.25s ease;
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
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
}

/* Dialog Bootstrap 4 pattern */
.modal-dialog {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  margin: 1.75rem auto;
}

/* Content con flexbox para distribuir espacio */
.modal-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  max-height: 90vh;
  overflow: hidden;
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Header fijo */
.modal-header {
  flex-shrink: 0;
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
}

/* Body flexible con scroll */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;

  /* Scrollbar estilizado */
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

/* Estilos para webkit (Chrome, Safari, Edge) */
.modal-body::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track {
  background: transparent;
}

.modal-body::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

/* Footer fijo */
.modal-footer {
  flex-shrink: 0;
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
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

/* Loading Overlay - Bloqueante */
.modal-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  border-radius: 0.25rem;
}

.modal-loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.modal-loading-spinner .spinner-border {
  width: 3rem;
  height: 3rem;
  border: 0.3rem solid rgba(0, 123, 255, 0.2);
  border-top-color: #007bff;
  border-radius: 50%;
  animation: spinner-rotate 0.75s linear infinite;
}

.modal-loading-spinner .sr-only {
  color: #6c757d;
  font-size: 0.875rem;
}

@keyframes spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}
</style>
