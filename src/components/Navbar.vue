<script setup>
// Top navigation, on every page. OWNER: Lai (look and layout).
// Links work now so everyone can click between pages while building.
// Yati: the guest vs logged-in links below already use v-if on currentUser.
import { useRouter } from 'vue-router'
import { currentUser, logout } from '../services/auth'
import NotificationBell from './NotificationBell.vue' // Yati - price alerts (D/HD)

const router = useRouter()

function handleLogout() {
  logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <router-link class="navbar-brand" :to="{ name: 'home' }">DriveDeal</router-link>

      <!-- Yati - price-drop alerts bell. Outside the collapse so it shows on phones too;
           order-lg-last moves it to the far right on wider screens. -->
      <NotificationBell v-if="currentUser" class="ms-auto me-2 ms-lg-2 me-lg-0 order-lg-last" />
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNav"
        aria-controls="mainNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div id="mainNav" class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'cars' }">Browse cars</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'sell' }">Sell a car</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'about' }">About</router-link>
          </li>
        </ul>

        <!-- Guest -->
        <ul v-if="!currentUser" class="navbar-nav">
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'login' }">Log in</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'register' }">Register</router-link>
          </li>
        </ul>

        <!-- Logged in -->
        <ul v-else class="navbar-nav">
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'my-listings' }">My listings</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'liked' }">Liked</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'watchlist' }">Watchlist</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" :to="{ name: 'profile' }">
              {{ currentUser.full_name }}
            </router-link>
          </li>
          <li class="nav-item">
            <button class="btn btn-link nav-link" type="button" @click="handleLogout">
              Log out
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>
