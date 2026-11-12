<script setup>
// Login page - /login
// OWNER: Yati
// LoginForm -> login.php -> setUser(), then back to the page the guard sent us from
// (route.query.redirect), or home.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginForm from '../components/LoginForm.vue'
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
  <section class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <!-- Already logged in -->
        <div v-if="currentUser" class="card shadow-sm">
          <div class="card-body p-4 text-center">
            <h1 class="h4 mb-3">You're logged in</h1>
            <p class="text-muted">
              Signed in as <strong>{{ currentUser.full_name }}</strong> ({{ currentUser.email }}).
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
              <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
              <button type="button" class="btn btn-outline-secondary" @click="logout">
                Log in as someone else
              </button>
            </div>
          </div>
        </div>

        <!-- Guest -->
        <div v-else class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h3 mb-1">Log in</h1>
            <p class="text-muted mb-4">Welcome back to DriveDeal.</p>

            <div v-if="cameFromProtectedPage" class="alert alert-info py-2" role="status">
              Please log in to continue.
            </div>
            <div v-if="errorMessage" class="alert alert-danger py-2" role="alert">
              {{ errorMessage }}
            </div>

            <LoginForm :saving="saving" @submit="login" />

            <p class="text-center mt-4 mb-0">
              New to DriveDeal?
              <router-link :to="{ name: 'register', query: route.query }">
                Create an account
              </router-link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
