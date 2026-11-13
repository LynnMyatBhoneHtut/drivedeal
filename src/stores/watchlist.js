// Watchlist + price-drop alerts store (Pinia). OWNER: Yati (D/HD feature).
//
// One shared place for the watchlist and the alerts, so the navbar bell, the Watch button on
// Car detail and the My watchlist page always agree - watch a car on one page and the others
// update straight away, with no props passed down or events passed up between them.
//
//   import { useWatchlistStore } from '../stores/watchlist'
//   const watchlist = useWatchlistStore()
//   watchlist.isWatching(3)      watchlist.watch(car, 45000)      watchlist.unread
//
// The store follows the logged-in user by itself: it loads on login and clears on logout.
import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { apiDelete, apiGet, apiPost, apiPut } from '../services/api'
import { currentUser } from '../services/auth'
import { priceChange } from '../services/priceChange'

export const useWatchlistStore = defineStore('watchlist', () => {
  // ---- State ----
  const cars = ref([]) // watched cars: car fields + price_when_added, target_price, added_at
  const loaded = ref(false)
  const loading = ref(false)
  const error = ref('')

  const notifications = ref([]) // newest first, at most 20
  const unread = ref(0)
  const latestId = ref(0) // newest alert id we have, so the next check only asks for newer ones
  const freshAlerts = ref([]) // alerts that arrived while the page was open (for the pop-up)
  let notificationsLoaded = false

  const userId = computed(() => (currentUser.value ? currentUser.value.user_id : null))

  // ---- Getters (computed) ----
  const count = computed(() => cars.value.length)

  /** Each watched car with its price movement worked out: { ...car, change: {amount, percent, direction} } */
  const carsWithChange = computed(() =>
    cars.value.map((car) => ({
      ...car,
      change: priceChange(car.price_when_added, car.price),
      targetReached: car.target_price !== null && car.price <= car.target_price
    }))
  )

  const droppedCars = computed(() =>
    carsWithChange.value.filter((car) => car.change.direction === 'down')
  )
  const targetsReached = computed(() => carsWithChange.value.filter((car) => car.targetReached))

  /** Total RM saved across all watched cars that got cheaper. */
  const totalSavings = computed(() =>
    droppedCars.value.reduce((sum, car) => sum + car.change.amount, 0)
  )

  function isWatching(listingId) {
    return cars.value.some((car) => car.listing_id === Number(listingId))
  }

  function watchFor(listingId) {
    return cars.value.find((car) => car.listing_id === Number(listingId)) || null
  }

  // ---- Actions ----
  async function loadWatchlist() {
    if (!userId.value) return
    loading.value = true
    error.value = ''
    try {
      const data = await apiGet('watchlist.php', { user_id: userId.value })
      cars.value = data.cars || []
      loaded.value = true
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  /** Start watching (or change the target). `car` needs listing_id; target can be null. */
  async function watch_(car, targetPrice = null) {
    await apiPost('watchlist.php', {
      user_id: userId.value,
      listing_id: car.listing_id,
      target_price: targetPrice
    })
    await loadWatchlist() // fetch the full row (with photo, seller...) for the watchlist page
  }

  async function setTarget(listingId, targetPrice) {
    const result = await apiPut('watchlist.php', {
      user_id: userId.value,
      listing_id: listingId,
      target_price: targetPrice
    })
    const car = watchFor(listingId)
    if (car) car.target_price = result.target_price
  }

  async function unwatch(listingId) {
    // Optimistic: remove now, put it back if the server says no
    const before = cars.value
    cars.value = cars.value.filter((car) => car.listing_id !== Number(listingId))
    try {
      await apiDelete('watchlist.php', { user_id: userId.value, listing_id: Number(listingId) })
    } catch (e) {
      cars.value = before
      throw e
    }
  }

  /**
   * Ask the server for alerts. The first call gets the latest 20; after that only ones newer
   * than latestId (what the bell's polling calls every few seconds).
   */
  async function checkNotifications() {
    if (!userId.value) return
    const firstLoad = !notificationsLoaded
    const data = await apiGet('notifications.php', {
      user_id: userId.value,
      after_id: firstLoad ? undefined : latestId.value
    })
    if (userId.value === null) return // logged out while waiting

    unread.value = data.unread
    if (firstLoad) {
      notifications.value = data.notifications
    } else if (data.notifications.length) {
      notifications.value = [...data.notifications, ...notifications.value].slice(0, 20)
      freshAlerts.value = [...data.notifications, ...freshAlerts.value]
      // A watched car just got cheaper -> refresh the watchlist so prices on screen are current
      await loadWatchlist()
    }
    latestId.value = Math.max(latestId.value, data.latest_id || 0)
    notificationsLoaded = true
  }

  async function markRead(notificationId) {
    const item = notifications.value.find((n) => n.notification_id === notificationId)
    if (!item || item.is_read) return
    item.is_read = true
    unread.value = Math.max(0, unread.value - 1)
    const result = await apiPut('notifications.php', {
      user_id: userId.value,
      notification_id: notificationId
    })
    unread.value = result.unread
  }

  async function markAllRead() {
    notifications.value.forEach((n) => (n.is_read = true))
    unread.value = 0
    const result = await apiPut('notifications.php', { user_id: userId.value, all: true })
    unread.value = result.unread
  }

  function dismissFresh(notificationId) {
    freshAlerts.value = freshAlerts.value.filter((n) => n.notification_id !== notificationId)
  }

  function reset() {
    cars.value = []
    loaded.value = false
    error.value = ''
    notifications.value = []
    unread.value = 0
    latestId.value = 0
    freshAlerts.value = []
    notificationsLoaded = false
  }

  // Follow the logged-in user: load on login, clear on logout or when someone else logs in
  watch(
    userId,
    (id) => {
      reset()
      if (id) loadWatchlist()
    },
    { immediate: true }
  )

  return {
    cars,
    loaded,
    loading,
    error,
    notifications,
    unread,
    latestId,
    freshAlerts,
    count,
    carsWithChange,
    droppedCars,
    targetsReached,
    totalSavings,
    isWatching,
    watchFor,
    loadWatchlist,
    watch: watch_,
    setTarget,
    unwatch,
    checkNotifications,
    markRead,
    markAllRead,
    dismissFresh,
    reset
  }
})
