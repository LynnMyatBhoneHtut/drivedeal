<script setup>
// Bell in the navbar with the unread count and a dropdown of price-drop alerts.
// OWNER: Yati (D/HD feature). Lives in Navbar.vue, only for logged-in users.
//
// - usePolling asks notifications.php for NEW alerts every 15 s (paused while the tab is hidden).
// - New alerts also pop up as a small toast in the corner (<Teleport> to <body>, so the navbar's
//   layout and overflow can't clip it) and slide in with <TransitionGroup>.
// - The browser tab title shows the unread count, e.g. "(2) DriveDeal".
import { computed, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useWatchlistStore } from '../stores/watchlist'
import { usePolling } from '../composables/usePolling'
import { currentUser } from '../services/auth'
import { imageUrl } from '../services/api'
import { formatRM, timeAgo } from '../services/priceChange'

const POLL_EVERY_MS = 15000
const TOAST_MS = 7000

const watchlist = useWatchlistStore()
const router = useRouter()

usePolling(() => watchlist.checkNotifications(), {
  interval: POLL_EVERY_MS,
  enabled: () => Boolean(currentUser.value)
})

const badgeText = computed(() => (watchlist.unread > 9 ? '9+' : String(watchlist.unread)))
const bellLabel = computed(() =>
  watchlist.unread ? `Price alerts, ${watchlist.unread} unread` : 'Price alerts, no unread alerts'
)

// Unread count in the tab title, so it shows even when looking at another tab
const baseTitle = document.title.replace(/^\(\d+\+?\)\s*/, '')
watch(
  () => watchlist.unread,
  (n) => {
    document.title = n ? `(${n > 9 ? '9+' : n}) ${baseTitle}` : baseTitle
  },
  { immediate: true }
)

// Logged out -> the bell disappears, so put the normal title back
onUnmounted(() => {
  document.title = baseTitle
})

// Hide each pop-up toast after a few seconds
watch(
  () => watchlist.freshAlerts.map((n) => n.notification_id),
  (ids, oldIds = []) => {
    ids
      .filter((id) => !oldIds.includes(id))
      .forEach((id) => setTimeout(() => watchlist.dismissFresh(id), TOAST_MS))
  }
)

async function openAlert(alert) {
  watchlist.dismissFresh(alert.notification_id)
  await router.push({ name: 'car-detail', params: { id: String(alert.listing_id) } })
  watchlist.markRead(alert.notification_id).catch(() => {})
}

function alertIcon(alert) {
  return alert.type === 'target_reached' ? '\u{1F3AF}' : '↓'
}
</script>

<template>
  <div class="dropdown notification-bell">
    <button
      type="button"
      class="bell-btn position-relative"
      data-bs-toggle="dropdown"
      data-bs-auto-close="outside"
      aria-expanded="false"
      :aria-label="bellLabel"
      :title="bellLabel"
    >
      <!-- Line-style bell drawn to match AppIcon (which has no bell) -->
      <svg
        class="bell-icon"
        :class="{ ring: watchlist.unread }"
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15z" />
        <path d="M10 20.5a2 2 0 0 0 4 0" />
      </svg>
      <Transition name="pop">
        <span v-if="watchlist.unread" :key="badgeText" class="bell-badge">
          {{ badgeText }}
        </span>
      </Transition>
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow p-0 alert-menu">
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <strong>Price alerts</strong>
        <button
          v-if="watchlist.unread"
          type="button"
          class="btn btn-link btn-sm p-0"
          @click="watchlist.markAllRead()"
        >
          Mark all as read
        </button>
      </div>

      <p v-if="!watchlist.notifications.length" class="text-muted small px-3 py-4 mb-0 text-center">
        No alerts yet. Watch a car and we'll tell you when its price drops.
      </p>

      <TransitionGroup v-else tag="ul" name="alert-list" class="list-unstyled mb-0 alert-scroll">
        <li v-for="alert in watchlist.notifications" :key="alert.notification_id">
          <button
            type="button"
            class="dropdown-item d-flex gap-2 py-2 text-wrap"
            :class="{ unread: !alert.is_read }"
            @click="openAlert(alert)"
          >
            <img
              v-if="alert.image_path"
              :src="imageUrl(alert.image_path)"
              alt=""
              class="rounded flex-shrink-0 alert-thumb"
            />
            <span class="flex-grow-1">
              <span class="d-block small">
                <span class="me-1" aria-hidden="true">{{ alertIcon(alert) }}</span>
                {{ alert.message }}
              </span>
              <span class="d-block text-muted alert-time">{{ timeAgo(alert.created_at) }}</span>
            </span>
            <span v-if="!alert.is_read" class="unread-dot flex-shrink-0" aria-label="Unread"></span>
          </button>
        </li>
      </TransitionGroup>

      <div class="border-top px-3 py-2 text-center">
        <router-link :to="{ name: 'watchlist' }" class="small">Open my watchlist</router-link>
      </div>
    </div>

    <!-- Pop-up toasts for alerts that arrive while the page is open -->
    <Teleport to="body">
      <div class="toast-stack" aria-live="polite" aria-atomic="false">
        <TransitionGroup name="toast">
          <div
            v-for="alert in watchlist.freshAlerts.slice(0, 3)"
            :key="alert.notification_id"
            class="toast show shadow"
            role="status"
          >
            <div class="toast-header">
              <strong class="me-auto text-success">
                {{ alert.type === 'target_reached' ? 'Target price reached!' : 'Price drop!' }}
              </strong>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="watchlist.dismissFresh(alert.notification_id)"
              ></button>
            </div>
            <button
              type="button"
              class="toast-body btn btn-link text-start text-reset text-decoration-none w-100"
              @click="openAlert(alert)"
            >
              {{ alert.make }} {{ alert.model }}:
              <s class="text-muted">{{ formatRM(alert.old_price) }}</s>
              &rarr; <strong>{{ formatRM(alert.new_price) }}</strong>
            </button>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.bell-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: var(--dd-ink);
}
.bell-btn:hover,
.bell-btn[aria-expanded='true'] {
  color: var(--dd-primary);
  background: var(--dd-primary-50);
}
.bell-badge {
  position: absolute;
  top: 4px;
  right: 2px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9px;
  background: var(--dd-danger);
  color: var(--dd-white);
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 18px;
  text-align: center;
}
.bell-icon.ring {
  animation: ring 1.2s ease-in-out 1;
  transform-origin: top center;
}
@keyframes ring {
  0%,
  100% {
    transform: rotate(0);
  }
  20% {
    transform: rotate(15deg);
  }
  40% {
    transform: rotate(-12deg);
  }
  60% {
    transform: rotate(8deg);
  }
  80% {
    transform: rotate(-4deg);
  }
}

.alert-menu {
  width: min(360px, 92vw);
}
.alert-scroll {
  max-height: 360px;
  overflow-y: auto;
}
.alert-thumb {
  width: 48px;
  height: 36px;
  object-fit: cover;
}
.alert-time {
  font-size: 0.75rem;
}
.dropdown-item.unread {
  background-color: var(--dd-primary-50);
}
.unread-dot {
  width: 8px;
  height: 8px;
  margin-top: 6px;
  border-radius: 50%;
  background: var(--dd-primary);
}

/* Badge pops when the count changes */
.pop-enter-active {
  transition: transform 0.25s cubic-bezier(0.3, 1.6, 0.6, 1);
}
.pop-enter-from {
  transform: scale(0);
}

/* New alerts slide into the list */
.alert-list-enter-active,
.alert-list-leave-active {
  transition: all 0.3s ease;
}
.alert-list-enter-from,
.alert-list-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

.toast-stack {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  z-index: 1090;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: min(320px, calc(100vw - 2rem));
}
.toast-enter-active,
.toast-leave-active {
  transition: all 0.35s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(40px);
}
</style>
