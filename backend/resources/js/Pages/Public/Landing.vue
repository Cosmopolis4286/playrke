<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useHead } from "@vueuse/head";
// La Landing usa components/layouts/inertia.blade.php (head minimalista, sin
// Bootstrap/AdminLTE) — a diferencia de las páginas de Auth, aquí NO llega
// el <link> de FontAwesome vendorizado en head.blade.php, así que sin este
// import los iconos (fab fa-facebook, etc.) no renderizan nunca. Mismo
// paquete e import que ya usa GuestLayout.vue con éxito.
import "@fortawesome/fontawesome-free/css/all.min.css";

import BingoGameRoomCard from "@js/Components/games/bingo/Room/GameRooms/BingoGameRoomCard.vue";
import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import GameRoomsKpiHeader from "@js/Components/games/bingo/Room/GameRooms/GameRoomsKpiHeader.vue";
import GameRoomsFilterBar from "@js/Components/games/bingo/Room/GameRooms/GameRoomsFilterBar.vue";
import BingoRulesModal from "@js/Pages/Games/Bingo/Config/Rules/BingoRulesModal.vue";
import InfoModal from "@js/Components/auth/InfoModal.vue";
import { useGameRoomCountdown } from "@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown";
import { useGameRoomKpis } from "@js/composables/games/bingo/room/game-rooms/useGameRoomKpis";
import { useGameRoomEcho } from "@js/composables/games/bingo/room/game-rooms/useGameRoomEcho";
import { usePlatformWinEvents } from "@js/composables/platform/usePlatformWinEvents";
import { useRevealOnScroll } from "@js/composables/ui/useRevealOnScroll";
import { axios } from "@js/plugins/axios";

import type { BingoGameRoomsPageProps, GameRoomFilters } from "@js/types/games/bingo/room/game-rooms/bingo-game-room";

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
// Copia reactiva propia (no las props directas): useGameRoomEcho parchea este
// array in-place con los mismos eventos WebSocket que usa la vista autenticada
// (bingo.lobby/bingo.room.{id} son canales públicos, sin auth), dándole a la
// landing el mismo realtime que /bingo/game-rooms en vez del listado estático
// que tenía antes.
const rooms = ref(props.rooms ? props.rooms.map((r) => ({ ...r })) : []);

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

const { init: initCountdown, getCountdown } = useGameRoomCountdown(serverTimestampMs);

onMounted(() => {
  initCountdown(rooms.value);
});

useGameRoomEcho(rooms, {
  onRoomAdded: (room) => initCountdown([room]),
});

// ── Filtro por variante (75/90/60/30) — mismo GameRoomsFilterBar que la
// vista autenticada, pero filtrado 100% client-side: con solo 3 salas
// destacadas no hay paginación ni filtrado server-side que valga la pena. ──
const filters = ref<GameRoomFilters>({ dateFrom: null, dateTo: null, variant: null });
const filteredRooms = computed(() => {
  if (!filters.value.variant && !filters.value.dateFrom) return rooms.value;
  return rooms.value.filter((room) => {
    if (filters.value.variant && room.config?.game_variant !== filters.value.variant) return false;
    if (filters.value.dateFrom) {
      const unix = room.config?.unix_date;
      if (!unix) return false;
      const roomDate = new Date(unix * 1000).toISOString().slice(0, 10);
      const to = filters.value.dateTo ?? filters.value.dateFrom;
      if (roomDate < filters.value.dateFrom || roomDate > to) return false;
    }
    return true;
  });
});
function resetFilters() {
  filters.value = { dateFrom: null, dateTo: null, variant: null };
}

const isFiltered = ref(false);
const { nextRoomUnixDate, totalActiveRooms } = useGameRoomKpis(rooms, isFiltered);

// ── Reglamento del bingo — sin dueño único en el contexto público
// (PublicController::gameRoomsShowcase() omite bingoRules a propósito),
// BingoRulesModal ya resuelve ese caso mostrando el reglamento genérico
// (BingoRulesDefault.vue) cuando no llega texto/imagen personalizados. ──
const showRulesModal = ref(false);

// La Landing es pública y siempre se ve oscura, sin importar el tema del
// dashboard guardado en localStorage (head.blade.php pone data-theme="light"
// en <html> ANTES de montar Vue, vía script anti-flash) — eso hacía que
// --ds-bg/--ds-surface (usadas por Modal.vue y otros 50+ componentes
// compartidos del dashboard) cayeran en su variante clara también aquí,
// por ejemplo en InfoModal.vue al abrir Términos/Acerca de/Privacidad.
// Se fuerza data-theme="dark" mientras la Landing está montada y se
// restaura el valor previo al salir, para no alterar la preferencia
// guardada del usuario si luego entra al dashboard real.
const previousTheme = document.documentElement.getAttribute("data-theme");
document.documentElement.setAttribute("data-theme", "dark");
const previousBodyBg = document.body.style.backgroundColor;
const previousBodyMargin = document.body.style.margin;
document.body.style.backgroundColor = "#060a18";
document.body.style.margin = "0";
onUnmounted(() => {
  if (previousTheme) {
    document.documentElement.setAttribute("data-theme", previousTheme);
  } else {
    document.documentElement.removeAttribute("data-theme");
  }
  document.body.style.backgroundColor = previousBodyBg;
  document.body.style.margin = previousBodyMargin;
});

function goToRegister() {
  window.location.href = "/register";
}

// ── Modal "Ver Top de Compras" (mismo componente que la vitrina completa) ──
const topBuyersRoomId = ref<string | null>(null);
const topBuyersRoomUuidCode = computed(() => {
  const room = rooms.value.find((r) => r.id === topBuyersRoomId.value);
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
  () => platformStats.value?.players_online ?? rooms.value.reduce((sum, r) => sum + (r.total_players ?? 0), 0) ?? 0
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

// ── Modales de Términos/Acerca de/Privacidad — mismo InfoModal reutilizado
// por GuestLayout.vue (Auth), en vez de navegar a /info/{page} y salir de
// la SPA como hacía antes el footer. ──
type InfoModalKey = "terms" | "about" | "privacy";
const activeInfoModal = ref<InfoModalKey | null>(null);
const openInfoModal = (key: InfoModalKey) => { activeInfoModal.value = key; };
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

    <!-- ══ SALAS DESTACADAS — mismo header/filtros/realtime que /bingo/game-rooms
         (vista autenticada), portado a la landing pública ══ -->
    <section id="salas" class="band band--rooms reveal" :ref="setRevealRef">
      <!-- Botón flotante: reglamento del bingo (mismo modal que la vista autenticada,
           usa el reglamento genérico ya que no hay dueño único sin sesión) -->
      <button
        type="button"
        class="grp-rules-fab"
        title="Ver reglamento del Bingo"
        aria-label="Ver reglamento del Bingo"
        @click="showRulesModal = true"
      >
        <i class="fas fa-book" />
      </button>

      <div class="game-rooms-page__header">
        <div class="grp-header-glow" />
        <div class="grp-title-wrap">
          <span class="grp-dice grp-dice--1">&#9858;</span>
          <h2 class="game-rooms-page__title">Donde el pozo crece en vivo</h2>
          <span class="grp-dice grp-dice--2">&#9858;</span>
        </div>
        <p class="game-rooms-page__subtitle">Salas activas ahora mismo. Cada cartón suma al premio.</p>
        <div class="grp-header-line" />

        <div class="grp-toolbar">
          <div class="grp-toolbar__top">
            <GameRoomsKpiHeader
              :next-room-unix-date="nextRoomUnixDate"
              :total-active-rooms="totalActiveRooms"
            />
          </div>

          <GameRoomsFilterBar :filters="filters" :loading="false" @apply="() => {}" @reset="resetFilters" />
        </div>
      </div>

      <div v-if="!filteredRooms.length" class="game-rooms-page__empty">
        <i class="fas fa-door-closed grp-empty-icon" />
        <p class="grp-empty-title">No hay salas activas en este momento</p>
      </div>
      <div v-else class="grp-cards-grid">
        <BingoGameRoomCard
          v-for="(room, index) in filteredRooms"
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
      <div class="landing__footer-inner">
        <div class="landing__footer-brand">
          <span class="landing__brand">PLAYRKE</span>
          <p class="landing__footer-tagline">Bingo en vivo con premios reales. Ves el pozo, ves quién gana.</p>
          <nav class="landing__footer-socials" aria-label="Redes sociales">
            <a href="https://www.facebook.com/my.play.rke" target="_blank" rel="noopener" aria-label="Facebook" class="fab fa-facebook" />
            <a href="https://www.instagram.com/my.play.rke" target="_blank" rel="noopener" aria-label="Instagram" class="fab fa-instagram" />
            <a href="https://t.me/+rV3KWBuuKOtjYzkx" target="_blank" rel="noopener" aria-label="Telegram" class="fab fa-telegram" />
          </nav>
        </div>

        <nav class="landing__footer-col" aria-label="Navegación">
          <span class="landing__footer-col-title">Plataforma</span>
          <a href="#salas">Salas en vivo</a>
          <a href="#verificar">Verificar cartón</a>
          <a href="#ganadores">Ganadores</a>
        </nav>

        <nav class="landing__footer-col" aria-label="Legal">
          <span class="landing__footer-col-title">Legal</span>
          <button type="button" class="landing__footer-link" @click="openInfoModal('terms')">Términos y condiciones</button>
          <button type="button" class="landing__footer-link" @click="openInfoModal('about')">Acerca de nosotros</button>
          <button type="button" class="landing__footer-link" @click="openInfoModal('privacy')">Política de privacidad</button>
        </nav>
      </div>

      <div class="landing__footer-bottom">
        <span>PlayRKE © {{ new Date().getFullYear() }}. Todos los derechos reservados.</span>
        <span class="landing__footer-trust">Retiros verificados · Cartones auditables por serial</span>
      </div>
    </footer>

    <!-- ══ Modal "Ver Top de Compras" ══ -->
    <RoomTopBuyersModal
      :room-id="topBuyersRoomId"
      :room-uuid-code="topBuyersRoomUuidCode"
      endpoint-base="/game-rooms"
      @close="closeTopBuyers"
    />

    <!-- ══ Modal Términos/Acerca de/Privacidad — mismo componente que Auth ══ -->
    <InfoModal v-model="activeInfoModal" />

    <!-- ══ Modal de reglamento del bingo — mismo componente que /bingo/game-rooms ══ -->
    <BingoRulesModal v-model="showRulesModal" :rules="props.bingoRules" :image="props.imageBingoRules" />
  </div>
</template>

<style>
/* Sin scoped: la Landing usa components/layouts/inertia.blade.php, un head
   minimalista sin Bootstrap/AdminLTE/reset alguno (Tailwind corre con
   corePlugins.preflight:false para no romper Bootstrap en el dashboard —
   ver tailwind.config.js) — así que nadie más resetea el margin de 8px
   que el navegador aplica a <body> por defecto. Confirmado inspeccionando
   el DOM real: getComputedStyle(body).marginLeft === "8px" sin esto,
   dejando franjas visibles a los lados aun con .landing ocupando el resto
   del ancho. También se fuerza el fondo oscuro real (sin importar
   --ds-bg del tema del dashboard guardado en localStorage) y un scrollbar
   a tono, ya que el scroll ocurre en el documento, no dentro de .landing. */
html:has(.landing), body:has(.landing) {
  margin: 0;
  background-color: #060a18 !important;
  overflow-x: hidden;
}

html:has(.landing) {
  scrollbar-width: thin;
  scrollbar-color: #2af598 rgba(255, 255, 255, 0.06);
}
html:has(.landing)::-webkit-scrollbar { width: 10px; }
html:has(.landing)::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.03); }
html:has(.landing)::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #2af598, #009efd);
  border-radius: 6px;
  border: 2px solid #060a18;
}
html:has(.landing)::-webkit-scrollbar-thumb:hover { background: #2af598; }
</style>

<style scoped>
/* Sin este reset, width:100% + padding en inputs/cards se suma al ancho
   (content-box, default del navegador) en vez de incluirse en él — causaba
   que .verify-form input y otros elementos con padding se desbordaran de
   su contenedor. */
.landing,
.landing *,
.landing *::before,
.landing *::after {
  box-sizing: border-box;
}

.landing {
  min-height: 100vh;
  background: var(--bg-0, #060a18);
  color: #c7d2f0;
  font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
  overflow-x: hidden;
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
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
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

/* ══════════════════════════════════════════════════════════
   SALAS DESTACADAS — portado de Pages/Games/Bingo/Room/GameRooms.vue
   (vista autenticada de /bingo/game-rooms), mismas clases grp- y
   game-rooms-page__ para reutilizar tal cual el header con dados, KPIs,
   filtros y FAB de reglamento. Las capas de fondo estelar (before/after
   en el original) se omiten aquí: .landing ya tiene su propio fondo de
   marca (gradientes + scroll-reveal) y duplicar un segundo fondo estelar
   solo en esta sección competiría visualmente con el resto de la página.
   ══════════════════════════════════════════════════════════ */
.band--rooms {
  position: relative;
  isolation: isolate;
  overflow: hidden;
}

/* Fondo estelar — mismas dos capas que Pages/Games/Bingo/Room/GameRooms.vue
   (vista autenticada), portadas literalmente para que la sección de salas
   comparta el mismo "mood" en ambos contextos. isolation:isolate en el
   padre crea un stacking context propio, así que z-index:-1/-2 aquí
   quedan detrás del contenido de ESTA sección sin colarse detrás de
   secciones hermanas (Landing es una página larga, a diferencia de
   GameRooms.vue que ocupa toda la pantalla). */
.band--rooms::before {
  content: "";
  position: absolute; inset: 0; z-index: -2; pointer-events: none;
  background-image:
    radial-gradient(1px 1px at  3%  4%, rgba(255,255,255,0.92) 0%, transparent 100%),
    radial-gradient(1px 1px at  9% 14%, rgba(255,255,255,0.60) 0%, transparent 100%),
    radial-gradient(1px 1px at 16%  2%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 22% 28%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 29%  9%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 36% 18%, rgba(255,255,255,0.55) 0%, transparent 100%),
    radial-gradient(1px 1px at 42%  5%, rgba(255,255,255,0.88) 0%, transparent 100%),
    radial-gradient(1px 1px at 50% 22%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 57%  8%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 63% 32%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at 70%  3%, rgba(255,255,255,0.86) 0%, transparent 100%),
    radial-gradient(1px 1px at 77% 18%, rgba(255,255,255,0.68) 0%, transparent 100%),
    radial-gradient(1px 1px at 84%  7%, rgba(255,255,255,0.75) 0%, transparent 100%),
    radial-gradient(1px 1px at 91% 25%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 97% 12%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(1px 1px at  5% 42%, rgba(255,255,255,0.62) 0%, transparent 100%),
    radial-gradient(1px 1px at 13% 55%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 21% 67%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(1px 1px at 28% 48%, rgba(255,255,255,0.50) 0%, transparent 100%),
    radial-gradient(1px 1px at 35% 72%, rgba(255,255,255,0.70) 0%, transparent 100%),
    radial-gradient(1px 1px at 44% 58%, rgba(255,255,255,0.42) 0%, transparent 100%),
    radial-gradient(1px 1px at 51% 80%, rgba(255,255,255,0.82) 0%, transparent 100%),
    radial-gradient(1px 1px at 59% 45%, rgba(255,255,255,0.64) 0%, transparent 100%),
    radial-gradient(1px 1px at 67% 62%, rgba(255,255,255,0.56) 0%, transparent 100%),
    radial-gradient(1px 1px at 74% 78%, rgba(255,255,255,0.74) 0%, transparent 100%),
    radial-gradient(1px 1px at 81% 50%, rgba(255,255,255,0.48) 0%, transparent 100%),
    radial-gradient(1px 1px at 88% 68%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 94% 85%, rgba(255,255,255,0.52) 0%, transparent 100%),
    radial-gradient(1px 1px at  7% 88%, rgba(255,255,255,0.66) 0%, transparent 100%),
    radial-gradient(1px 1px at 18% 93%, rgba(255,255,255,0.44) 0%, transparent 100%),
    radial-gradient(1px 1px at 33% 87%, rgba(255,255,255,0.72) 0%, transparent 100%),
    radial-gradient(1px 1px at 48% 95%, rgba(255,255,255,0.58) 0%, transparent 100%),
    radial-gradient(1px 1px at 62% 90%, rgba(255,255,255,0.80) 0%, transparent 100%),
    radial-gradient(1px 1px at 78% 96%, rgba(255,255,255,0.46) 0%, transparent 100%),
    radial-gradient(1px 1px at 92% 89%, rgba(255,255,255,0.76) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 11% 16%, rgba(190,210,255,0.95) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 26%  6%, rgba(210,185,255,0.88) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 46% 12%, rgba(180,225,255,0.90) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 66% 19%, rgba(200,255,220,0.82) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 85% 11%, rgba(255,240,180,0.85) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 38% 60%, rgba(190,210,255,0.78) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 72% 52%, rgba(255,200,200,0.72) 0%, transparent 100%),
    radial-gradient(1.5px 1.5px at 55% 76%, rgba(200,225,255,0.86) 0%, transparent 100%);
  animation: twinkle-a 7s ease-in-out infinite alternate;
}

.band--rooms::after {
  content: "";
  position: absolute; inset: 0; z-index: -1; pointer-events: none;
  background-image:
    radial-gradient(2px 2px at  8% 12%, rgba(255,255,255,0.96) 0%, transparent 100%),
    radial-gradient(2px 2px at 24% 35%, rgba(200,215,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 40%  6%, rgba(255,255,255,0.84) 0%, transparent 100%),
    radial-gradient(2px 2px at 58% 22%, rgba(255,240,180,0.92) 0%, transparent 100%),
    radial-gradient(2px 2px at 74% 15%, rgba(180,200,255,0.86) 0%, transparent 100%),
    radial-gradient(2px 2px at 90% 38%, rgba(255,255,255,0.78) 0%, transparent 100%),
    radial-gradient(2px 2px at 15% 65%, rgba(200,255,240,0.82) 0%, transparent 100%),
    radial-gradient(2px 2px at 50% 55%, rgba(220,200,255,0.88) 0%, transparent 100%),
    radial-gradient(2px 2px at 80% 72%, rgba(255,255,255,0.90) 0%, transparent 100%),
    radial-gradient(2px 2px at 35% 82%, rgba(200,220,255,0.80) 0%, transparent 100%),
    radial-gradient(2px 2px at 68% 88%, rgba(255,250,200,0.84) 0%, transparent 100%),
    radial-gradient(3px 3px at 19% 20%, rgba(255,255,255,1.00) 0%, rgba(210,230,255,0.20) 55%, transparent 100%),
    radial-gradient(3px 3px at 44% 14%, rgba(220,200,255,0.96) 0%, rgba(185,165,255,0.18) 55%, transparent 100%),
    radial-gradient(3px 3px at 69% 42%, rgba(255,255,255,0.94) 0%, rgba(225,245,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 88% 60%, rgba(200,240,255,0.98) 0%, rgba(160,220,255,0.15) 55%, transparent 100%),
    radial-gradient(3px 3px at 30% 78%, rgba(255,240,160,0.90) 0%, rgba(255,220,100,0.12) 55%, transparent 100%),
    radial-gradient(4px 4px at 55% 30%, rgba(255,255,255,1.00) 0%, rgba(205,225,255,0.28) 50%, transparent 100%),
    radial-gradient(4px 4px at 12% 45%, rgba(255,248,200,0.95) 0%, rgba(255,240,160,0.18) 50%, transparent 100%);
  animation: twinkle-b 9s ease-in-out infinite alternate-reverse;
}

@keyframes twinkle-a {
  0%   { opacity: 0.55; } 40%  { opacity: 0.95; }
  70%  { opacity: 0.70; } 100% { opacity: 0.85; }
}
@keyframes twinkle-b {
  0%   { opacity: 0.70; } 35%  { opacity: 0.50; }
  65%  { opacity: 1.00; } 100% { opacity: 0.65; }
}

.grp-rules-fab {
  position: absolute;
  right: 16px;
  bottom: 18px;
  z-index: 5;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid rgba(123,79,255,0.35);
  background: linear-gradient(135deg, #2a1f5e 0%, #1a2a52 100%);
  color: #cdd8ff;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.5);
  transition: transform .2s ease, box-shadow .2s ease, color .2s ease, border-color .2s ease;
  animation: rulesFabPulse 3s ease-in-out infinite;
}
.grp-rules-fab:hover {
  transform: translateY(-2px) scale(1.06);
  color: #fff;
  border-color: rgba(42,245,152,0.6);
  box-shadow: 0 6px 22px rgba(0,0,0,0.5), 0 0 18px rgba(42,245,152,0.35);
}
.grp-rules-fab:active { transform: translateY(0) scale(0.97); }
@keyframes rulesFabPulse {
  0%, 100% { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 0 rgba(123,79,255,0.45); }
  50%      { box-shadow: 0 4px 18px rgba(0,0,0,0.45), 0 0 0 8px rgba(123,79,255,0); }
}

.game-rooms-page__header { text-align: center; margin-bottom: 32px; position: relative; z-index: 5; }

/* Entrada propia del título — antes solo heredaba el fade genérico de
   .reveal (toda la sección); esto le da su propio "pop" (scale+fade) con
   un pequeño delay respecto al resto, para que se sienta como la pieza
   protagonista del header en vez de aparecer plano junto a todo lo demás. */
.reveal .game-rooms-page__title {
  display: inline-block;
  opacity: 0;
  transform: scale(0.92);
  transition: opacity 0.6s ease 0.15s, transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s;
}
.reveal.is-visible .game-rooms-page__title {
  opacity: 1;
  transform: scale(1);
}
.grp-header-glow {
  position: absolute;
  top: -60px; left: 50%; transform: translateX(-50%);
  width: 500px; height: 200px;
  background: radial-gradient(ellipse, rgba(123,79,255,0.25) 0%, transparent 70%);
  pointer-events: none;
}
.grp-title-wrap { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 4px; }
.grp-dice {
  font-size: 28px;
  filter: drop-shadow(0 0 10px rgba(42,245,152,0.6));
  animation: spinDice 8s linear infinite;
  color: #2af598;
  flex-shrink: 0;
}
.grp-dice--2 { animation-direction: reverse; animation-duration: 10s; color: #009efd; }
@keyframes spinDice { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.game-rooms-page__title {
  font-family: "Orbitron", "Anton", sans-serif;
  font-size: clamp(26px, 4.2vw, 38px);
  font-weight: 800;
  letter-spacing: 1px;
  margin: 0;
  text-shadow: 0 0 30px rgba(42,245,152,0.22), 0 0 60px rgba(123,79,255,0.12), 0 2px 4px rgba(0,0,0,0.5);
  background: linear-gradient(135deg, #e0eaff 0%, #a8c4ff 50%, #7b4fff 100%);
  background-size: 200% auto;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: title-shine 5s ease-in-out infinite;
}
@keyframes title-shine {
  0%, 100% { background-position: 0% center; }
  50%      { background-position: 100% center; }
}
.game-rooms-page__subtitle {
  font-size: 13.5px;
  color: #7a96c8;
  letter-spacing: 0.2px;
  margin: 6px 0 14px;
  max-width: 380px;
  margin-left: auto;
  margin-right: auto;
}

.grp-header-line {
  width: 60px; height: 2px; margin: 0 auto;
  background: linear-gradient(90deg, transparent, #2af598, #009efd, transparent);
  border-radius: 2px;
  position: relative;
}
.grp-header-line::after {
  content: '';
  position: absolute; top: -1px; left: 50%; transform: translateX(-50%);
  width: 6px; height: 4px;
  background: #2af598; border-radius: 50%;
  box-shadow: 0 0 8px #2af598;
}

.grp-toolbar { display: flex; flex-direction: column; align-items: center; gap: 10px; margin: 18px auto 0; max-width: 620px; position: relative; z-index: 30; }
.grp-toolbar__top { display: flex; align-items: center; justify-content: center; gap: 8px; }

.game-rooms-page__empty {
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
}
.grp-empty-icon { font-size: 24px; color: #4a6698; }
.grp-empty-title { font-size: 13.5px; font-weight: 600; color: #9fb3da; margin: 0; }

.grp-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 220px));
  justify-content: center;
  gap: 18px;
}

@media (max-width: 380px) {
  .grp-cards-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
  .grp-rules-fab { right: 12px; bottom: 14px; width: 34px; height: 34px; font-size: 12px; }
}

/* ── Verify strip ── */
.verify-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 40px;
  align-items: center;
  padding: 60px 20px;
}
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
.leaderboard-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 40px; align-items: center; }
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
  position: relative;
  border-top: 1px solid rgba(255,255,255,.08);
  background: linear-gradient(180deg, rgba(123,79,255,0.05) 0%, transparent 40%), #050813;
  padding: 56px 20px 28px;
}
/* Filete de marca sobre el borde superior — mismo lenguaje que el resto
   del sitio (gradiente mint→azul→violeta) en vez de una línea neutra. */
.landing__footer::before {
  content: '';
  position: absolute;
  top: -1px; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, #2af598 20%, #7b4fff 50%, #009efd 80%, transparent);
  opacity: 0.5;
}

.landing__footer-inner {
  max-width: 1180px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 40px;
  padding-bottom: 40px;
}

.landing__footer-brand { display: flex; flex-direction: column; gap: 12px; align-items: flex-start; }
.landing__footer-tagline { margin: 0; font-size: 13px; line-height: 1.6; color: #7a96c8; max-width: 320px; }
.landing__footer-socials { display: flex; gap: 14px; }
.landing__footer-socials a {
  display: flex; align-items: center; justify-content: center;
  width: 34px; height: 34px;
  border-radius: 10px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #7a96c8;
  font-size: 14px;
  text-decoration: none;
  transition: color 0.2s, border-color 0.2s, transform 0.2s;
}
.landing__footer-socials a:hover {
  color: #2af598;
  border-color: rgba(42,245,152,0.35);
  transform: translateY(-2px);
}

.landing__footer-col { display: flex; flex-direction: column; gap: 12px; }
.landing__footer-col-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  color: #eef2ff;
  margin-bottom: 2px;
}
.landing__footer-col a,
.landing__footer-link {
  font-family: inherit;
  font-size: 13px;
  text-decoration: none;
  color: #7a96c8;
  transition: color 0.2s;
}
/* .landing__footer-link es <button> (abre InfoModal) — reset de estilos
   nativos de botón para que se vea idéntico a los <a> de la misma columna. */
.landing__footer-link {
  background: none;
  border: none;
  padding: 0;
  text-align: inherit;
  cursor: pointer;
}
.landing__footer-col a:hover,
.landing__footer-link:hover { color: #2af598; }

.landing__footer-bottom {
  max-width: 1180px;
  margin: 0 auto;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 12px;
  color: #4a6698;
}
.landing__footer-trust { color: #4a6698; }

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
  .landing__hero-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; }
  .verify-grid, .leaderboard-grid { gap: 28px; }
}

@media (max-width: 880px) {
  .landing__hero-grid, .verify-grid, .leaderboard-grid { grid-template-columns: minmax(0, 1fr); }
  .landing__nav-links { display: none; }
  .band { padding: 44px 20px; }
  .verify-grid { padding: 40px 20px; gap: 32px; }
  .verify-sub { max-width: none; }
  .landing__footer-inner { grid-template-columns: 1fr 1fr; gap: 28px 24px; }
  .landing__footer-brand { grid-column: 1 / -1; align-items: center; text-align: center; }
  .landing__footer-tagline { max-width: none; }
  .landing__footer-col { align-items: center; text-align: center; }
}

@media (max-width: 640px) {
  .landing__nav-cta { gap: 6px; }
  .landing__nav-cta .btn { padding: 8px 13px; font-size: 12px; }
  .grp-cards-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
  .landing__hero-title { font-size: clamp(26px, 8vw, 50px); }
  .verify-form, .lb-card { padding: 16px; }
  .verify-grid { padding: 32px 16px; gap: 24px; }
  .landing__footer { padding: 40px 16px 20px; }
  .landing__footer-inner { grid-template-columns: 1fr; text-align: center; }
  .landing__footer-bottom { flex-direction: column; text-align: center; }
  .verify-title { font-size: clamp(19px, 6vw, 25px); }
  .verify-form input { padding: 10px 12px; font-size: 13px; }
}

@media (prefers-reduced-motion: reduce) {
  .reveal { opacity: 1; transform: none; transition: none; }
  .reveal .game-rooms-page__title { opacity: 1; transform: none; transition: none; }
  .game-rooms-page__title { animation: none; }
  .band--rooms::before,
  .band--rooms::after { animation: none; }
  .grp-dice { animation: none; }
}
</style>
