import { computed, ref } from 'vue'
import { useDashboard } from '@/js/composables/common/dashboard/useDashboard'
import type {
  DashboardPlayerPageProps, PlayerStats, PrizeWonRow, TriviaActivityRow, KycStatus,
} from '@/js/types/roles/player/dashboard/dashboard-player'
import type { StatCardData, ActivityItem } from '@/js/types/common/dashboard/dashboard'

const CROSS_GAME_BRIDGE_DISMISS_KEY = 'playrke.dashboard.crossGameBridgeDismissed'

const DEFAULT_STATS: StatCardData[] = [
  { label: 'Saldo disponible',  value: '—', icon: 'fas fa-wallet',      variant: 'primary' },
  { label: 'Saldo de bono',     value: '—', icon: 'fas fa-gift',        variant: 'secondary' },
  { label: 'Salas disponibles', value: '—', icon: 'fas fa-hat-wizard',  variant: 'success' },
  { label: 'Cartones este mes', value: '—', icon: 'fas fa-ticket-alt',  variant: 'info'    },
  { label: 'Partidas jugadas',  value: '—', icon: 'fas fa-history',     variant: 'warning' },
]

export function usePlayerDashboard(props: DashboardPlayerPageProps) {
  const { sortedActivity } = useDashboard(undefined, props.recentActivity as ActivityItem[])

  const statCards = computed<StatCardData[]>(() => {
    if (!props.stats) return DEFAULT_STATS
    const s = props.stats as PlayerStats
    return [s.walletBalance, s.bonusBalance, s.activeRooms, s.cardsThisMonth, s.gamesPlayed]
  })

  const prizesWon = computed<PrizeWonRow[]>(() => props.prizesWon ?? [])

  const triviaActivity = computed<TriviaActivityRow[]>(() => props.triviaActivity ?? [])

  const kycStatus = computed<KycStatus | null>(() => props.kycStatus ?? null)

  const bridgeDismissed = ref(false)
  try {
    bridgeDismissed.value = localStorage.getItem(CROSS_GAME_BRIDGE_DISMISS_KEY) === '1'
  } catch {
    // localStorage puede no estar disponible (modo privado, etc.) — el banner simplemente no se recuerda cerrado
  }

  const showCrossGameBridge = computed(() => !!props.showCrossGameBridge && !bridgeDismissed.value)

  function dismissCrossGameBridge() {
    bridgeDismissed.value = true
    try {
      localStorage.setItem(CROSS_GAME_BRIDGE_DISMISS_KEY, '1')
    } catch {
      // no crítico si no se puede persistir
    }
  }

  return {
    statCards, sortedActivity, prizesWon, triviaActivity, kycStatus,
    showCrossGameBridge, dismissCrossGameBridge,
  }
}
