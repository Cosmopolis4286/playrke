import type { Ref } from 'vue'

/**
 * Registro de timers "flash" (con debounce por target) + retries programados,
 * todos limpiables de una vez en onUnmounted.
 */
export function useTimerRegistry() {
  const _pendingTimers = new Set<ReturnType<typeof setTimeout>>()
  const _flashPerTarget = new Map<Ref<boolean>, ReturnType<typeof setTimeout>>()

  function flash(target: Ref<boolean>, ms = 1000) {
    const prev = _flashPerTarget.get(target)
    if (prev !== undefined) { clearTimeout(prev); _pendingTimers.delete(prev) }
    target.value = true
    const t = setTimeout(() => {
      target.value = false
      _pendingTimers.delete(t)
      _flashPerTarget.delete(target)
    }, ms)
    _pendingTimers.add(t)
    _flashPerTarget.set(target, t)
  }

  function scheduleRetry(fn: () => void, ms: number) {
    const t = setTimeout(() => { _pendingTimers.delete(t); fn() }, ms)
    _pendingTimers.add(t)
  }

  function clearAll() {
    _pendingTimers.forEach(clearTimeout)
    _flashPerTarget.forEach((_, target) => { target.value = false })
    _flashPerTarget.clear()
  }

  return { flash, scheduleRetry, clearAll }
}
