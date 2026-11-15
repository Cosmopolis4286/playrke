<script setup lang="ts">
/**
 * "Invitar" a una sala privada: enlace directo a la sala de espera (ahí solo
 * se pide la contraseña) y el código para "Tengo un código". La contraseña
 * nunca sale del backend (se guarda cifrada): el anfitrión la comparte por su
 * cuenta, por un canal privado.
 */
import { computed } from 'vue'
import { showToast } from '@js/utils/toastNotify'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'

const props = defineProps<{ room: BingoRoom }>()

const config = computed(() => props.room.config ?? {})
const visible = computed(() => config.value.room_type_code === 'PRIVATE' && !!config.value.access_code)
const isOpen = computed(() => !['FINISHED', 'CANCELLED', 'DELETED'].includes(String(props.room.status)))

const link = computed(() => `${window.location.origin}/bingo/${props.room.id}/lobby`)
const shareText = computed(() =>
  `Te invito a mi sala privada de Bingo 🎱\n${link.value}\nSi te pide código: ${config.value.access_code}\nLa contraseña te la paso por privado.`
)
const whatsappUrl = computed(() => `https://wa.me/?text=${encodeURIComponent(shareText.value)}`)

async function copy(text: string, label: string) {
  try {
    await navigator.clipboard.writeText(text)
    showToast(`${label} copiado.`, 'success')
  } catch {
    showToast('No se pudo copiar; selecciónalo y cópialo a mano.', 'warning')
  }
}
</script>

<template>
  <section v-if="visible && isOpen" class="rd-invite" aria-label="Invitar a la sala privada">
    <h4 class="rd-invite__title">
      <i class="fas fa-user-plus" /> Invitar jugadores
    </h4>

    <div class="rd-invite__row">
      <span class="rd-invite__label">Enlace</span>
      <code class="rd-invite__value">{{ link }}</code>
      <button type="button" class="btn btn-xs btn-outline-secondary" @click="copy(link, 'Enlace')">
        <i class="fas fa-copy" /> Copiar
      </button>
    </div>

    <div class="rd-invite__row">
      <span class="rd-invite__label">Código</span>
      <code class="rd-invite__value">{{ config.access_code }}</code>
      <button type="button" class="btn btn-xs btn-outline-secondary" @click="copy(String(config.access_code), 'Código')">
        <i class="fas fa-copy" /> Copiar
      </button>
    </div>

    <div class="rd-invite__actions">
      <a
        :href="whatsappUrl"
        target="_blank"
        rel="noopener"
        class="btn btn-sm btn-success"
      >
        <i class="fab fa-whatsapp" /> Compartir por WhatsApp
      </a>
      <button type="button" class="btn btn-sm btn-outline-secondary" @click="copy(shareText, 'Invitación')">
        <i class="fas fa-copy" /> Copiar invitación
      </button>
    </div>

    <p class="rd-invite__hint">
      <i class="fas fa-lock" />
      <template v-if="config.has_password">
        La contraseña no se muestra (se guarda cifrada): compártela tú por privado. Si la olvidaste, define una nueva editando la sala.
      </template>
      <template v-else>
        Esta sala todavía no tiene contraseña: edítala y define una antes de activarla.
      </template>
    </p>
  </section>
</template>

<style scoped>
.rd-invite {
  margin: 0.75rem 0;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
}

.rd-invite__title {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
  font-weight: 600;
}

.rd-invite__row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
  flex-wrap: wrap;
}

.rd-invite__label {
  min-width: 4.5rem;
  font-size: 0.8rem;
  opacity: 0.75;
}

.rd-invite__value {
  flex: 1 1 12rem;
  min-width: 0;
  word-break: break-all;
}

.rd-invite__actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin: 0.6rem 0 0.4rem;
}

.rd-invite__hint {
  margin: 0;
  font-size: 0.78rem;
  opacity: 0.8;
}
</style>
