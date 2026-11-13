<script setup>
// Login page - /login
// OWNER: Yati
// LoginForm -> login.php -> setUser(), then back to the page the guard sent us from
// (route.query.redirect), or home.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginForm from '../components/LoginForm.vue'
import AuthShell from '../components/AuthShell.vue'
import AppIcon from '../components/AppIcon.vue'
import { apiPost } from '../services/api'
import { currentUser, logout, setUser } from '../services/auth'
import { safeRedirect } from '../services/accountRules'

const route = useRoute()
const router = useRouter()

const saving = ref(false)
const errorMessage = ref('')

// The guard adds ?redirect=/profile when a guest opens a protected page
const cameFromProtectedPage = computed(() => Boolean(route.query.redirect))

async function login(credentials) {
  saving.value = true
  errorMessage.value = ''
  try {
    const user = await apiPost('login.php', credentials)
    setUser({
      user_id: user.user_id,
      full_name: user.full_name,
      email: user.email,
      role: user.role
    })
    router.replace(safeRedirect(route.query.redirect))
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AuthShell
    headline="Welcome back to DriveDeal"
    :benefits="[
      'Pick up where you left off with your liked cars',
      'Message sellers about the cars you want',
      'Manage your own listings and enquiries'
    ]"
  >
    <!-- Already logged in -->
    <div v-if="currentUser" class="text-center">
      <span class="dd-icon-circle mb-3"><AppIcon name="check" :size="22" /></span>
      <h1 class="h2 mb-2">You're signed in</h1>
      <p class="text-muted">
        Signed in as <strong>{{ currentUser.full_name }}</strong> ({{ currentUser.email }}).
      </p>
      <div class="d-grid gap-2">
        <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
        <button type="button" class="btn btn-outline-secondary" @click="logout">
          Sign in as someone else
        </button>
      </div>
    </div>

    <!-- Guest -->
    <template v-else>
      <h1 class="h2 mb-1">Sign in</h1>
      <p class="text-muted mb-4">Good to see you again.</p>

      <div v-if="cameFromProtectedPage" class="alert alert-info py-2 d-flex gap-2" role="status">
        <AppIcon name="info" :size="18" class="mt-1" />
        <span>Please sign in to continue.</span>
      </div>
      <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
        {{ errorMessage }}
      </div>

      <LoginForm :saving="saving" @submit="login" />

      <p class="text-center text-muted mt-4 mb-0">
        New to DriveDeal?
        <router-link :to="{ name: 'register', query: route.query }" class="fw-semibold">
          Create an account
        </router-link>
      </p>
    </template>
  </AuthShell>
</template>
