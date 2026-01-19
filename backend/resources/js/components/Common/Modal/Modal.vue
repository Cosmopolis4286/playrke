<script setup lang="ts">
/**
 * Modal.vue
 * -----------------------------------------------------------------------------
 * Componente Modal reusable inspirado en Bootstrap, adaptado a Vue 3.
 *
 * Features:
 * - Control por v-model
 * - Submit async con validación
 * - Cierre solo si submit es exitoso
 * - Animaciones con Transition
 * - Focus trap + restauración de foco
 * - Bloqueo de scroll
 * - Escape / backdrop controlado por `static`
 *
 * Recomendado para:
 * - Formularios
 * - Confirmaciones
 * - Flujos CRUD
 */

import { ref, watch, onMounted, onUnmounted, nextTick } from "vue";

defineOptions({ name: "Modal" });

/**
 * Props del Modal
 */
const props = defineProps<{
  modelValue: boolean;
  id?: string;
  title: string;
  size?: "sm" | "md" | "lg" | "xl";
  static?: boolean;
  showConfirm?: boolean;
  confirmText?: string;
  loading?: boolean;

  /**
   * Función opcional para submit del formulario.
   * Debe retornar:
   * - true → cerrar modal
   * - false | throw → mantener abierto
   */
  onSubmit?: () => boolean | Promise<boolean>;
}>();

/**
 * Eventos emitidos
 */
const emit = defineEmits<{
  "update:modelValue": [value: boolean];
}>();

/**
 * Refs
 */
const modalRef = ref<HTMLElement | null>(null);
let lastFocused: Element | null = null;

/**
 * Cierra el modal
 */
const close = () => {
  emit("update:modelValue", false);
};

/**
 * Ejecuta submit y solo cierra si es OK
 */
const confirm = async () => {
  if (!props.onSubmit) {
    close();
    return;
  }

  try {
    const result = await props.onSubmit();
    if (result === true) close();
  } catch {
    // No cerrar modal si hay error
  }
};

/**
 * Bloquear scroll + manejar foco
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
 * Focus trap (TAB dentro del modal)
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
 * Escape key handler
 */
const onKeydown = (e: KeyboardEvent) => {
  if (e.key === "Escape" && props.modelValue && !props.static) {
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
    <!-- Modal -->
    <Transition name="modal-fade">
      <div
        v-if="modelValue"
        ref="modalRef"
        class="modal d-block"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="id ? `${id}-label` : undefined"
        @click.self="!static && close()"
      >
        <div class="modal-dialog" :class="size && `modal-${size}`" role="document">
          <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
              <h5 class="modal-title" :id="id ? `${id}-label` : undefined">
                {{ title }}
              </h5>
              <button type="button" class="close" @click="close">
                <span>&times;</span>
              </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
              <slot />
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <slot name="footer">
                <button class="btn btn-sm btn-secondary" @click="close">Cerrar</button>

                <button
                  v-if="showConfirm"
                  class="btn btn-sm btn-primary"
                  :disabled="loading"
                  @click="confirm"
                >
                  {{ loading ? "Guardando..." : confirmText ?? "Guardar" }}
                </button>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
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
}
</style>
