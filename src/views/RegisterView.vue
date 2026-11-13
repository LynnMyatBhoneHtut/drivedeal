<script setup>
// Register page - /register
// OWNER: Yati
// RegisterForm (with validation) -> register.php -> setUser(), so the new user is logged in
// straight away, then on to the page they wanted (route.query.redirect) or home.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import RegisterForm from '../components/RegisterForm.vue'
import AuthShell from '../components/AuthShell.vue'
import AppIcon from '../components/AppIcon.vue'
import { apiPost } from '../services/api'
import { currentUser, setUser } from '../services/auth'
import { fieldForServerError, safeRedirect } from '../services/accountRules'

const route = useRoute()
const router = useRouter()

const saving = ref(false)
const errorMessage = ref('')
// Errors about one field (e.g. "email already exists") show under that field instead
const generalError = computed(() =>
  fieldForServerError(errorMessage.value) ? '' : errorMessage.value
)

async function register(details) {
  saving.value = true
  errorMessage.value = ''
  try {
    const user = await apiPost('register.php', details)
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
    headline="Join DriveDeal in under a minute"
    :benefits="[
      'Free to list your car with photos',
      'Contact sellers directly, no middlemen',
      'Save cars you like and compare them'
    ]"
  >
    <div v-if="currentUser" class="text-center">
      <span class="dd-icon-circle mb-3"><AppIcon name="check" :size="22" /></span>
      <h1 class="h2 mb-2">You already have an account</h1>
      <p class="text-muted">
        You're signed in as <strong>{{ currentUser.full_name }}</strong
        >.
      </p>
      <router-link :to="{ name: 'profile' }" class="btn btn-primary w-100">
        Go to your profile
      </router-link>
    </div>

    <template v-else>
      <h1 class="h2 mb-1">Create an account</h1>
      <p class="text-muted mb-4">Like cars, contact sellers and list your own car for sale.</p>

      <div v-if="generalError" class="alert alert-danger py-2" role="alert">
        {{ generalError }}
      </div>

      <RegisterForm :saving="saving" :server-error="errorMessage" @submit="register" />

      <p class="text-center text-muted mt-4 mb-0">
        Already have an account?
        <router-link :to="{ name: 'login', query: route.query }" class="fw-semibold">
          Sign in
        </router-link>
      </p>
    </template>
  </AuthShell>
</template>
