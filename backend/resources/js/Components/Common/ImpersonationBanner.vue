<script setup lang="ts">
/**
 * Se monta una sola vez fuera del árbol Inertia (mismo patrón que
 * PlatformWinTicker/PendingMovementsBadge: div dedicado en auth-layout.blade.php,
 * montado desde global.ts) — necesario porque no hay un layout Vue único que
 * envuelva todas las páginas, y este banner debe verse en cualquier pantalla
 * a la que navegue el admin mientras impersona a un jugador (ver
 * ImpersonationService).
 */
import { ref } from 'vue'

const props = defineProps<{
  adminName: string
  playerName: string
}>()

const stopping = ref(false)

async function handleStop() {
  stopping.value = true
  try {
    // POST tradicional (no JSON) porque el controlador responde con
    // redirect(), no JSON — un submit de formulario real deja que el
    // navegador siga la redirección normalmente.
    const form = document.createElement('form')
    form.method = 'POST'
    form.action = '/admin/impersonation/stop'

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const csrfInput = document.createElement('input')
    csrfInput.type = 'hidden'
    csrfInput.name = '_token'
    csrfInput.value = csrfToken
    form.appendChild(csrfInput)

    document.body.appendChild(form)
    form.submit()
  } catch (error) {
    console.error(error)
    stopping.value = false
  }
}
</script>

<template>
  <div class="impersonation-banner">
    <i class="fas fa-user-secret"></i>
    <span>
      Estás viendo la plataforma como <strong>{{ playerName }}</strong>
      — sesión de soporte iniciada por {{ adminName }}
    </span>
    <button type="button" class="impersonation-banner__btn" :disabled="stopping" @click="handleStop">
      <span v-if="stopping" class="spinner-border spinner-border-sm me-1"></span>
      <template v-else><i class="fas fa-arrow-left me-1"></i>Volver a mi cuenta</template>
    </button>
  </div>
</template>

<style scoped>
.impersonation-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.6rem 1rem;
  background: linear-gradient(135deg, #d97706, #b45309);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 600;
  flex-wrap: wrap;
  text-align: center;
}
.impersonation-banner__btn {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.85rem;
  border-radius: 8px;
  border: 1.5px solid rgba(255,255,255,.6);
  background: rgba(255,255,255,.15);
  color: #fff;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}
.impersonation-banner__btn:hover:not(:disabled) { background: rgba(255,255,255,.3); }
.impersonation-banner__btn:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
