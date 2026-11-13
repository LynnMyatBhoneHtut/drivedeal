// usePolling - run a function again and again on a timer, the polite way. OWNER: Yati.
// Used by the notification bell to ask the server "any new price-drop alerts?".
//
//   const { isRunning, lastRun } = usePolling(checkForAlerts, { interval: 15000, enabled: isLoggedInRef })
//
// What it does beyond a plain setInterval:
//  - Starts and stops by itself when `enabled` (a ref or getter) changes, e.g. on login / logout.
//  - Pauses while the browser tab is hidden (Page Visibility API), so a tab left open in the
//    background doesn't keep calling the server, and checks straight away when you come back.
//  - Never runs two checks at once: if the server is slow, the next tick waits for the last one.
//  - Cleans up its timer and listener when the component using it is removed (onScopeDispose).
import { onScopeDispose, ref, toValue, watch } from 'vue'

export function usePolling(task, { interval = 15000, enabled = true, immediate = true } = {}) {
  const isRunning = ref(false) // timer active
  const isBusy = ref(false) // a check is happening right now
  const lastRun = ref(null) // Date of the last finished check
  const lastError = ref('')
  let timer = null

  async function runOnce() {
    if (isBusy.value) return
    isBusy.value = true
    try {
      await task()
      lastError.value = ''
    } catch (e) {
      lastError.value = e.message // keep polling: a short network blip shouldn't stop the alerts
    } finally {
      isBusy.value = false
      lastRun.value = new Date()
    }
  }

  function schedule() {
    clearTimeout(timer)
    timer = setTimeout(async () => {
      if (!document.hidden) await runOnce()
      if (isRunning.value) schedule()
    }, interval)
  }

  function start() {
    if (isRunning.value) return
    isRunning.value = true
    if (immediate) runOnce()
    schedule()
  }

  function stop() {
    isRunning.value = false
    clearTimeout(timer)
    timer = null
  }

  // Coming back to the tab -> check now instead of waiting for the next tick
  function onVisibilityChange() {
    if (!document.hidden && isRunning.value) {
      runOnce()
      schedule()
    }
  }
  document.addEventListener('visibilitychange', onVisibilityChange)

  watch(
    () => Boolean(toValue(enabled)),
    (on) => (on ? start() : stop()),
    { immediate: true }
  )

  onScopeDispose(() => {
    stop()
    document.removeEventListener('visibilitychange', onVisibilityChange)
  })

  return { isRunning, isBusy, lastRun, lastError, runNow: runOnce, start, stop }
}
