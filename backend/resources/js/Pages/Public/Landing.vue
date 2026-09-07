<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useHead } from "@vueuse/head";

import BingoGameRoomCard from "@js/Components/games/bingo/Room/GameRooms/BingoGameRoomCard.vue";
import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { usePlatformWinEvents } from "@js/composables/platform/usePlatformWinEvents";
import { useRevealOnScroll } from "@js/composables/ui/useRevealOnScroll";
import { axios } from "@js/plugins/axios";

import type { BingoGameRoomsPageProps } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

/**
 * Landing pública — nueva raíz `/` (ver PublicController::root()). PlayRKE
 * no tiene "producto" que vender fuera del juego mismo, así que la landing
 * abre directo con prueba social (ganancias en vivo) y enlaza a los otros
 * endpoints públicos: vitrina completa de salas y verificador de cartón.
 * login/registro viven en sus propias rutas (/login, /register).
 */

interface LeaderboardEntry {
  readonly rank: number;
  readonly winner_name: string;
  readonly total_amount: number;
  readonly wins_count: number;
}

interface PlatformStats {
  readonly players_online: number;
  readonly total_paid_usd: number;
  readonly rooms_played_count: number;
}

const props = defineProps<
  BingoGameRoomsPageProps & {
    register?: boolean;
    platformStats?: PlatformStats;
    initialLeaderboard?: LeaderboardEntry[];
  }
>();

const SEO_DESCRIPTION =
  "Salas de bingo con premios reales, en vivo. Verifica tu cartón sin cuenta y mira las ganancias del momento.";
// Sin banner social dedicado (1200x630) en /public/img; se usa el logo de marca
// como mejor alternativa disponible — reemplazar si se agrega un asset og:image real.
const SEO_IMAGE = "https://www.playrke.com/img/logo.png";

useHead({
  title: "PlayRKE — Juega en vivo, gana en serio",
  meta: [
    { property: "og:type", content: "website" },
    { property: "og:url", content: "https://www.playrke.com/" },
    { property: "og:site_name", content: "PlayRKE" },
    { property: "og:title", content: "PlayRKE — Juega en vivo, gana en serio" },
    { property: "og:description", content: SEO_DESCRIPTION },
    { property: "og:image", content: SEO_IMAGE },
    { property: "og:locale", content: "es_VE" },
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:title", content: "PlayRKE — Juega en vivo, gana en serio" },
    { name: "twitter:description", content: SEO_DESCRIPTION },
    { name: "twitter:image", content: SEO_IMAGE },
    { name: "description", content: SEO_DESCRIPTION },
  ],
  link: [
    { rel: "icon", type: "image/x-icon", href: "/img/fav.ico" },
    { rel: "shortcut icon", type: "image/x-icon", href: "/img/fav.ico" },
    { rel: "canonical", href: "https://www.playrke.com/" },
  ],
  script: [
    {
      type: "application/ld+json",
      innerHTML: JSON.stringify({
        "@context": "https://schema.org",
        "@graph": [
          {
            "@type": "Organization",
            name: "PlayRKE",
            url: "https://www.playrke.com/",
            logo: SEO_IMAGE,
          },
          {
            "@type": "WebSite",
            name: "PlayRKE",
            url: "https://www.playrke.com/",
          },
        ],
      }),
    },
  ],
});

// ── Salas destacadas (mismo DTO que /game-rooms, ya truncado a 3 por el backend) ──
const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(serverTimestampMs);

onMounted(() => {
  initCountdown(props.rooms);
});

function goToRegister() {
  window.location.href = "/register";
}

// ── Modal "Ver Top de Compras" (mismo componente que la vitrina completa) ──
const topBuyersRoomId = ref<string | null>(null);
const topBuyersRoomUuidCode = computed(() => {
  const room = props.rooms?.find((r) => r.id === topBuyersRoomId.value);
  return room?.uuid_code ?? null;
});
function openTopBuyers(roomId: string) {
  topBuyersRoomId.value = roomId;
}
function closeTopBuyers() {
  topBuyersRoomId.value = null;
}

// ── Ganancias en vivo — mismo composable/canal Echo que el ticker autenticado
// (platform.win-events es público, sin auth: ver routes/channels.php), con
// seed inicial vía GET /api/win-events/recent. El nombre del ganador ya llega
// enmascarado desde el backend (WinEvent::maskedWinnerName()).
const { events: wins, formatPrize } = usePlatformWinEvents();

// ── Leaderboard semanal — sembrado por el backend (WinEventLeaderboardService),
// sin canal dedicado: no vale la pena un segundo WebSocket solo para un
// ranking que cambia lentamente. Se refresca junto con las stats de plataforma.
const leaderboard = ref<LeaderboardEntry[]>(props.initialLeaderboard ?? []);

// ── Stats de plataforma — sembradas por el backend (PlatformStatsService,
// cacheadas 60s), refrescadas junto al leaderboard cada pocos minutos.
const platformStats = ref<PlatformStats | undefined>(props.platformStats);

let statsTimer: ReturnType<typeof setInterval> | null = null;

async function refreshStatsAndLeaderboard() {
  try {
    const [statsRes, leaderboardRes] = await Promise.all([
      axios.get("/api/platform/stats"),
      axios.get("/api/win-events/leaderboard"),
    ]);
    if (statsRes.data) platformStats.value = statsRes.data;
    if (Array.isArray(leaderboardRes.data?.leaderboard)) {
      leaderboard.value = leaderboardRes.data.leaderboard;
    }
  } catch {
    // Silencioso: se mantiene la última data buena conocida.
  }
}

onMounted(() => {
  statsTimer = setInterval(refreshStatsAndLeaderboard, 120_000);
});
onUnmounted(() => {
  if (statsTimer) clearInterval(statsTimer);
});

const onlineCount = computed(
  () => platformStats.value?.players_online ?? props.rooms?.reduce((sum, r) => sum + (r.total_players ?? 0), 0) ?? 0
);
const totalPaidFormatted = computed(() => {
  const total = platformStats.value?.total_paid_usd;
  if (total == null) return null;
  return total.toLocaleString("es", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
});

// ── Verificador de cartón (inline, envía a /verify-card con los valores precargados) ──
const cardSerial = ref("");
const cardPhone = ref("");

function submitVerify() {
  const params = new URLSearchParams();
  if (cardSerial.value) params.set("serial", cardSerial.value);
  if (cardPhone.value) params.set("phone", cardPhone.value);
  const qs = params.toString();
  window.location.href = qs ? `/verify-card?${qs}` : "/verify-card";
}

// ── Animación de entrada al hacer scroll — el hero queda fuera a propósito
// (contenido above-the-fold, no debe animarse para evitar CLS/LCP) ──
const revealRefs = ref<HTMLElement[]>([]);
function setRevealRef(el: Element | null) {
  if (el instanceof HTMLElement && !revealRefs.value.includes(el)) {
    revealRefs.value.push(el);
  }
}
useRevealOnScroll(revealRefs);
</script>

<template>
  <div class="landing">
    <!-- ══ NAV ══ -->
    <nav class="landing__nav">
      <div class="landing__nav-inner">
        <span class="landing__brand">PLAYRKE</span>
        <div class="landing__nav-links">
          <a href="#salas">Salas en vivo</a>
          <a href="#verificar">Verificar cartón</a>
          <a href="#ganadores">Ganadores</a>
        </div>
        <div class="landing__nav-cta">
          <Link href="/login" class="btn btn--ghost">Iniciar sesión</Link>
          <button v-if="register !== false" type="button" class="btn btn--primary" @click="goToRegister">
            Crear cuenta
          </button>
        </div>
      </div>
    </nav>

    <!-- ══ HERO ══ -->
    <header class="landing__hero">
      <div class="landing__hero-grid">
        <div>
          <span class="eyebrow">
            <i class="fas fa-circle" /> {{ onlineCount }} jugadores en salas activas
          </span>
          <h1 class="landing__hero-title">Juega en vivo.<br /><em>Gana en serio.</em></h1>
          <p class="landing__hero-sub">
            Salas de bingo con premios reales, actualizadas en tiempo real. Ves el pozo,
            ves quién compró, ves quién ganó.
          </p>
          <div class="landing__hero-actions">
            <button v-if="register !== false" type="button" class="btn btn--primary btn--lg" @click="goToRegister">
              Crear cuenta gratis
            </button>
            <a class="btn btn--ghost btn--lg" href="#salas">Ver salas activas</a>
          </div>
          <div v-if="totalPaidFormatted" class="landing__hero-stat">
            <i class="fas fa-sack-dollar" />
            <span><strong>${{ totalPaidFormatted }} USD</strong> repartidos en premios a la fecha</span>
          </div>
          <div class="landing__hero-trust">
            <span>Retiros verificados</span>
            <span class="dot" />
            <span>Cartones auditables por serial</span>
            <span class="dot" />
            <span>Soporte 24/7</span>
          </div>
        </div>

        <!-- Ticker de ganancias en vivo -->
        <div class="ticker-card">
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

    <!-- ══ SALAS DESTACADAS ══ -->
    <section id="salas" class="band reveal" :ref="setRevealRef">
      <div class="band__head">
        <span class="eyebrow">Catálogo en vivo</span>
        <h2>Salas de Bingo activas</h2>
        <p>Cartones a la venta ahora mismo. El pozo crece con cada compra.</p>
      </div>

      <div v-if="!props.rooms?.length" class="rooms-empty">
        <i class="fas fa-door-closed" />
        <p>No hay salas activas en este momento</p>
      </div>
      <div v-else class="rooms-grid">
        <BingoGameRoomCard
          v-for="(room, index) in props.rooms"
          :key="room.id"
          :room="room"
          :exchange-rate="1"
          :currency-sym="'USD'"
          :countdown="getCountdown(room.id)"
          :index="index"
          @select="goToRegister"
          @show-top-buyers="openTopBuyers"
        />
      </div>

      <div class="band__cta">
        <Link href="/game-rooms" class="btn btn--ghost">Ver todas las salas →</Link>
      </div>
    </section>

    <!-- ══ VERIFICAR CARTÓN ══ -->
    <section id="verificar" class="band band--alt reveal" :ref="setRevealRef">
      <div class="verify-grid">
        <div>
          <span class="eyebrow">Sin necesidad de cuenta</span>
          <h2 class="verify-title">Verifica tu cartón como un ticket físico</h2>
          <p class="verify-sub">
            ¿Compraste un cartón por WhatsApp o en punto físico? Confírmalo en segundos
            con el número de serial y los últimos 4 dígitos de tu teléfono.
          </p>
          <ul class="verify-steps">
            <li><span class="num">1</span> Ingresa el serial impreso en tu cartón</li>
            <li><span class="num">2</span> Ingresa los últimos 4 dígitos de tu teléfono</li>
            <li><span class="num">3</span> Ve el estado, la sala y el resultado al instante</li>
          </ul>
        </div>
        <form class="verify-form" @submit.prevent="submitVerify">
          <label for="landing-serial">Serial del cartón</label>
          <input id="landing-serial" v-model="cardSerial" type="text" placeholder="BGO-2K9X-771" autocomplete="off" />

          <label for="landing-phone">Últimos 4 dígitos del teléfono</label>
          <input id="landing-phone" v-model="cardPhone" type="text" inputmode="numeric" maxlength="4" placeholder="••••" autocomplete="off" />

          <button type="submit" class="btn btn--primary btn--block">Verificar cartón</button>
        </form>
      </div>
    </section>

    <!-- ══ GANADORES ══ -->
    <section id="ganadores" class="band reveal" :ref="setRevealRef">
      <div class="leaderboard-grid">
        <div>
          <span class="eyebrow">Ranking de la semana</span>
          <h2 class="verify-title">Los mejores jugadores de los últimos 7 días</h2>
          <p class="verify-sub">
            Suma de ganancias en dinero por jugador, actualizado cada pocos minutos.
            Los nombres se muestran parcialmente por privacidad.
          </p>
        </div>
        <div class="lb-card">
          <div v-if="leaderboard.length" class="lb-list">
            <div v-for="entry in leaderboard" :key="entry.rank" class="lb-row">
              <span class="lb-rank">{{ String(entry.rank).padStart(2, "0") }}</span>
              <span class="lb-name">{{ entry.winner_name }}</span>
              <span class="lb-amt">+${{ entry.total_amount.toFixed(2) }}</span>
            </div>
          </div>
          <p v-else class="ticker-empty">Sin datos suficientes todavía esta semana</p>
        </div>
      </div>
    </section>

    <!-- ══ CTA FINAL ══ -->
    <section v-if="register !== false" class="final-cta reveal" :ref="setRevealRef">
      <span class="eyebrow eyebrow--center">Empieza hoy</span>
      <h2>Tu primera sala te espera</h2>
      <p>Crear cuenta toma menos de un minuto. El pozo no espera.</p>
      <button type="button" class="btn btn--primary btn--lg" @click="goToRegister">Crear cuenta gratis</button>
    </section>

    <!-- ══ FOOTER ══ -->
    <footer class="landing__footer">
      <span>PlayRKE © {{ new Date().getFullYear() }}</span>
      <nav class="landing__footer-legal">
        <a href="/info/terms">Términos</a>
        <a href="/info/about">Acerca de</a>
        <a href="/info/privacy">Privacidad</a>
      </nav>
    </footer>

    <!-- ══ Modal "Ver Top de Compras" ══ -->
    <RoomTopBuyersModal
      :room-id="topBuyersRoomId"
      :room-uuid-code="topBuyersRoomUuidCode"
      endpoint-base="/game-rooms"
      @close="closeTopBuyers"
    />
  </div>
</template>

<style scoped>
.landing {
  min-height: 100vh;
  background: var(--bg-0, #060a18);
  color: #c7d2f0;
  font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
}

h1, h2, h3 { font-family: "Orbitron", "Anton", sans-serif; margin: 0; color: #eef2ff; }

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.6px;
  text-transform: uppercase;
  color: #2af598;
}
.eyebrow i { font-size: 6px; filter: drop-shadow(0 0 4px #2af598); }
.eyebrow--sm { font-size: 10px; }
.eyebrow--center { display: flex; justify-content: center; }

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 18px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.3px;
  text-decoration: none;
  cursor: pointer;
  border: none;
  white-space: nowrap;
}
.btn--ghost { background: transparent; border: 1px solid rgba(255,255,255,.08); color: #c7d2f0; }
.btn--ghost:hover { border-color: #7a96c8; }
.btn--primary {
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%);
  color: #06121a;
  box-shadow: 0 6px 20px rgba(42,245,152,0.22);
}
.btn--primary:hover { filter: brightness(1.06); }
.btn--lg { padding: 14px 30px; font-size: 14.5px; }
.btn--block { width: 100%; justify-content: center; }

/* ── Foco accesible — el default del navegador desentona con el fondo oscuro ── */
.btn:focus-visible,
.landing__nav-links a:focus-visible,
.landing__footer-legal a:focus-visible,
.verify-form input:focus-visible {
  outline: 2px solid #2af598;
  outline-offset: 2px;
}

/* ── Anclas del nav sticky — sin esto el header tapa el título al saltar ── */
#salas, #verificar, #ganadores { scroll-margin-top: 76px; }

/* ── Nav ── */
.landing__nav {
  position: sticky;
  top: 0;
  z-index: 40;
  background: rgba(6,10,24,0.72);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.landing__nav-inner {
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
}
.landing__brand {
  font-family: "Orbitron", sans-serif;
  font-weight: 800;
  font-size: 18px;
  letter-spacing: 1px;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.landing__nav-links { display: flex; gap: 26px; font-size: 13px; font-weight: 600; color: #7a96c8; }
.landing__nav-links a { text-decoration: none; color: inherit; }
.landing__nav-links a:hover { color: #eef2ff; }
.landing__nav-cta { display: flex; gap: 10px; align-items: center; }

/* ── Hero ── */
.landing__hero {
  position: relative;
  overflow: hidden;
  padding: 64px 20px 40px;
  background:
    radial-gradient(ellipse 60% 50% at 22% 0%, rgba(123,79,255,0.28), transparent 60%),
    radial-gradient(ellipse 55% 45% at 85% 15%, rgba(0,158,253,0.18), transparent 60%),
    linear-gradient(160deg, #0a0f24 0%, #0c1230 45%, #060a18 100%);
}
.landing__hero-grid {
  max-width: 1180px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 48px;
  align-items: center;
}
.landing__hero-title {
  font-size: clamp(30px, 4.6vw, 50px);
  font-weight: 800;
  letter-spacing: 0.5px;
  line-height: 1.1;
  margin: 14px 0 16px;
}
.landing__hero-title em {
  font-style: normal;
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 45%, #7b4fff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.landing__hero-sub { font-size: 15px; line-height: 1.6; color: #7a96c8; max-width: 480px; margin: 0 0 24px; }
.landing__hero-actions { display: flex; gap: 12px; margin-bottom: 26px; flex-wrap: wrap; }
.landing__hero-stat {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14.5px;
  color: #eef2ff;
  margin-bottom: 16px;
}
.landing__hero-stat i { color: #2af598; font-size: 14.5px; }
.landing__hero-stat strong { color: #2af598; font-weight: 800; }
.landing__hero-trust { display: flex; gap: 20px; flex-wrap: wrap; font-size: 12px; color: #93a5c8; }
.landing__hero-trust span { display: flex; align-items: center; gap: 6px; }
.dot { width: 4px; height: 4px; border-radius: 50%; background: #93a5c8; }

/* ── Ticker ── */
.ticker-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 18px;
  backdrop-filter: blur(6px);
}
.ticker-card__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.ticker-card__head h3 { font-size: 12.5px; letter-spacing: 0.8px; text-transform: uppercase; color: #c7d2f0; }
.ticker-list { display: flex; flex-direction: column; gap: 9px; }
.ticker-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 11px;
  border-radius: 12px;
  background: rgba(255,255,255,0.05);
  border: 1px solid transparent;
}
.ticker-row--top {
  border-color: rgba(255,176,32,0.35);
  background: linear-gradient(90deg, rgba(255,176,32,0.10), rgba(255,176,32,0.02));
}
.ticker-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7b4fff, #009efd);
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; color: #fff;
}
.ticker-meta { flex: 1; min-width: 0; }
.ticker-name { font-size: 12.5px; font-weight: 700; color: #eef2ff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ticker-game { font-size: 11px; color: #4a6698; }
.ticker-amount { font-variant-numeric: tabular-nums; font-weight: 800; font-size: 13px; color: #2af598; white-space: nowrap; }
.ticker-row--top .ticker-amount { color: #ffb020; }
.ticker-card__foot { margin-top: 12px; text-align: center; font-size: 11px; color: #4a6698; }
.ticker-empty { font-size: 13px; color: #4a6698; text-align: center; padding: 20px 0; }

/* ── Section shell ── */
.band { padding: 60px 20px; max-width: 1180px; margin: 0 auto; }
.band--alt { max-width: none; background: rgba(255,255,255,0.015); border-top: 1px solid rgba(255,255,255,.08); border-bottom: 1px solid rgba(255,255,255,.08); }
.band--alt > .verify-grid { max-width: 1180px; margin: 0 auto; }
.band__head { text-align: center; max-width: 560px; margin: 0 auto 36px; }
.band__head h2 { font-size: clamp(21px, 3vw, 28px); margin: 10px 0 10px; }
.band__head p { color: #7a96c8; font-size: 13.5px; line-height: 1.6; margin: 0; }
.band__cta { text-align: center; margin-top: 28px; }

.rooms-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  max-width: 320px;
  margin: 24px auto;
  padding: 28px 20px;
  border-radius: 20px;
  text-align: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #9fb3da;
  font-size: 13.5px;
  font-weight: 600;
}
.rooms-empty i { font-size: 24px; color: #4a6698; }

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 220px));
  justify-content: center;
  gap: 18px;
}

/* ── Verify strip ── */
.verify-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; padding: 60px 20px; }
.verify-title { font-size: 25px; margin: 10px 0 14px; }
.verify-sub { color: #7a96c8; font-size: 13.5px; line-height: 1.6; margin: 0 0 22px; max-width: 440px; }
.verify-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px; }
.verify-steps li { display: flex; gap: 12px; align-items: flex-start; font-size: 13.5px; color: #7a96c8; line-height: 1.5; }
.verify-steps .num {
  flex-shrink: 0;
  width: 26px; height: 26px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,.08);
  display: flex; align-items: center; justify-content: center;
  font-size: 11.5px; font-weight: 800; color: #009efd;
  font-variant-numeric: tabular-nums;
}

.verify-form {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 22px;
}
.verify-form label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: #7a96c8;
  letter-spacing: 0.4px;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.verify-form input {
  width: 100%;
  background: #060a18;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 11px 14px;
  font-size: 13.5px;
  color: #eef2ff;
  margin-bottom: 14px;
  font-family: inherit;
}
.verify-form input:focus {
  outline: none;
  border-color: #2af598;
}
.verify-form input::placeholder { color: #4a6698; }

/* ── Leaderboard semanal ── */
.leaderboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; }
.lb-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 18px; position: relative; }
.lb-list { display: flex; flex-direction: column; }
.lb-row { display: flex; align-items: center; gap: 12px; padding: 10px 6px; border-bottom: 1px solid rgba(255,255,255,.08); }
.lb-row:last-child { border-bottom: none; }
.lb-rank { width: 22px; font-weight: 800; font-size: 13px; color: #4a6698; font-variant-numeric: tabular-nums; }
.lb-row:nth-child(1) .lb-rank { color: #ffb020; }
.lb-row:nth-child(2) .lb-rank { color: #c7d2e0; }
.lb-row:nth-child(3) .lb-rank { color: #d38a52; }
.lb-name { flex: 1; font-size: 13px; font-weight: 700; color: #eef2ff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.lb-amt { font-size: 13px; font-weight: 800; color: #2af598; font-variant-numeric: tabular-nums; }

/* ── Final CTA ── */
.final-cta {
  text-align: center;
  padding: 64px 20px 72px;
  background: radial-gradient(ellipse 70% 60% at 50% 100%, rgba(123,79,255,0.22), transparent 65%);
}
.final-cta h2 { font-size: clamp(22px, 3.4vw, 32px); margin: 14px 0; }
.final-cta p { color: #7a96c8; font-size: 14px; margin: 0 0 26px; }

/* ── Footer ── */
.landing__footer {
  border-top: 1px solid rgba(255,255,255,.08);
  padding: 26px 20px;
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: #4a6698;
}
.landing__footer-legal { display: flex; gap: 20px; }
.landing__footer-legal a { text-decoration: none; color: inherit; }
.landing__footer-legal a:hover { color: #c7d2f0; }

/* ── Scroll-reveal — ver useRevealOnScroll.ts; oculto por defecto y
   revelado al agregar .is-visible cuando la sección entra en viewport ── */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: none;
}

@media (max-width: 1040px) {
  .landing__hero-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
}

@media (max-width: 880px) {
  .landing__hero-grid, .verify-grid, .leaderboard-grid { grid-template-columns: 1fr; }
  .landing__nav-links { display: none; }
  .landing__footer { flex-direction: column; gap: 12px; text-align: center; }
}

@media (max-width: 640px) {
  .landing__nav-cta { gap: 6px; }
  .landing__nav-cta .btn { padding: 8px 13px; font-size: 12px; }
  .rooms-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
  .landing__hero-title { font-size: clamp(26px, 8vw, 50px); }
  .verify-form, .lb-card { padding: 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .reveal { opacity: 1; transform: none; transition: none; }
}
</style>
