<script setup lang="ts">
import type { LobbyPlayer } from '@js/composables/games/bingo/room/lobby/useLobbyPlayers'

defineProps<{
  show: boolean
  totalPlayers: number
  players: LobbyPlayer[]
  loading: boolean
  fetchError: boolean
  currentUserId: string | number | null | undefined
}>()

const emit = defineEmits<{ close: []; retry: [] }>()
</script>

<template>
  <Teleport to="body">
  <Transition name="lby-modal">
    <div v-if="show" class="lby-overlay" @click.self="emit('close')">
      <div class="lby-modal">
        <button class="lby-modal__close" @click="emit('close')">
          <i class="fas fa-times" />
        </button>

        <div class="lby-modal__header">
          <i class="fas fa-trophy lby-modal__icon" />
          <div>
            <h3 class="lby-modal__title">Top Jugadores</h3>
            <p class="lby-modal__sub">{{ totalPlayers }} jugador{{ totalPlayers === 1 ? '' : 'es' }} · ranking por cartones</p>
          </div>
        </div>

        <div v-if="loading" class="lby-modal__loading">
          <div class="lby-spinner" /> Cargando…
        </div>
        <div v-else-if="fetchError" class="lby-modal__empty lby-modal__empty--error">
          <div class="lby-modal__error-content">
            <i class="fas fa-exclamation-circle" />
            <span>Error al cargar jugadores</span>
            <button class="lby-modal__retry" @click="emit('retry')">
              <i class="fas fa-redo" /> Reintentar
            </button>
          </div>
        </div>
        <div v-else-if="players.length === 0" class="lby-modal__empty">
          <i class="fas fa-user-slash" /> Sin jugadores registrados
        </div>
        <div v-else class="lby-modal__list">
          <div
            v-for="(p, i) in players.slice(0, 10)"
            :key="p.user_id"
            class="lby-player-row"
            :class="[
              i === 0 ? 'lby-player-row--gold' : i === 1 ? 'lby-player-row--silver' : i === 2 ? 'lby-player-row--bronze' : '',
              { 'lby-player-row--me': String(p.user_id) === String(currentUserId) }
            ]"
          >
            <span class="lby-player-row__medal">
              <template v-if="i === 0"><i class="fas fa-medal lby-medal--gold" /></template>
              <template v-else-if="i === 1"><i class="fas fa-medal lby-medal--silver" /></template>
              <template v-else-if="i === 2"><i class="fas fa-medal lby-medal--bronze" /></template>
              <span v-else class="lby-player-row__rank">{{ i + 1 }}</span>
            </span>
            <span class="lby-player-row__name">
              {{ p.name }}
              <span v-if="String(p.user_id) === String(currentUserId)" class="lby-player-row__you">TÚ</span>
            </span>
            <span class="lby-player-row__ctns">
              <i class="fas fa-ticket-alt" /> {{ p.cards_purchased }}
            </span>
          </div>

          <template v-if="players.length > 10">
            <div class="lby-modal__rest-fade">
              <div
                v-for="(p, i) in players.slice(10, 13)"
                :key="p.user_id"
                class="lby-player-row lby-player-row--faded"
                :style="{ opacity: 0.35 - i * 0.1 }"
              >
                <span class="lby-player-row__medal">
                  <span class="lby-player-row__rank">{{ 11 + i }}</span>
                </span>
                <span class="lby-player-row__name">{{ p.name }}</span>
                <span class="lby-player-row__ctns">
                  <i class="fas fa-ticket-alt" /> {{ p.cards_purchased }}
                </span>
              </div>
              <div v-if="players.length > 13" class="lby-modal__rest-label">
                <i class="fas fa-ellipsis-h" />
                {{ players.length - 13 }} jugador{{ players.length - 13 === 1 ? '' : 'es' }} más
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Transition>
  </Teleport>
</template>

<style scoped>
.lby-overlay {
  position: fixed; inset: 0; z-index: 9000;
  background: rgba(2,5,16,.78); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.lby-modal {
  position: relative; width: 100%; max-width: 420px; max-height: 80vh;
  background: linear-gradient(160deg, #0d1530 0%, #0a1020 100%);
  border: 1px solid rgba(255,255,255,.1); border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04) inset;
  display: flex; flex-direction: column; overflow: hidden;
}
.lby-modal__close {
  position: absolute; top: 12px; right: 12px;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.05); color: rgba(255,255,255,.5);
  font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s;
}
.lby-modal__close:hover { background: rgba(255,77,109,.15); color: #ff6e8a; border-color: rgba(255,77,109,.25); }
.lby-modal__header {
  display: flex; align-items: center; gap: 12px; padding: 18px 18px 14px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.lby-modal__icon {
  font-size: 18px; color: #ffc200; flex-shrink: 0;
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(255,194,0,.08); border: 1px solid rgba(255,194,0,.18);
  display: flex; align-items: center; justify-content: center;
  filter: drop-shadow(0 0 6px rgba(255,194,0,.2));
}
.lby-modal__title { font-size: 15px; font-weight: 700; color: #e0eaff; margin: 0 0 2px; }
.lby-modal__sub   { font-size: 10px; color: #3a5068; margin: 0; }
.lby-modal__loading {
  display: flex; align-items: center; gap: 10px;
  padding: 28px 18px; color: #4a6080; font-size: 11px;
}
.lby-modal__empty--error { color: #ff6e8a !important; }
.lby-modal__empty--error i { color: #ff4d6d; }
.lby-modal__empty {
  padding: 28px 18px; text-align: center; color: #3a5068; font-size: 11px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.lby-modal__error-content {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
}
.lby-modal__error-content i { font-size: 20px; }
.lby-modal__retry {
  padding: 6px 16px; border-radius: 8px; font-size: 10px; font-weight: 700;
  letter-spacing: .5px; cursor: pointer;
  background: rgba(255,77,109,.12); border: 1px solid rgba(255,77,109,.3); color: #ff6e8a;
  display: inline-flex; align-items: center; gap: 6px;
  transition: background .2s, border-color .2s;
}
.lby-modal__retry:hover { background: rgba(255,77,109,.22); border-color: rgba(255,77,109,.5); }
.lby-modal__list { overflow-y: auto; flex: 1; padding: 8px 0; }
.lby-modal__list::-webkit-scrollbar       { width: 3px; }
.lby-modal__list::-webkit-scrollbar-track { background: transparent; }
.lby-modal__list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 2px; }

.lby-player-row {
  display: flex; align-items: center; gap: 10px; padding: 8px 18px;
  border-bottom: 1px solid rgba(255,255,255,.035);
  transition: background .15s; position: relative;
}
.lby-player-row:last-child { border-bottom: none; }
.lby-player-row:hover { background: rgba(255,255,255,.02); }
.lby-player-row__medal { width: 22px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.lby-player-row__rank  { font-size: 9px; color: #2a3f58; font-weight: 700; }

.lby-player-row--gold   { background: linear-gradient(90deg, rgba(255,191,0,.07) 0%, transparent 100%); }
.lby-player-row--gold:hover { background: linear-gradient(90deg, rgba(255,191,0,.12) 0%, transparent 100%); }
.lby-medal--gold   { font-size: 15px; color: #ffc200; filter: drop-shadow(0 0 5px rgba(255,194,0,.55)); }
.lby-player-row--silver { background: linear-gradient(90deg, rgba(180,200,220,.06) 0%, transparent 100%); }
.lby-medal--silver { font-size: 15px; color: #b0c4d8; filter: drop-shadow(0 0 4px rgba(176,196,216,.4)); }
.lby-player-row--bronze { background: linear-gradient(90deg, rgba(180,100,30,.07) 0%, transparent 100%); }
.lby-medal--bronze { font-size: 15px; color: #c87840; filter: drop-shadow(0 0 4px rgba(200,120,64,.4)); }

.lby-player-row--me { background: rgba(42,245,152,.04); }
.lby-player-row--me:hover { background: rgba(42,245,152,.07); }
.lby-player-row--me .lby-player-row__name { color: #2af598; }
.lby-player-row--me .lby-player-row__ctns { color: #2af598; }

.lby-player-row__name {
  flex: 1; font-size: 11.5px; color: #c8d8f0; font-weight: 600;
  min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  display: flex; align-items: center; gap: 6px;
}
.lby-player-row__you {
  font-size: 8px; font-weight: 800; letter-spacing: .5px;
  background: rgba(42,245,152,.15); border: 1px solid rgba(42,245,152,.3); color: #2af598;
  padding: 1px 5px; border-radius: 4px; flex-shrink: 0;
}
.lby-player-row__ctns {
  font-size: 10px; font-weight: 700; color: #3a5068; flex-shrink: 0;
  display: flex; align-items: center; gap: 4px;
}
.lby-player-row__ctns i { font-size: 8px; }

.lby-modal__rest-fade {
  position: relative;
  mask-image: linear-gradient(180deg, rgba(0,0,0,.5) 0%, transparent 85%);
  -webkit-mask-image: linear-gradient(180deg, rgba(0,0,0,.5) 0%, transparent 85%);
  pointer-events: none;
}
.lby-player-row--faded { transition: none; }
.lby-modal__rest-label {
  position: absolute; bottom: 2px; left: 0; right: 0; text-align: center;
  font-size: 9px; color: #2a3f58; padding-bottom: 6px;
  display: flex; align-items: center; justify-content: center; gap: 5px;
}

.lby-spinner {
  width: 20px; height: 20px; flex-shrink: 0;
  border: 2px solid rgba(100,140,255,.15); border-top-color: #2af598;
  border-radius: 50%; animation: lbySpin 1s linear infinite;
}
@keyframes lbySpin { to { transform: rotate(360deg); } }

.lby-modal-enter-active, .lby-modal-leave-active { transition: opacity .2s, transform .25s cubic-bezier(.22,1,.36,1); }
.lby-modal-enter-from, .lby-modal-leave-to { opacity: 0; }
.lby-modal-enter-from .lby-modal, .lby-modal-leave-to .lby-modal { transform: scale(.95) translateY(8px); }
</style>
