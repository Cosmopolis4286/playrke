/**
 * Plugin de sonidos del juego
 * Maneja efectos de sonido y música de fondo
 */

export default defineNuxtPlugin(() => {
  const sounds = ref<{ [key: string]: HTMLAudioElement }>({})
  const musicEnabled = ref(true)
  const sfxEnabled = ref(true)
  const volume = ref(0.7)

  // Cargar sonido
  const loadSound = (name: string, path: string) => {
    const audio = new Audio(path)
    audio.volume = volume.value
    sounds.value[name] = audio
  }

  // Reproducir sonido
  const playSound = (name: string, loop = false) => {
    if (!sfxEnabled.value) return

    const sound = sounds.value[name]
    if (sound) {
      sound.loop = loop
      sound.currentTime = 0
      sound.play().catch(err => console.error('Error playing sound:', err))
    }
  }

  // Detener sonido
  const stopSound = (name: string) => {
    const sound = sounds.value[name]
    if (sound) {
      sound.pause()
      sound.currentTime = 0
    }
  }

  // Establecer volumen
  const setVolume = (value: number) => {
    volume.value = Math.max(0, Math.min(1, value))
    Object.values(sounds.value).forEach(sound => {
      sound.volume = volume.value
    })
  }

  // Precargar sonidos comunes del juego
  if (process.client) {
    // TODO: Cargar sonidos reales cuando estén disponibles
    // loadSound('number-call', '/sounds/number-call.mp3')
    // loadSound('bingo', '/sounds/bingo.mp3')
    // loadSound('win', '/sounds/win.mp3')
    // loadSound('background', '/sounds/background.mp3')
  }

  return {
    provide: {
      sound: {
        loadSound,
        playSound,
        stopSound,
        setVolume,
        musicEnabled,
        sfxEnabled,
        volume
      }
    }
  }
})
