<script setup lang="ts">
import type { HeroContent } from '@js/types/content/landing-section'
import type { PlatformWinEvent } from '@js/composables/platform/usePlatformWinEvents'

defineProps<{
  content: HeroContent | null
  onlineCount: number
  totalPaidFormatted: string | null
  wins: PlatformWinEvent[]
  formatPrize: (win: PlatformWinEvent) => string
  register?: boolean
}>()

defineEmits<{ register: [] }>()
</script>

<template>
  <header class="landing__hero">
    <div class="landing__hero-grid">
      <div>
        <span class="eyebrow hero-in" style="--stagger-index: 1">
          <i class="fas fa-circle" /> {{ content?.eyebrowPrefix }}{{ content?.eyebrowPrefix ? ' ' : '' }}{{ onlineCount }} {{ content?.eyebrowSuffix ?? 'jugadores en salas activas' }}
        </span>
        <h1 class="landing__hero-title hero-in" style="--stagger-index: 2" v-html="content?.title ?? 'Juega en vivo.<br/><em>Gana en serio.</em>'" />
        <p class="landing__hero-sub hero-in" style="--stagger-index: 3">
          {{ content?.subtitle ?? 'Salas de bingo con premios reales, actualizadas en tiempo real. Ves el pozo, ves quién compró, ves quién ganó.' }}
        </p>
        <div class="landing__hero-actions hero-in" style="--stagger-index: 4">
          <button v-if="register !== false" type="button" class="btn btn--primary btn--lg" @click="$emit('register')">
            {{ content?.ctaPrimaryLabel ?? 'Crear cuenta gratis' }}
          </button>
          <a class="btn btn--ghost btn--lg" href="#salas">{{ content?.ctaSecondaryLabel ?? 'Ver salas activas' }}</a>
        </div>
        <div v-if="totalPaidFormatted" class="landing__hero-stat hero-in" style="--stagger-index: 5">
          <i class="fas fa-sack-dollar" />
          <span><strong>${{ totalPaidFormatted }} USD</strong> repartidos en premios a la fecha</span>
        </div>
        <div class="landing__hero-trust hero-in" style="--stagger-index: 6">
          <template v-for="(item, i) in (content?.trustItems ?? ['Retiros verificados', 'Cartones auditables por serial', 'Soporte 24/7'])" :key="i">
            <span v-if="i > 0" class="dot" />
            <span>{{ item }}</span>
          </template>
        </div>
      </div>

      <!-- Ticker de ganancias en vivo -->
      <div class="ticker-card hero-in" style="--stagger-index: 3">
        <div class="ticker-card__head">
          <h3>Ganancias recientes</h3>
          <span class="eyebrow eyebrow--sm"><i class="fas fa-circle" /> en vivo</span>
        </div>
        <div v-if="wins.length" class="ticker-list">
          <div
            v-for="(win, i) in wins"
            :key="win.id"
            class="ticker-row"
            :class="{ 'ticker-row--top': i === 0 }"
          >
            <div class="ticker-avatar">{{ win.winner_name.slice(0, 2).toUpperCase() }}</div>
            <div class="ticker-meta">
              <div class="ticker-name">{{ win.winner_name }}</div>
              <div class="ticker-game">{{ win.game }}</div>
            </div>
            <div class="ticker-amount">+{{ formatPrize(win) }}</div>
          </div>
        </div>
        <p v-else class="ticker-empty">Aún no hay ganancias recientes — ¡sé el primero!</p>
        <div class="ticker-card__foot">Actualizado automáticamente</div>
      </div>
    </div>
  </header>
</template>
