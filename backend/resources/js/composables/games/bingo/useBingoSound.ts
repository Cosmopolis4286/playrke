/**
 * Composable de audio para el juego de Bingo.
 * Gestiona los tres sonidos disponibles:
 *   - next.mp3   → número sorteado
 *   - select.mp3 → celda marcada en el cartón
 *   - winner.mp3 → bingo / ganador
 *
 * El volumen del usuario se persiste en localStorage.
 * Carga lazy: el AudioContext se crea sólo cuando el usuario
 * interactúa por primera vez (política autoplay de los navegadores).
 */

import { ref, computed } from 'vue'

type SoundKey = 'next' | 'select' | 'winner'

const BASE = '/sounds/bingo/'
const FILES: Record<SoundKey, string> = {
  next:   `${BASE}next.mp3`,
  select: `${BASE}select.mp3`,
  winner: `${BASE}winner.mp3`,
}

// Estado compartido entre todas las instancias del composable
const _muted  = ref(localStorage.getItem('bingo_sound_muted') === '1')
const _volume = ref(parseFloat(localStorage.getItem('bingo_sound_vol') ?? '0.7'))
const _cache  = new Map<SoundKey, HTMLAudioElement>()

function _getAudio(key: SoundKey): HTMLAudioElement {
  if (!_cache.has(key)) {
    const a = new Audio(FILES[key])
    a.preload = 'auto'
    _cache.set(key, a)
  }
  return _cache.get(key)!
}

function play(key: SoundKey, volumeOverride?: number) {
  if (_muted.value) return
  try {
    const a = _getAudio(key)
    a.volume = Math.max(0, Math.min(1, volumeOverride ?? _volume.value))
    // Si ya está sonando, reinicia desde el principio
    a.currentTime = 0
    a.play().catch((err: unknown) => {
      // NotAllowedError = política de autoplay del navegador — esperado
      if (err instanceof DOMException && err.name === 'NotAllowedError') return
      console.warn('[BingoSound]', key, err)
    })
  } catch { /* ignore */ }
}

export function useBingoSound() {
  const muted  = computed({
    get: () => _muted.value,
    set: (v) => {
      _muted.value = v
      localStorage.setItem('bingo_sound_muted', v ? '1' : '0')
    },
  })

  const volume = computed({
    get: () => _volume.value,
    set: (v) => {
      _volume.value = v
      localStorage.setItem('bingo_sound_vol', String(v))
    },
  })

  function toggleMute() { muted.value = !muted.value }

  /** Número sorteado — reproducir next.mp3 */
  function playNext()   { play('next') }

  /** Celda marcada — reproducir select.mp3 a volumen reducido */
  function playSelect() { play('select', _volume.value * 0.65) }

  /** Bingo detectado o ganador — reproducir winner.mp3 */
  function playWinner() { play('winner') }

  /** Precargar todos los sonidos (llamar en onMounted) */
  function preload() {
    for (const k of Object.keys(FILES) as SoundKey[]) _getAudio(k)
  }

  return { muted, volume, toggleMute, playNext, playSelect, playWinner, preload }
}
