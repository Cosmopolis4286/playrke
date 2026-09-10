<script setup lang="ts">
import { onMounted, onUnmounted, ref, reactive, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { useHead } from "@vueuse/head";
// La Landing usa components/layouts/inertia.blade.php (head minimalista, sin
// Bootstrap/AdminLTE) — a diferencia de las páginas de Auth, aquí NO llega
// el <link> de FontAwesome vendorizado en head.blade.php, así que sin este
// import los iconos (fab fa-facebook, etc.) no renderizan nunca. Mismo
// paquete e import que ya usa GuestLayout.vue con éxito.
import "@fortawesome/fontawesome-free/css/all.min.css";

import RoomTopBuyersModal from "@js/Components/games/bingo/Room/GameRooms/RoomTopBuyersModal.vue";
import GameRoomsSection from "@js/Components/games/bingo/Room/GameRooms/GameRoomsSection.vue";
import InfoModal from "@js/Components/auth/InfoModal.vue";
import JackpotSection from "@js/Components/Platform/JackpotSection.vue";
import PublicNavbar from "@js/Components/Platform/PublicNavbar.vue";
import type { NavLink } from "@js/Components/Platform/PublicNavbar.vue";
import type { JackpotEntry } from "@js/Components/Platform/JackpotDisplay.vue";
import { usePlatformWinEvents } from "@js/composables/platform/usePlatformWinEvents";
import { verifyCardSchema, SERIAL_MASK, PHONE_LAST4_MASK } from "@js/composables/public/verifyCardSchema";
import { IMaskDirective } from "vue-imask";
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
    initialJackpots?: JackpotEntry[];
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

const serverTimestampMs = props.dateTimeRooms?.timestamp
  ? Number(props.dateTimeRooms.timestamp)
  : Date.now();

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

const navLinks: NavLink[] = [
  { href: "#jackpot", label: "Jackpot" },
  { href: "#salas", label: "Salas en vivo" },
  { href: "#verificar", label: "Verificar cartón" },
  { href: "#invita", label: "Invita y gana" },
  { href: "#ganadores", label: "Ganadores" },
];

// ── Modal "Ver Top de Compras" (mismo componente que la vitrina completa) ──
// GameRoomsSection mantiene su propia copia reactiva de rooms (con realtime
// vía Echo) internamente — el uuid_code se resuelve contra props.rooms, que
// no cambia de identidad aunque el precio/pozo se actualice en vivo.
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

// ── Jackpots — sembrados por el backend, refrescados por el propio
// JackpotDisplay.vue (polling interno cada 15s, mismo TTL de cache del
// endpoint) — sin orquestar aquí, el componente es autosuficiente.
const jackpots = ref<JackpotEntry[]>(props.initialJackpots ?? []);

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
  () => platformStats.value?.players_online ?? (props.rooms ?? []).reduce((sum, r) => sum + (r.total_players ?? 0), 0) ?? 0
);
const totalPaidFormatted = computed(() => {
  const total = platformStats.value?.total_paid_usd;
  if (total == null) return null;
  return total.toLocaleString("es", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
});

// ── Verificador de cartón (inline, envía a /verify-card con los valores
// precargados) — valida formato antes de redirigir con las mismas reglas
// que VerifyCardRequest del backend, para no mandar al usuario a una página
// nueva solo para descubrir ahí que el serial o el teléfono están mal. ──
const cardSerial = ref("");
const cardPhone = ref("");
const verifyErrors = reactive<{ serial?: string; phone?: string }>({});
const vImask = IMaskDirective;

function onSerialAccept(event: CustomEvent) {
  cardSerial.value = event.detail.value;
  verifyErrors.serial = undefined;
}
function onPhoneAccept(event: CustomEvent) {
  cardPhone.value = event.detail.value;
  verifyErrors.phone = undefined;
}

function submitVerify() {
  verifyErrors.serial = undefined;
  verifyErrors.phone = undefined;

  const result = verifyCardSchema.safeParse({
    serial_number: cardSerial.value,
    phone_last4: cardPhone.value,
  });
  if (!result.success) {
    for (const issue of result.error.issues) {
      const path = issue.path[0];
      if (path === "serial_number") verifyErrors.serial = issue.message;
      if (path === "phone_last4") verifyErrors.phone = issue.message;
    }
    return;
  }

  const params = new URLSearchParams({
    serial: cardSerial.value.trim(),
    phone: cardPhone.value.trim(),
  });
  window.location.href = `/verify-card?${params.toString()}`;
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
    <PublicNavbar :links="navLinks" :show-register="register !== false" @register="goToRegister" />

    <!-- ══ HERO ══ -->
    <header class="landing__hero">
      <div class="landing__hero-grid">
        <div>
          <span class="eyebrow hero-in" style="--stagger-index: 1">
            <i class="fas fa-circle" /> {{ onlineCount }} jugadores en salas activas
          </span>
          <h1 class="landing__hero-title hero-in" style="--stagger-index: 2">Juega en vivo.<br /><em>Gana en serio.</em></h1>
          <p class="landing__hero-sub hero-in" style="--stagger-index: 3">
            Salas de bingo con premios reales, actualizadas en tiempo real. Ves el pozo,
            ves quién compró, ves quién ganó.
          </p>
          <div class="landing__hero-actions hero-in" style="--stagger-index: 4">
            <button v-if="register !== false" type="button" class="btn btn--primary btn--lg" @click="goToRegister">
              Crear cuenta gratis
            </button>
            <a class="btn btn--ghost btn--lg" href="#salas">Ver salas activas</a>
          </div>
          <div v-if="totalPaidFormatted" class="landing__hero-stat hero-in" style="--stagger-index: 5">
            <i class="fas fa-sack-dollar" />
            <span><strong>${{ totalPaidFormatted }} USD</strong> repartidos en premios a la fecha</span>
          </div>
          <div class="landing__hero-trust hero-in" style="--stagger-index: 6">
            <span>Retiros verificados</span>
            <span class="dot" />
            <span>Cartones auditables por serial</span>
            <span class="dot" />
            <span>Soporte 24/7</span>
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

    <!-- ══ JACKPOT — pozo progresivo, alimentado por JackpotLedgerService en
         cada venta de cartón (dominio ya existía, sin ninguna UI hasta ahora) ══ -->
    <div class="reveal reveal--pop" :ref="setRevealRef">
      <JackpotSection :jackpots="jackpots" />
    </div>

    <!-- ══ SALAS DESTACADAS — componente compartido con GameRoomsShowcase.vue ══ -->
    <div id="salas" class="band band--rooms reveal" :ref="setRevealRef">
      <GameRoomsSection
        :rooms="props.rooms ?? []"
        :server-timestamp-ms="serverTimestampMs"
        title="Donde el pozo crece en vivo"
        subtitle="Salas activas ahora mismo. Cada cartón suma al premio."
        @select="goToRegister"
        @show-top-buyers="openTopBuyers"
      >
        <template #after-grid>
          <div class="band__cta">
            <Link href="/game-rooms" class="btn btn--ghost">Ver todas las salas →</Link>
          </div>
        </template>
      </GameRoomsSection>
    </div>

    <!-- ══ VERIFICAR CARTÓN ══ -->
    <section id="verificar" class="band band--alt reveal reveal--left" :ref="setRevealRef">
      <div class="verify-grid">
        <div>
          <span class="eyebrow">Sin necesidad de cuenta</span>
          <h2 class="verify-title">Verifica tu cartón como un ticket físico</h2>
          <p class="verify-sub">
            ¿Compraste un cartón por WhatsApp o en punto físico? Confírmalo en segundos
            con el número de serial y los últimos 4 dígitos de tu teléfono.
          </p>
          <ul class="verify-steps">
            <li class="reveal-child" style="--stagger-index: 0"><span class="num">1</span> Ingresa el serial impreso en tu cartón</li>
            <li class="reveal-child" style="--stagger-index: 1"><span class="num">2</span> Ingresa los últimos 4 dígitos de tu teléfono</li>
            <li class="reveal-child" style="--stagger-index: 2"><span class="num">3</span> Ve el estado, la sala y el resultado al instante</li>
          </ul>
        </div>
        <form class="verify-form" novalidate @submit.prevent="submitVerify">
          <label for="landing-serial">Serial del cartón</label>
          <input
            id="landing-serial"
            v-imask="SERIAL_MASK"
            :value="cardSerial"
            type="text"
            placeholder="BINGO-A1B2C3D4E5F67"
            autocomplete="off"
            :aria-invalid="!!verifyErrors.serial"
            :class="{ 'verify-form__input--error': verifyErrors.serial }"
            @accept="onSerialAccept"
          />
          <p v-if="verifyErrors.serial" class="verify-form__error">{{ verifyErrors.serial }}</p>

          <label for="landing-phone">Últimos 4 dígitos del teléfono</label>
          <input
            id="landing-phone"
            v-imask="PHONE_LAST4_MASK"
            :value="cardPhone"
            type="text"
            inputmode="numeric"
            placeholder="••••"
            autocomplete="off"
            :aria-invalid="!!verifyErrors.phone"
            :class="{ 'verify-form__input--error': verifyErrors.phone }"
            @accept="onPhoneAccept"
          />
          <p v-if="verifyErrors.phone" class="verify-form__error">{{ verifyErrors.phone }}</p>

          <button type="submit" class="btn btn--primary btn--block">Verificar cartón</button>
        </form>
      </div>
    </section>

    <!-- ══ INVITA Y GANA — sistema de referidos ya existente en backend
         (código propio, link compartible, recompensa XP anti-fraude vía
         AwardReferralXpOnVerifiedListener), sin UI pública hasta ahora. El
         código/link real solo se ve ya logueado (perfil); esta sección solo
         explica la mecánica para atraer registros. ══ -->
    <section id="invita" class="band band--alt reveal reveal--right" :ref="setRevealRef">
      <div class="verify-grid">
        <div>
          <span class="eyebrow">Programa de referidos</span>
          <h2 class="verify-title">Invita y gana</h2>
          <p class="verify-sub">
            Comparte tu enlace único cuando crees tu cuenta. Cuando un amigo
            se registre y verifique su cuenta, ganas XP.
          </p>
          <ul class="verify-steps">
            <li class="reveal-child" style="--stagger-index: 0"><span class="num">1</span> Crea tu cuenta y obtén tu enlace de invitación personal</li>
            <li class="reveal-child" style="--stagger-index: 1"><span class="num">2</span> Comparte tu enlace con amigos</li>
            <li class="reveal-child" style="--stagger-index: 2"><span class="num">3</span> Cuando se registren y verifiquen su cuenta, ganas XP automáticamente</li>
          </ul>
        </div>
        <div class="invite-card">
          <i class="fas fa-user-plus invite-card__icon" />
          <p class="invite-card__lead">Tu enlace de invitación te espera dentro de tu perfil</p>
          <button type="button" class="btn btn--primary btn--block" @click="goToRegister">
            Crear cuenta gratis
          </button>
        </div>
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
            <div
              v-for="(entry, i) in leaderboard"
              :key="entry.rank"
              class="lb-row reveal-child"
              :style="{ '--stagger-index': i }"
            >
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
    <section v-if="register !== false" class="final-cta reveal reveal--cta" :ref="setRevealRef">
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
          <a href="#jackpot">Jackpot</a>
          <a href="#salas">Salas en vivo</a>
          <a href="#verificar">Verificar cartón</a>
          <a href="#invita">Invita y gana</a>
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
.landing__footer-legal a:focus-visible,
.verify-form input:focus-visible {
  outline: 2px solid #2af598;
  outline-offset: 2px;
}

/* ── Anclas del nav sticky — sin esto el header tapa el título al saltar ──
   El nav en sí (sticky, logo, links, CTAs) ahora vive en PublicNavbar.vue,
   compartido con GameRoomsShowcase.vue. */
#jackpot, #salas, #verificar, #invita, #ganadores { scroll-margin-top: 76px; }

/* ── Hero ── */
.landing__hero {
  position: relative;
  overflow: hidden;
  padding: 40px 20px;
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

/* ── Salas destacadas — el header/KPIs/filtros/grid ahora viven en
   GameRoomsSection.vue (componente compartido con GameRoomsShowcase.vue).
   .band--rooms solo aporta el padding del contenedor externo; el fondo
   estelar se omite a propósito en la Landing (ya tiene su propio fondo de
   marca) — ver prop starryBackground del componente, sin usar aquí. ── */
.band--rooms { padding: 60px 0; }

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
.verify-form__input--error {
  border-color: rgba(248,113,113,0.55) !important;
}
.verify-form__error {
  margin: -10px 0 14px;
  font-size: 11.5px;
  color: #f87171;
}

/* ── Invita y gana ── */
.invite-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 32px 24px;
}
.invite-card__icon {
  font-size: 28px;
  color: #2af598;
  filter: drop-shadow(0 0 10px rgba(42,245,152,0.4));
}
.invite-card__lead {
  margin: 0;
  color: #c7d2f0;
  font-size: 14px;
  line-height: 1.6;
  max-width: 280px;
}

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
  position: relative;
  text-align: center;
  padding: 64px 20px 72px;
  overflow: hidden;
}
.final-cta::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 100%, rgba(123,79,255,0.22), transparent 65%);
  animation: final-cta-breathe 5s ease-in-out infinite;
  pointer-events: none;
}
@keyframes final-cta-breathe {
  0%, 100% { opacity: 0.75; }
  50%      { opacity: 1; }
}
.final-cta > * { position: relative; z-index: 1; }
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

/* ── Hero — entrada al montar (no ligada al scroll: es contenido
   above-the-fold, visible de inmediato). Se anima con @keyframes en vez del
   patrón .reveal (que depende de un IntersectionObserver que nunca
   dispararía aquí porque el hero ya está en el viewport en el primer
   frame) — no afecta LCP real: el contenido se pinta en su posición final
   casi de inmediato, solo con un fundido corto. */
.hero-in {
  animation: hero-in-anim 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: calc(var(--stagger-index, 0) * 80ms);
}
@keyframes hero-in-anim {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: none; }
}

/* ── Scroll-reveal — ver useRevealOnScroll.ts; oculto por defecto y
   revelado al agregar .is-visible cuando la sección entra en viewport.
   Variantes por sección (en vez de un único fade+translateY genérico en
   toda la página) para que el recorrido se sienta más vivo: cada bloque
   "llega" de una forma distinta, coherente con su contenido. ── */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
.reveal.is-visible {
  opacity: 1;
  transform: none;
}

/* Jackpot — "pop" con leve escala, refuerza el efecto de sección más
   dramática de la página. */
.reveal--pop {
  transform: translateY(20px) scale(0.96);
}
.reveal--pop.is-visible {
  transform: none;
}

/* Verificar cartón — el bloque de texto entra desde la izquierda. */
.reveal--left {
  transform: translateX(-32px);
}
.reveal--left.is-visible {
  transform: none;
}

/* Invita y gana — espejo del anterior, entra desde la derecha (variedad
   frente a "Verificar cartón", misma estructura visual .verify-grid). */
.reveal--right {
  transform: translateX(32px);
}
.reveal--right.is-visible {
  transform: none;
}

/* CTA final — el llamado a la acción con más énfasis: escala más notoria +
   un halo que aparece junto con el contenido. */
.reveal--cta {
  transform: translateY(28px) scale(0.94);
}
.reveal--cta.is-visible {
  transform: none;
}

/* ── Stagger de hijos — dentro de una sección ya revelada, sus elementos
   directos (pasos numerados, cards) entran en cascada leyendo
   --stagger-index (asignado inline por elemento) en vez de todos a la vez. ── */
.reveal-child {
  opacity: 0;
  transform: translateY(14px);
  transition: opacity 0.5s ease, transform 0.5s ease;
  transition-delay: calc(var(--stagger-index, 0) * 90ms);
}
.reveal.is-visible .reveal-child {
  opacity: 1;
  transform: none;
}

@media (max-width: 1040px) {
  .landing__hero-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; }
  .verify-grid, .leaderboard-grid { gap: 28px; }
}

@media (max-width: 880px) {
  .landing__hero-grid, .verify-grid, .leaderboard-grid { grid-template-columns: minmax(0, 1fr); }
  .band { padding: 44px 20px; }
  .verify-grid { padding: 40px 20px; gap: 32px; }
  .verify-sub { max-width: none; }
  .landing__footer-inner { grid-template-columns: 1fr 1fr; gap: 28px 24px; }
  .landing__footer-brand { grid-column: 1 / -1; align-items: center; text-align: center; }
  .landing__footer-tagline { max-width: none; }
  .landing__footer-col { align-items: center; text-align: center; }
}

@media (max-width: 640px) {
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
  .reveal,
  .reveal-child { opacity: 1; transform: none; transition: none; }
  .hero-in { animation: none; opacity: 1; transform: none; }
  .final-cta::before { animation: none; }
}
</style>
