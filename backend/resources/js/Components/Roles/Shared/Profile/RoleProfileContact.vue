<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import RpCard from './RpCard.vue'
import FormInput from '@/js/Components/Common/Form/FormInput.vue'
import type { useRoleProfile } from '@/js/composables/roles/shared/profile/useRoleProfile'

type Editor = ReturnType<typeof useRoleProfile>

const props = defineProps<{ editor: Editor }>()

const profile = computed(() => props.editor.profile.value)
const formRef = ref<HTMLFormElement | null>(null)

/** Error del servidor si lo hay; si no, el de la validación local (el form arranca con los datos actuales, válidos). */
function fieldError(field: 'name' | 'phone'): string | null {
  return props.editor.errors.value[field] ?? props.editor.localErrors.value[field] ?? null
}

async function startEdit() {
  props.editor.startEdit()
  await nextTick()
  formRef.value?.querySelector<HTMLInputElement>('input')?.focus()
}

const subsidiaryCurrency = computed(() => (profile.value.role === 'subsidiary' ? profile.value.currency : null))

const copied = ref<string | null>(null)
async function copy(value: string, key: string) {
  try {
    await navigator.clipboard.writeText(value)
    copied.value = key
    setTimeout(() => { if (copied.value === key) copied.value = null }, 1500)
  } catch { /* portapapeles no disponible: sin feedback */ }
}
</script>

<template>
  <RpCard title="Datos de contacto" icon="fa-address-card">
    <template #actions>
      <Transition name="rp-fade">
        <span v-if="editor.justSaved.value" class="rp-saved"><i class="fas fa-circle-check"></i>Guardado</span>
      </Transition>
      <button v-if="!editor.editMode.value" type="button" class="rp-btn rp-btn--ghost" @click="startEdit">
        <i class="fas fa-pen"></i>Editar
      </button>
    </template>

    <form
      v-if="editor.editMode.value"
      ref="formRef"
      class="row rp-form"
      novalidate
      @submit.prevent="editor.save"
      @keydown.esc.prevent="editor.cancelEdit"
    >
      <FormInput
        label="Nombre comercial"
        name="name"
        required
        col="col-12"
        maxlength="30"
        :disabled="editor.saving.value"
        :error="fieldError('name')"
        v-model="editor.form.value.name"
      />
      <FormInput
        label="Teléfono"
        name="phone"
        required
        col="col-12"
        maxlength="20"
        :disabled="editor.saving.value"
        :error="fieldError('phone')"
        v-model="editor.form.value.phone"
      />
      <div class="col-12 rp-form__actions">
        <span class="rp-form__hint">Esc para cancelar</span>
        <button type="button" class="rp-btn rp-btn--ghost" :disabled="editor.saving.value" @click="editor.cancelEdit">Cancelar</button>
        <button type="submit" class="rp-btn rp-btn--primary" :disabled="!editor.canSave.value">
          <i :class="['fas', editor.saving.value ? 'fa-spinner fa-spin' : 'fa-check']"></i>
          {{ editor.saving.value ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </form>

    <dl v-else class="rp-fields">
      <div class="rp-field">
        <dt><i class="fas fa-signature"></i>Nombre comercial</dt>
        <dd>{{ profile.name }}</dd>
      </div>
      <div class="rp-field">
        <dt><i class="fas fa-phone"></i>Teléfono</dt>
        <dd class="rp-field__copy">
          <a :href="`tel:${profile.phone.replace(/[^\d+]/g, '')}`">{{ profile.phone }}</a>
          <button type="button" class="rp-copy" :title="copied === 'phone' ? 'Copiado' : 'Copiar'" @click="copy(profile.phone, 'phone')">
            <i :class="['fas', copied === 'phone' ? 'fa-check' : 'fa-copy']"></i>
          </button>
        </dd>
      </div>
      <div class="rp-field">
        <dt><i class="fas fa-envelope"></i>Email</dt>
        <dd class="rp-field__copy">
          <span class="rp-field__ellipsis">{{ profile.user.email }}</span>
          <button type="button" class="rp-copy" :title="copied === 'email' ? 'Copiado' : 'Copiar'" @click="copy(profile.user.email, 'email')">
            <i :class="['fas', copied === 'email' ? 'fa-check' : 'fa-copy']"></i>
          </button>
        </dd>
      </div>
      <div class="rp-field">
        <dt><i class="fas fa-flag"></i>País</dt>
        <dd>{{ profile.country ?? '—' }}</dd>
      </div>
      <div v-if="subsidiaryCurrency" class="rp-field">
        <dt><i class="fas fa-coins"></i>Moneda</dt>
        <dd>{{ subsidiaryCurrency }}</dd>
      </div>
    </dl>

    <p class="rp-note">
      <i class="fas fa-circle-info"></i>
      El email y el país se cambian desde <a href="/profile">Mi cuenta</a> o con soporte.
    </p>
  </RpCard>
</template>

<style scoped>
.rp-fields { display: flex; flex-direction: column; gap: 0.75rem; margin: 0; }
.rp-field dt {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
  color: var(--ds-text-muted);
}
.rp-field dt i { font-size: 0.66rem; }
.rp-field dd { margin: 0.15rem 0 0; font-size: 0.9rem; font-weight: 600; color: var(--ds-text); min-width: 0; }
.rp-field dd a { color: inherit; text-decoration: none; }
.rp-field dd a:hover { text-decoration: underline; }
.rp-field__copy { display: flex; align-items: center; gap: 0.4rem; }
.rp-field__ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rp-copy {
  flex-shrink: 0;
  width: 26px; height: 26px;
  display: inline-flex; align-items: center; justify-content: center;
  border: none; border-radius: 7px;
  background: transparent; color: var(--ds-text-muted);
  font-size: 0.72rem; cursor: pointer;
}
.rp-copy:hover { background: var(--ds-surface-2); color: var(--ds-text); }

.rp-form { margin-top: -0.25rem; }
.rp-form__actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; }
.rp-form__hint { margin-right: auto; font-size: 0.7rem; color: var(--ds-text-muted); }

.rp-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 6px 14px; border-radius: 8px;
  font-size: 0.8rem; font-weight: 700; line-height: 1.4;
  cursor: pointer; transition: background 0.15s, filter 0.15s;
}
.rp-btn--ghost { border: 1.5px solid var(--ds-border-2); background: transparent; color: var(--ds-text-2); }
.rp-btn--ghost:hover:not(:disabled) { background: var(--ds-surface-2); }
.rp-btn--primary { border: none; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.rp-btn--primary:hover:not(:disabled) { filter: brightness(1.08); }
.rp-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.rp-saved { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; font-weight: 700; color: #22c55e; }
.rp-fade-enter-active, .rp-fade-leave-active { transition: opacity 0.3s; }
.rp-fade-enter-from, .rp-fade-leave-to { opacity: 0; }

.rp-note { display: flex; align-items: center; gap: 0.4rem; margin: 0.9rem 0 0; font-size: 0.74rem; color: var(--ds-text-muted); }
.rp-note a { color: #7c8cf0; }
</style>
