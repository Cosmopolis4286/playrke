<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'
  import type { BingoRoomInvitation, InvitationType } from '@js/composables/games/bingo/room/summary/useRoomInvitationsModal'

  const props = defineProps<{
    modelValue: boolean
    room: BingoRoom | null
    invitations: BingoRoomInvitation[]
    loading?: boolean
    saving?: boolean
    error?: string | null
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'add', payload: { type: InvitationType; identifier?: string; phone?: string; label?: string }, done: (ok: boolean) => void): void
    (e: 'remove', invitation: BingoRoomInvitation): void
  }>()

  const type = ref<InvitationType>('player')
  const identifier = ref('')
  const phone = ref('')
  const label = ref('')

  const canAdd = computed(() =>
    type.value === 'player' ? identifier.value.trim().length > 0 : phone.value.replace(/\D/g, '').length >= 7
  )

  const players = computed(() => props.invitations.filter(i => i.type === 'player').length)
  const phones = computed(() => props.invitations.filter(i => i.type === 'phone').length)

  watch(() => props.modelValue, open => {
    if (open) {
      type.value = 'player'
      identifier.value = phone.value = label.value = ''
    }
  })

  function submit(): void {
    if (!canAdd.value || props.saving) return
    const payload = type.value === 'player'
      ? { type: type.value, identifier: identifier.value.trim() }
      : { type: type.value, phone: phone.value.trim(), label: label.value.trim() || undefined }

    emit('add', payload, ok => {
      if (ok) identifier.value = phone.value = label.value = ''
    })
  }
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Invitados de la sala VIP"
    icon="fa-crown"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="room" class="riv">
      <p class="riv__intro">
        Sala <b>{{ room.uuid_code }}</b>. Solo entran su creador, los administradores y esta lista:
        los <b>jugadores registrados</b> la ven en su lobby y compran con su billetera; a los
        <b>compradores externos</b> los atiende un vendedor de tu red, que solo puede venderles si su
        teléfono está aquí.
      </p>

      <!-- Alta -->
      <form class="riv__form" @submit.prevent="submit">
        <div class="riv__tabs" role="tablist">
          <button type="button" class="riv__tab" :class="{ 'riv__tab--on': type === 'player' }" @click="type = 'player'">
            <i class="fas fa-user"></i> Jugador registrado
          </button>
          <button type="button" class="riv__tab" :class="{ 'riv__tab--on': type === 'phone' }" @click="type = 'phone'">
            <i class="fas fa-phone"></i> Comprador externo
          </button>
        </div>

        <div class="riv__fields">
          <template v-if="type === 'player'">
            <input
              v-model="identifier"
              class="form-control form-control-sm"
              maxlength="150"
              placeholder="Usuario o email del jugador"
              autocomplete="off"
            />
          </template>
          <template v-else>
            <input
              v-model="phone"
              class="form-control form-control-sm"
              maxlength="30"
              inputmode="tel"
              placeholder="Teléfono (ej. +58 0414-1234567)"
              autocomplete="off"
            />
            <input
              v-model="label"
              class="form-control form-control-sm"
              maxlength="100"
              placeholder="Nombre (opcional)"
              autocomplete="off"
            />
          </template>
          <button type="submit" class="btn btn-sm btn-primary riv__add" :disabled="!canAdd || saving">
            <i class="fas fa-plus"></i> {{ saving ? 'Agregando…' : 'Agregar' }}
          </button>
        </div>
        <p v-if="error" class="riv__error"><i class="fas fa-circle-exclamation"></i> {{ error }}</p>
      </form>

      <!-- Lista -->
      <div class="riv__summary">
        {{ invitations.length }} invitado(s) · {{ players }} jugador(es) · {{ phones }} teléfono(s)
      </div>

      <p v-if="loading" class="riv__empty"><i class="fas fa-spinner fa-spin"></i> Cargando…</p>
      <p v-else-if="!invitations.length" class="riv__empty">
        Todavía no hay invitados: nadie más que tú y los administradores puede entrar a esta sala.
      </p>
      <ul v-else class="riv__list">
        <li v-for="inv in invitations" :key="inv.id" class="riv__item">
          <span class="riv__icon"><i :class="inv.type === 'player' ? 'fas fa-user' : 'fas fa-phone'"></i></span>
          <span class="riv__who">
            {{ inv.name }}
            <small>{{ inv.detail }}</small>
          </span>
          <button type="button" class="btn btn-sm btn-outline-danger" title="Quitar invitado" @click="emit('remove', inv)">
            <i class="fas fa-trash"></i>
          </button>
        </li>
      </ul>
    </div>
  </Modal>
</template>

<style scoped>
.riv { display: flex; flex-direction: column; gap: 0.75rem; }
.riv__intro { margin: 0; font-size: 0.82rem; color: var(--ds-text-2, #64748b); }

.riv__form { display: flex; flex-direction: column; gap: 0.5rem; }
.riv__tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.riv__tab {
  border: 1px solid var(--ds-border, #e2e8f0); background: transparent; color: var(--ds-text-2, #64748b);
  font-size: 0.78rem; font-weight: 600; padding: 4px 12px; border-radius: 20px;
}
.riv__tab--on { background: #6366f1; border-color: #6366f1; color: #fff; }
.riv__fields { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.riv__fields .form-control { flex: 1 1 180px; min-width: 0; }
.riv__add { flex: 0 0 auto; }
.riv__error { margin: 0; font-size: 0.78rem; color: #dc2626; }

.riv__summary { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ds-text-muted, #94a3b8); }
.riv__empty { margin: 0; font-size: 0.82rem; color: var(--ds-text-muted, #94a3b8); }

.riv__list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; max-height: 320px; overflow-y: auto; }
.riv__item {
  display: flex; align-items: center; gap: 0.6rem;
  padding: 0.45rem 0.75rem; border-radius: 8px; background: var(--ds-surface-2, #f8fafc);
}
.riv__icon { color: #6366f1; width: 18px; text-align: center; }
.riv__who { flex: 1; min-width: 0; display: flex; flex-direction: column; font-size: 0.82rem; color: var(--ds-text, #1e293b); }
.riv__who small { font-size: 0.7rem; color: var(--ds-text-muted, #94a3b8); overflow: hidden; text-overflow: ellipsis; }
</style>
