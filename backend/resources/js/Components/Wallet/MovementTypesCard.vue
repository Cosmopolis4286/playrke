<script setup lang="ts">
import type { MovementTypeSection } from '@js/composables/wallet/useMovementMeta'

defineProps<{
  sections: MovementTypeSection[]
  maxHeight: number | null
}>()
</script>

<template>
  <div
    class="wallet-card"
    :style="maxHeight ? { maxHeight: maxHeight + 'px' } : undefined"
  >
    <h3 class="wallet-card__title">
      <i class="fas fa-info-circle"></i> Tipos de movimiento
    </h3>
    <div class="movement-types movement-types--scroll">
      <div v-for="section in sections" :key="section.group ?? 'general'" class="movement-type-section">
        <p v-if="sections.length > 1" class="movement-type-section__title">{{ section.groupLabel }}</p>

        <div v-for="item in section.items" :key="item.label" class="movement-type-group">
          <div class="movement-type-item" :class="item.colorClass">
            <div class="movement-type-item__icon" :style="item.colorStyle">
              <i :class="item.icon"></i>
            </div>
            <div class="movement-type-item__info">
              <p class="movement-type-item__label">{{ item.label }}</p>
              <p class="movement-type-item__desc">{{ item.description }}</p>
            </div>
            <span class="movement-type-item__sign" :class="item.sign === '+' ? 'sign--positive' : 'sign--negative'">
              {{ item.sign }}
            </span>
          </div>

          <div v-if="item.children" class="movement-type-children">
            <div
              v-for="child in item.children"
              :key="child.label"
              class="movement-type-item movement-type-item--child"
              :class="child.colorClass"
            >
              <div class="movement-type-item__icon movement-type-item__icon--sm">
                <i :class="child.icon"></i>
              </div>
              <div class="movement-type-item__info">
                <p class="movement-type-item__label">{{ child.label }}</p>
                <p class="movement-type-item__desc">{{ child.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wallet-card {
  background: var(--ds-surface);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.wallet-card__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.wallet-card__title i { color: #667eea; }

/* ── Tipos de movimiento ── */
.movement-types { display: flex; flex-direction: column; gap: 14px; }

.movement-type-section { display: flex; flex-direction: column; gap: 8px; }

.movement-type-section__title {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--ds-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin: 0;
}

.movement-types--scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
  padding-right: 4px;
}

.movement-type-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--ds-surface-2);
  transition: box-shadow 0.15s;
}
.movement-type-item:hover { box-shadow: 0 2px 8px rgba(102, 126, 234, 0.12); }

.movement-type-item__icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--ds-surface);
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.meta--deposit      .movement-type-item__icon { color: #16a34a; }
.meta--prize        .movement-type-item__icon { color: #d97706; }
.meta--purchase     .movement-type-item__icon { color: #e11d48; }
.meta--withdrawal   .movement-type-item__icon { color: #2563eb; }
.meta--trivia-entry .movement-type-item__icon { color: #7c3aed; }
.meta--trivia-prize .movement-type-item__icon { color: #7c3aed; }

.movement-type-item__info { flex: 1; min-width: 0; }

.movement-type-item__label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0;
}

.movement-type-item__desc {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}

.movement-type-item__sign {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* ── Grupo con subtipos (ej: Premio → Premio Bingo / Premio Trivia) ── */
.movement-type-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.movement-type-children {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-left: 20px;
  padding-left: 14px;
  border-left: 2px solid var(--ds-border);
}

.movement-type-item--child {
  padding: 7px 10px;
  background: var(--ds-surface);
}

.movement-type-item__icon--sm {
  width: 30px;
  height: 30px;
  font-size: 0.78rem;
}

.sign--positive { background: rgba(34, 197, 94, .16); color: #16a34a; }
.sign--negative { background: rgba(225, 29, 72, .16); color: #e11d48; }
</style>
