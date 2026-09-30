<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { localIsoDate } from '@/js/composables/games/bingo/card/pos/usePosRooms'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'
import type { PosRoom } from '@/js/types/games/bingo/card/bingo-card-generate'

/**
 * Paso 1 del punto de venta: fecha en chips (Hoy / Mañana / …), filtros en
 * chips derivados de las salas del día y salas como tarjetas con cuenta
 * regresiva. Al elegir una sala se colapsa a un resumen con "Cambiar".
 */
const { rooms: ctx } = usePos()

const DAY_NAMES = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const quickDates = computed(() => [0, 1, 2].map(offset => {
  const iso = localIsoDate(offset)
  const d = new Date(`${iso}T00:00:00`)
  return {
    iso,
    label: offset === 0 ? 'Hoy' : offset === 1 ? 'Mañana' : `${DAY_NAMES[d.getDay()]} ${d.getDate()}`,
  }
}))

const isCustomDate = computed(() => !quickDates.value.some(d => d.iso === ctx.date))

const expanded = ref(!ctx.selectedRoomId)
watch(() => ctx.selectedRoomId, id => { expanded.value = !id })

function select(room: PosRoom): void {
  if (!ctx.isSalesOpen(room)) return
  ctx.selectedRoomId = room.id
  expanded.value = false
}

/**
 * Etiqueta del plazo de VENTA (no del inicio): el vendedor deja de poder
 * vender unos minutos antes de la hora (ver findSellableRoom() en backend).
 */
function saleLabel(room: PosRoom): { text: string; tone: string } {
  if (!ctx.isSalesOpen(room)) return { text: 'Ventas cerradas', tone: 'muted' }
  if (room.is_manual) return { text: 'Sin horario', tone: 'info' }

  const mins = ctx.minutesToClose(room)
  if (mins === null) return { text: 'Abierta', tone: 'info' }
  if (mins < 1) return { text: 'Cierra ya', tone: 'danger' }
  if (mins <= 10) return { text: `Cierra en ${mins} min`, tone: 'danger' }
  if (mins < 60) return { text: `Cierra en ${mins} min`, tone: 'warning' }
  if (mins < 24 * 60) {
    const h = Math.floor(mins / 60)
    const m = mins % 60
    return { text: m ? `Cierra en ${h} h ${m} min` : `Cierra en ${h} h`, tone: 'info' }
  }
  return { text: 'Abierta', tone: 'info' }
}

/** Hora de inicio en la zona horaria del vendedor (desde starts_at UTC). */
function hhmm(room: PosRoom): string {
  if (room.is_manual) return 'Manual'
  if (room.starts_at) return new Date(room.starts_at).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit', hour12: false })
  return room.time ? room.time.slice(0, 5) : '--:--'
}

function closeTime(room: PosRoom): string | null {
  return room.sales_close_at
    ? new Date(room.sales_close_at).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit', hour12: false })
    : null
}

function usd(n: number): string {
  return `$${n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function fillPct(room: PosRoom): number | null {
  if (!room.max_players) return null
  return Math.min(100, Math.round((room.total_players / room.max_players) * 100))
}
</script>

<template>
  <section class="pos-card">
    <div class="pos-step">
      <span class="pos-step__num" :class="ctx.selectedRoom ? 'pos-step__num--done' : 'pos-step__num--active'">
        <i v-if="ctx.selectedRoom" class="fas fa-check"></i><template v-else>1</template>
      </span>
      <div>
        <h2 class="pos-step__title">Sala</h2>
        <p class="pos-step__hint">¿Para qué partida vendes?</p>
      </div>
      <div class="pos-step__aside">
        <button
          v-if="!expanded && ctx.selectedRoom"
          type="button"
          class="pos-btn pos-btn--ghost pos-btn--sm"
          @click="expanded = true"
        >
          <i class="fas fa-exchange-alt"></i>Cambiar
        </button>
        <button
          v-else
          type="button"
          class="pos-icon-btn"
          title="Actualizar salas"
          aria-label="Actualizar salas"
          :disabled="ctx.loading"
          @click="ctx.fetchRooms()"
        >
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': ctx.loading }"></i>
        </button>
      </div>
    </div>

    <!-- ── Resumen compacto de la sala elegida ── -->
    <button
      v-if="!expanded && ctx.selectedRoom"
      type="button"
      class="prp-selected"
      @click="expanded = true"
    >
      <span class="prp-selected__time">{{ hhmm(ctx.selectedRoom) }}</span>
      <span class="prp-selected__body">
        <strong>{{ ctx.selectedRoom.uuid_code }}</strong>
        <span class="pos-muted">
          {{ ctx.typeLabel('room_type_id', ctx.selectedRoom.room_type_id) ?? 'Sala' }}
          · {{ ctx.selectedRoom.total_players }} jugadores · bote {{ usd(ctx.selectedRoom.prize_pool) }}
        </span>
      </span>
      <span class="pos-pill" :class="`pos-pill--${saleLabel(ctx.selectedRoom).tone}`">
        {{ saleLabel(ctx.selectedRoom).text }}
      </span>
    </button>

    <template v-else>
      <!-- ── Fecha ── -->
      <div class="prp-toolbar">
        <div class="pos-chips" role="group" aria-label="Fecha">
          <button
            v-for="d in quickDates"
            :key="d.iso"
            type="button"
            class="pos-chip"
            :class="{ 'pos-chip--on': ctx.date === d.iso }"
            :aria-pressed="ctx.date === d.iso"
            @click="ctx.date = d.iso"
          >
            {{ d.label }}
          </button>
          <label class="pos-chip prp-date" :class="{ 'pos-chip--on': isCustomDate }">
            <i class="far fa-calendar-alt"></i>
            <input
              type="date"
              :min="quickDates[0].iso"
              :value="ctx.date"
              aria-label="Otra fecha"
              @change="ctx.date = ($event.target as HTMLInputElement).value || quickDates[0].iso"
            />
          </label>
        </div>

        <div class="prp-search">
          <i class="fas fa-search"></i>
          <input v-model="ctx.search" type="search" placeholder="Código u hora (ej. 19:30)" aria-label="Buscar sala" />
        </div>
      </div>

      <!-- ── Filtros (solo los que existen en las salas del día) ── -->
      <div v-if="ctx.filterGroups.length" class="prp-filters">
        <div v-for="group in ctx.filterGroups" :key="group.key" class="prp-filter">
          <span class="prp-filter__label">{{ group.label }}</span>
          <div class="pos-chips">
            <button
              v-for="chip in group.chips"
              :key="chip.value"
              type="button"
              class="pos-chip"
              :class="{ 'pos-chip--on': ctx.filters[group.key] === chip.value }"
              :aria-pressed="ctx.filters[group.key] === chip.value"
              @click="ctx.toggleFilter(group.key, chip.value)"
            >
              {{ chip.label }} <span class="pos-chip__count">{{ chip.count }}</span>
            </button>
          </div>
        </div>
        <button v-if="ctx.hasActiveFilters" type="button" class="prp-clear" @click="ctx.clearFilters()">
          <i class="fas fa-times"></i> Limpiar filtros
        </button>
      </div>

      <!-- ── Salas ── -->
      <div v-if="ctx.loading && !ctx.rooms.length" class="prp-grid">
        <div v-for="n in 3" :key="n" class="pos-skeleton" style="height: 128px"></div>
      </div>

      <div v-else-if="!ctx.rooms.length" class="prp-empty">
        <i class="far fa-calendar-times"></i>
        <strong>No hay salas a la venta para esta fecha</strong>
        <span class="pos-muted">Prueba con otro día o pregúntale a tu gestor por la programación.</span>
      </div>

      <div v-else-if="!ctx.visibleRooms.length" class="prp-empty">
        <i class="fas fa-filter"></i>
        <strong>Ninguna sala coincide con los filtros</strong>
        <button type="button" class="pos-btn pos-btn--ghost pos-btn--sm" @click="ctx.clearFilters()">Limpiar filtros</button>
      </div>

      <div v-else class="prp-grid" role="listbox" aria-label="Salas disponibles">
        <button
          v-for="room in ctx.visibleRooms"
          :key="room.id"
          type="button"
          role="option"
          class="prp-room"
          :class="{ 'prp-room--on': ctx.selectedRoomId === room.id, 'prp-room--closed': !ctx.isSalesOpen(room) }"
          :aria-selected="ctx.selectedRoomId === room.id"
          :aria-disabled="!ctx.isSalesOpen(room)"
          :title="!ctx.isSalesOpen(room) ? 'Las ventas del vendedor cierran antes del inicio para que el comercio alcance a aprobar el comprobante.' : undefined"
          @click="select(room)"
        >
          <span class="prp-room__head">
            <span class="prp-room__time">{{ hhmm(room) }}</span>
            <span class="pos-pill" :class="`pos-pill--${saleLabel(room).tone}`">{{ saleLabel(room).text }}</span>
          </span>

          <span class="prp-room__code">
            {{ room.uuid_code }}
            <span v-if="room.is_own" class="pos-pill pos-pill--info">Tu comercio</span>
          </span>

          <span class="prp-room__tags">
            <span v-for="key in (['room_type_id', 'connection_type_id', 'prize_pool_type_id'] as const)" :key="key">
              <template v-if="ctx.typeLabel(key, room[key])">{{ ctx.typeLabel(key, room[key]) }}</template>
            </span>
          </span>

          <span class="prp-room__stats">
            <span><i class="fas fa-trophy"></i> {{ usd(room.prize_pool) }}</span>
            <span><i class="fas fa-users"></i> {{ room.total_players }}<template v-if="room.max_players">/{{ room.max_players }}</template></span>
            <span><i class="fas fa-ticket-alt"></i> {{ usd(room.card_price_usd) }}</span>
          </span>

          <span v-if="closeTime(room) && ctx.isSalesOpen(room)" class="prp-room__close">
            <i class="far fa-clock"></i> Venta hasta las {{ closeTime(room) }}
          </span>

          <span v-if="fillPct(room) !== null" class="prp-room__bar" aria-hidden="true">
            <span :style="{ width: `${fillPct(room)}%` }"></span>
          </span>

          <i v-if="ctx.selectedRoomId === room.id" class="fas fa-check-circle prp-room__check"></i>
        </button>
      </div>
    </template>
  </section>
</template>

<style scoped>
.prp-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.prp-date { position: relative; padding-right: 8px; }
.prp-date input {
  border: none;
  background: transparent;
  color: inherit;
  font: inherit;
  width: 118px;
  padding: 0;
  outline: none;
  color-scheme: light dark;
}

.prp-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border: 1px solid var(--ds-border-2);
  border-radius: 10px;
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  min-width: 0;
  flex: 1 1 200px;
  max-width: 280px;
}
.prp-search input {
  border: none;
  background: transparent;
  color: var(--ds-text);
  outline: none;
  width: 100%;
  font-size: 13px;
}

.prp-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 10px 18px;
  padding: 10px 0 14px;
  border-top: 1px dashed var(--ds-border);
}
.prp-filter__label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ds-text-2);
  margin-bottom: 5px;
}
.prp-clear {
  border: none;
  background: none;
  color: var(--pos-accent-strong);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  padding: 6px 0;
}

.prp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}

.prp-room {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 7px;
  text-align: left;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1.5px solid var(--ds-border);
  background: var(--ds-surface-2);
  color: var(--ds-text);
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s, transform .15s;
}
.prp-room:hover { border-color: var(--pos-accent); transform: translateY(-1px); }
.prp-room:focus-visible { outline: 2px solid var(--pos-accent); outline-offset: 2px; }
.prp-room--on {
  border-color: var(--pos-accent);
  box-shadow: 0 0 0 3px var(--pos-accent-soft);
  background: var(--ds-surface);
}
.prp-room__head { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.prp-room__time { font-size: 22px; font-weight: 800; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.prp-room__code { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; flex-wrap: wrap; }
.prp-room__tags { display: flex; flex-wrap: wrap; gap: 4px 8px; font-size: 11px; color: var(--ds-text-2); }
.prp-room__tags span:empty { display: none; }
.prp-room__tags span:not(:empty) + span:not(:empty)::before { content: '·'; margin-right: 8px; }
.prp-room__stats { display: flex; justify-content: space-between; gap: 6px; font-size: 12px; color: var(--ds-text-2); font-variant-numeric: tabular-nums; }
.prp-room__stats i { font-size: 10px; opacity: .8; }
.prp-room__close { font-size: 11px; color: var(--ds-text-2); }
.prp-room--closed { opacity: .5; cursor: not-allowed; }
.prp-room--closed:hover { border-color: var(--ds-border); transform: none; }
.prp-room__bar { height: 4px; border-radius: 4px; background: var(--ds-border); overflow: hidden; }
.prp-room__bar span { display: block; height: 100%; background: var(--pos-accent); border-radius: 4px; }
.prp-room__check { position: absolute; top: -7px; right: -7px; font-size: 20px; color: var(--pos-accent); background: var(--ds-surface); border-radius: 50%; }

.prp-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 28px 12px;
  text-align: center;
  border: 1px dashed var(--ds-border-2);
  border-radius: 12px;
}
.prp-empty > i { font-size: 26px; color: var(--ds-text-muted); }

.prp-selected {
  display: flex;
  align-items: center;
  gap: 14px;
  width: 100%;
  text-align: left;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1.5px solid var(--pos-accent);
  background: var(--pos-accent-soft);
  color: var(--ds-text);
  cursor: pointer;
}
.prp-selected__time { font-size: 24px; font-weight: 800; font-variant-numeric: tabular-nums; }
.prp-selected__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.prp-selected__body .pos-muted { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

@media (max-width: 576px) {
  .prp-search { max-width: none; flex-basis: 100%; }
  .prp-grid { grid-template-columns: 1fr; }
  .prp-selected .pos-pill { display: none; }
}
</style>
