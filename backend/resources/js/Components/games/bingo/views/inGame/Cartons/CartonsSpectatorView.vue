<script setup lang="ts">
import type { BingoVariantDef, BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'

defineProps<{
  totalDrawn: number
  totalChips: number
  variantDef: BingoVariantDef
  colNumbers: (col: BingoVariantColumn) => number[]
  drawnSet: Set<number>
  latestDrawn: number | undefined
}>()
</script>

<template>
  <div v-if="totalDrawn > 0" class="ctn-spectator">
    <div class="ctn-spectator__hdr">
      <span class="ctn-spectator__eye"><i class="fas fa-eye" /></span>
      <span class="ctn-spectator__label">Modo espectador</span>
      <span class="ctn-spectator__count">{{ totalDrawn }}<small>/{{ totalChips }}</small></span>
    </div>

    <div class="ctn-ball-grid">
      <template v-for="col in variantDef.columns" :key="col.label">
        <div class="ctn-ball-col">
          <div class="ctn-ball-col__hdr" :class="`ctn-ball-col__hdr--col${col.colorIdx}`">{{ col.label }}</div>
          <div
            v-for="n in colNumbers(col)"
            :key="n"
            class="ctn-ball-cell"
            :class="{
              'ctn-ball-cell--hit':    drawnSet.has(n),
              'ctn-ball-cell--latest': n === latestDrawn,
              [`ctn-ball-cell--col${col.colorIdx}`]: true,
            }"
          >{{ n }}</div>
        </div>
      </template>
    </div>
  </div>

  <div v-else class="ctn-empty">
    <div class="ctn-empty__icon"><i class="fas fa-eye" /></div>
    <p class="ctn-empty__text">Modo espectador</p>
    <p class="ctn-empty__sub">El sorteo aún no ha comenzado</p>
  </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   SPECTATOR
═══════════════════════════════════════════ */
.ctn-spectator { padding: 6px 0 2px; }
.ctn-spectator__hdr {
  display: flex; align-items: center; gap: 6px;
  font-size: 10px; font-weight: 700; color: #4a6aaa;
  text-transform: uppercase; letter-spacing: 0.7px;
  margin-bottom: 6px; padding: 0 2px;
}
.ctn-spectator__eye {
  width: 20px; height: 20px; border-radius: 6px;
  background: rgba(80,120,255,0.1); border: 1px solid rgba(80,120,255,0.18);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: #6a90d8; flex-shrink: 0;
}
.ctn-spectator__label { flex: 1; }
.ctn-spectator__count { font-size: 11px; font-weight: 800; color: #7c9dff; }
.ctn-spectator__count small { font-weight: 400; color: #4a6aaa; margin-left: 2px; }

/* ── Ball grid (spectator) — fichas reales, minimalista y compacta ── */
.ctn-ball-grid {
  display: flex; gap: 3px;
}
.ctn-ball-col  { flex: 1; display: flex; flex-direction: column; gap: 1px; min-width: 0; }

/* Column header — barra plana y baja */
.ctn-ball-col__hdr {
  text-align: center; font-family: 'Anton', sans-serif; font-size: 11px;
  padding: 2px 0; border-radius: 4px; margin-bottom: 1px;
  letter-spacing: 0.3px; color: #fff; font-weight: 900;
  text-shadow: 0 1px 2px rgba(0,0,0,0.35);
  line-height: 1.3;
}
.ctn-ball-col__hdr--col0 { background: linear-gradient(160deg, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-ball-col__hdr--col1 { background: linear-gradient(160deg, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-ball-col__hdr--col2 { background: linear-gradient(160deg, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-ball-col__hdr--col3 { background: linear-gradient(160deg, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-ball-col__hdr--col4 { background: linear-gradient(160deg, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }

/* Number cells — bajas y planas, sin aspect-ratio cuadrado */
.ctn-ball-cell {
  display: flex; align-items: center; justify-content: center;
  height: 16px; border-radius: 4px;
  font-size: 9px; font-weight: 700; line-height: 1;
  color: rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.025);
  border: 1px solid rgba(255,255,255,0.04);
  transition: background 0.15s ease, color 0.15s ease;
  user-select: none; cursor: default;
}

/* Sorteada — celda sólida de color, plana (sin relieve) */
.ctn-ball-cell--hit {
  color: #fff; font-weight: 800;
  border-color: rgba(255,255,255,0.16);
}
.ctn-ball-cell--hit.ctn-ball-cell--col0 { background: var(--bc0-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col1 { background: var(--bc1-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col2 { background: var(--bc2-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col3 { background: var(--bc3-solid); }
.ctn-ball-cell--hit.ctn-ball-cell--col4 { background: var(--bc4-solid); }

/* Última sorteada — resalte sutil sin animación de escala (evita reflow de altura) */
.ctn-ball-cell--latest {
  box-shadow: 0 0 0 1px rgba(255,255,255,0.55);
}

/* ═══════════════════════════════════════════
   EMPTY
═══════════════════════════════════════════ */
.ctn-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 6px; padding: 60px 20px;
}
.ctn-empty__icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: rgba(80,120,255,0.06); border: 1px solid rgba(80,120,255,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: #2a3860; margin-bottom: 6px;
}
.ctn-empty__text { font-size: 13px; font-weight: 700; color: #3a4e80; margin: 0; }
.ctn-empty__sub  { font-size: 11px; color: #2a3660; margin: 0; }
</style>
