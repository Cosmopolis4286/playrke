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
 * - Efecto "bounce" idéntico a Bootstrap 4 cuando static es true
 * - Soporte de ícono en el header vía prop `icon` o slot `header-icon`
 */

import { ref, watch, onMounted, onUnmounted, nextTick, computed } from "vue";

defineOptions({ name: "Modal", inheritAttrs: false });

const props = defineProps<{
  modelValue: boolean;
  id?: string;
  title: string;
  /** Clase Font Awesome para el ícono del header, ej: "fa-lock", "fa-camera" */
  icon?: string;
  size?: "sm" | "md" | "lg" | "xl";
  static?: boolean;
  showConfirm?: boolean;
  confirmText?: string;
  confirmDisabled?: boolean;
  onSubmit?: () => boolean | Promise<boolean>;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
}>();

const modalRef   = ref<HTMLElement | null>(null);
const submitting = ref(false);

const buttonText = computed(() =>
  submitting.value ? "Guardando..." : (props.confirmText ?? "Guardar")
);

let lastFocused: Element | null = null;
const isBouncing = ref(false);

const triggerStaticBounce = () => {
  if (!modalRef.value || isBouncing.value) return;
  const dialog = modalRef.value.querySelector<HTMLElement>(".modal-dialog");
  if (!dialog) return;
  isBouncing.value = true;
  dialog.classList.add("modal-static");
  const onEnd = () => {
    dialog.classList.remove("modal-static");
    isBouncing.value = false;
    dialog.removeEventListener("animationend", onEnd);
  };
  dialog.addEventListener("animationend", onEnd);
};

const close = () => emit("update:modelValue", false);

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

const trapFocus = (e: KeyboardEvent) => {
  if (e.key !== "Tab" || !modalRef.value) return;
  const focusable = modalRef.value.querySelectorAll<HTMLElement>(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  );
  if (!focusable.length) return;
  const first = focusable[0];
  const last  = focusable[focusable.length - 1];
  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault(); last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault(); first.focus();
  }
};

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === "Escape" && props.modelValue) {
    props.static ? triggerStaticBounce() : close();
  }
};

const onBackdropClick = () => {
  props.static ? triggerStaticBounce() : close();
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
        :id="id"
        ref="modalRef"
        class="modal-overlay"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="id ? `${id}-label` : undefined"
        @click.self="onBackdropClick"
      >
        <div class="modal-dialog" :class="size ? `modal-${size}` : 'modal-md'" role="document">
          <div class="modal-content">

            <!-- Loading overlay -->
            <Transition name="overlay-fade">
              <div v-if="submitting" class="modal-loading-overlay">
                <div class="modal-loading-spinner">
                  <span class="spinner" role="status" aria-hidden="true" />
                  <span class="spinner-text">Procesando...</span>
                </div>
              </div>
            </Transition>

            <!-- ── Header ── -->
            <div class="modal-header">
              <div class="modal-header-left">
                <!-- Slot de ícono o prop icon -->
                <slot name="header-icon">
                  <div v-if="icon" class="modal-header-icon">
                    <i :class="['fas', icon]" />
                  </div>
                </slot>
                <div class="modal-header-text">
                  <h5 class="modal-title" :id="id ? `${id}-label` : undefined">
                    {{ title }}
                  </h5>
                </div>
              </div>
              <button type="button" class="modal-close-btn" @click="close" aria-label="Cerrar">
                <i class="fas fa-times" />
              </button>
            </div>

            <!-- ── Body ── -->
            <div class="modal-body">
              <slot />
            </div>

            <!-- ── Footer ── -->
            <div class="modal-footer">
              <slot name="footer">
                <button class="modal-btn modal-btn--ghost" @click="close">Cerrar</button>
                <button
                  v-if="showConfirm"
                  class="modal-btn modal-btn--primary"
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
      <div v-if="modelValue" class="modal-backdrop-layer" />
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ── Overlay & backdrop ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
  overflow-y: auto;
  overflow-x: hidden;
}

.modal-backdrop-layer {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  z-index: 1040;
}

/* ── Dialog ── */
.modal-dialog {
  position: relative;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  z-index: 1051;
  margin: auto;
}

.modal-sm  { max-width: 440px; }
.modal-md  { max-width: 540px; }
.modal-lg  { max-width: 720px; }
.modal-xl  { max-width: 960px; }

/* ── Content ── */
.modal-content {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 0;
  max-height: calc(100vh - 2rem);
  border-radius: 16px;
  background: var(--ds-surface, #ffffff);
  box-shadow:
    0 4px 6px rgba(0,0,0,0.05),
    0 20px 60px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

/* ── Header ── */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.1rem 1.4rem;
  border-bottom: 1px solid var(--ds-border, #d8dde8);
  background: var(--ds-surface-2, #f4f5fb);
  flex-shrink: 0;
}

.modal-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.modal-header-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea22, #764ba222);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.modal-header-text { min-width: 0; }

.modal-title {
  font-size: 0.975rem;
  font-weight: 700;
  color: var(--ds-text, #1a202c);
  margin: 0;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.modal-close-btn {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--ds-text-muted, #8a94a6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}
.modal-close-btn:hover {
  background: #fee2e2;
  color: #ef4444;
}

/* ── Body ── */
.modal-body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.5rem 1.4rem;
  scrollbar-width: thin;
  scrollbar-color: var(--ds-border-2, #c0c8d8) transparent;
  min-width: 0;
  color: var(--ds-text, #1a202c);
}
.modal-body::-webkit-scrollbar       { width: 6px; }
.modal-body::-webkit-scrollbar-track { background: transparent; }
.modal-body::-webkit-scrollbar-thumb { background: var(--ds-border-2, #c0c8d8); border-radius: 6px; }

/* ── Footer ── */
.modal-footer {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.6rem;
  padding: 1rem 1.4rem;
  border-top: 1px solid var(--ds-border, #d8dde8);
  background: var(--ds-surface-2, #f4f5fb);
  min-height: 64px;
}

/* Default footer buttons */
.modal-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
  line-height: 1.4;
}
.modal-btn--ghost {
  background: transparent;
  color: var(--ds-text-2, #4a5568);
  border: 1.5px solid var(--ds-border-2, #c0c8d8);
}
.modal-btn--ghost:hover { background: var(--ds-surface-2, #f4f5fb); }
.modal-btn--primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  box-shadow: 0 2px 8px rgba(102,126,234,0.3);
}
.modal-btn--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.modal-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

/* ── Loading overlay ── */
.modal-loading-overlay {
  position: absolute;
  inset: 0;
  background: var(--ds-overlay, rgba(255,255,255,.88));
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  border-radius: 16px;
}
.modal-loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}
.spinner {
  display: block;
  width: 2.5rem;
  height: 2.5rem;
  border: 3px solid rgba(102,126,234,0.2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.spinner-text { font-size: 0.8rem; color: var(--ds-text-2, #4a5568); font-weight: 500; }

/* ── Animations ── */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: translateY(-12px) scale(0.98);
}

.overlay-fade-enter-active,
.overlay-fade-leave-active { transition: opacity 0.15s; }
.overlay-fade-enter-from,
.overlay-fade-leave-to     { opacity: 0; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.22s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }

/* Bounce estático */
@keyframes static-bounce {
  0%, 100% { transform: scale(1); }
  40%       { transform: scale(1.04); }
  70%       { transform: scale(0.98); }
}
.modal-static {
  animation: static-bounce 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
