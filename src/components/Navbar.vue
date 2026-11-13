<script setup>
// Top navigation, on every page. White and sticky; a bottom border appears once you scroll.
// Guest: heart, Sign in, "Sell your car". Logged in: heart + avatar menu (My listings, Liked, Profile, Log out).
// The mobile menu is opened and closed by Vue (and closes itself after you pick a page).
// The avatar menu uses Bootstrap's dropdown (keyboard support built in).
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from './AppIcon.vue'
import logoUrl from '../assets/brand/logo.svg'
import { currentUser, logout } from '../services/auth'

const route = useRoute()
const router = useRouter()

const scrolled = ref(false)
const menuOpen = ref(false)

const links = [
  { label: 'Home', to: { name: 'home' }, routes: ['home'] },
  { label: 'Browse cars', to: { name: 'cars' }, routes: ['cars', 'car-detail'] },
  { label: 'Sell a car', to: { name: 'sell' }, routes: ['sell'] },
  { label: 'About', to: { name: 'about' }, routes: ['about'] }
]

function isActive(link) {
  return link.routes.includes(route.name)
}

const initials = computed(() => {
  const name = (currentUser.value && currentUser.value.full_name) || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  const first = parts[0].charAt(0)
  const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : ''
  return (first + last).toUpperCase()
})

function onScroll() {
  scrolled.value = window.scrollY > 4
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})

// Close the mobile menu after navigating
watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false
  }
)

function handleLogout() {
  logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <header class="dd-navbar sticky-top" :class="{ scrolled }">
    <nav class="navbar navbar-expand-lg h-100 py-0" aria-label="Main">
      <div class="container h-100">
        <router-link class="navbar-brand me-lg-4 py-0" :to="{ name: 'home' }">
          <img :src="logoUrl" alt="DriveDeal" class="dd-logo" width="195" height="28" />
        </router-link>

        <button
          class="dd-toggler d-lg-none"
          type="button"
          aria-controls="mainNav"
          :aria-expanded="menuOpen ? 'true' : 'false'"
          :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
          @click="menuOpen = !menuOpen"
        >
          <AppIcon :name="menuOpen ? 'x' : 'menu'" :size="24" />
        </button>

        <div id="mainNav" class="collapse navbar-collapse" :class="{ show: menuOpen }">
          <ul class="navbar-nav mx-lg-auto gap-lg-1">
            <li v-for="link in links" :key="link.label" class="nav-item">
              <router-link
                class="nav-link dd-nav-link"
                :class="{ active: isActive(link) }"
                active-class=""
                exact-active-class=""
                :to="link.to"
                :aria-current="isActive(link) ? 'page' : undefined"
              >
                {{ link.label }}
              </router-link>
            </li>
          </ul>

          <div class="dd-nav-actions">
            <router-link
              :to="{ name: 'liked' }"
              class="dd-icon-btn"
              :class="{ active: route.name === 'liked' }"
              aria-label="Liked cars"
              title="Liked cars"
            >
              <AppIcon name="heart" :size="22" />
              <span class="d-lg-none">Liked cars</span>
            </router-link>

            <!-- Guest -->
            <template v-if="!currentUser">
              <router-link :to="{ name: 'login' }" class="dd-signin">Sign in</router-link>
              <router-link :to="{ name: 'sell' }" class="btn btn-primary dd-nav-cta">
                Sell your car
              </router-link>
            </template>

            <!-- Logged in -->
            <div v-else class="dropdown">
              <button
                class="dd-avatar-btn"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                :aria-label="`Account menu for ${currentUser.full_name}`"
              >
                <span class="dd-avatar" aria-hidden="true">{{ initials }}</span>
                <span class="dd-avatar-name d-lg-none">{{ currentUser.full_name }}</span>
                <AppIcon name="chevron-down" :size="16" />
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 pt-2 pb-1 d-none d-lg-block">
                  <span class="d-block fw-semibold text-truncate dd-menu-name">
                    {{ currentUser.full_name }}
                  </span>
                  <span class="d-block small text-muted text-truncate dd-menu-name">
                    {{ currentUser.email }}
                  </span>
                </li>
                <li class="d-none d-lg-block"><hr class="dropdown-divider" /></li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'my-listings' }">
                    <AppIcon name="list" :size="18" /> My listings
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'my-offers' }">
                    <AppIcon name="tag" :size="18" /> My offers
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'liked' }">
                    <AppIcon name="heart" :size="18" /> Liked cars
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" :to="{ name: 'profile' }">
                    <AppIcon name="user" :size="18" /> Profile
                  </router-link>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                  <button class="dropdown-item" type="button" @click="handleLogout">
                    <AppIcon name="logout" :size="18" /> Log out
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </header>
</template>

<style scoped>
.dd-navbar {
  height: var(--dd-navbar-h);
  background: var(--dd-white);
  border-bottom: 1px solid transparent;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.dd-navbar .navbar {
  --bs-navbar-color: var(--dd-body);
  --bs-navbar-hover-color: var(--dd-primary);
  --bs-navbar-active-color: var(--dd-primary);
}

.dd-navbar.scrolled {
  border-bottom-color: var(--dd-line);
  box-shadow: var(--dd-shadow-sm);
}

.dd-logo {
  display: block;
  width: auto;
  height: 28px;
}

.dd-nav-link {
  position: relative;
  padding: 0.6rem 0.9rem !important;
  border-radius: var(--dd-radius-sm);
  color: var(--dd-body);
  font-weight: 600;
}

.dd-nav-link:hover {
  color: var(--dd-primary);
}

.dd-nav-link.active {
  color: var(--dd-primary);
}

.dd-nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.dd-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  min-width: 44px;
  height: 44px;
  border-radius: 50%;
  color: var(--dd-ink);
  text-decoration: none;
}

.dd-icon-btn:hover,
.dd-icon-btn.active {
  color: var(--dd-primary);
  background: var(--dd-primary-50);
}

.dd-signin {
  padding: 0.6rem 0.5rem;
  color: var(--dd-ink);
  font-weight: 600;
  text-decoration: none;
}

.dd-signin:hover {
  color: var(--dd-primary);
}

.dd-nav-cta {
  --bs-btn-padding-y: 0.6rem;
}

.dd-avatar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 44px;
  padding: 2px 6px 2px 2px;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-pill);
  background: var(--dd-white);
  color: var(--dd-muted);
}

.dd-avatar-btn:hover,
.dd-avatar-btn[aria-expanded='true'] {
  border-color: var(--dd-primary-100);
  background: var(--dd-primary-50);
}

.dd-avatar-btn .dd-avatar {
  width: 38px;
  height: 38px;
  background: var(--dd-primary);
  color: var(--dd-white);
}

.dd-menu-name {
  max-width: 220px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.dd-toggler {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-white);
  color: var(--dd-ink);
}

/* Desktop: dot under the active link */
@media (min-width: 992px) {
  .dd-nav-link.active::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: 0.1rem;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--dd-primary);
    transform: translateX(-50%);
  }
}

/* Mobile: full-width menu panel under the bar */
@media (max-width: 991.98px) {
  .dd-logo {
    height: 24px;
  }

  .navbar-collapse {
    position: absolute;
    top: var(--dd-navbar-h);
    left: 0;
    right: 0;
    max-height: calc(100vh - var(--dd-navbar-h));
    overflow-y: auto;
    padding: 0.75rem 1rem 1.25rem;
    background: var(--dd-white);
    border-bottom: 1px solid var(--dd-line);
    box-shadow: var(--dd-shadow-lg);
  }

  .dd-nav-link {
    padding: 0.8rem 1rem !important;
  }

  .dd-nav-link.active {
    background: var(--dd-primary-50);
  }

  .dd-nav-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--dd-line);
  }

  .dd-icon-btn {
    justify-content: flex-start;
    width: 100%;
    padding: 0 1rem;
    border-radius: var(--dd-radius-sm);
    font-weight: 600;
  }

  .dd-signin {
    padding: 0.8rem 1rem;
  }

  .dd-nav-cta {
    width: 100%;
  }

  .dd-avatar-btn {
    width: 100%;
    padding: 6px 12px 6px 6px;
    border-radius: var(--dd-radius-sm);
    color: var(--dd-ink);
    font-weight: 600;
  }

  .dd-avatar-btn .dd-avatar-name {
    flex: 1;
    text-align: left;
  }

  .dropdown-menu {
    box-shadow: none;
    border: 0;
    padding-left: 0.5rem;
  }
}
</style>
