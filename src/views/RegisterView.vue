<script setup>
// Register page - /register
// OWNER: Yati
// RegisterForm (with validation) -> register.php -> setUser(), so the new user is logged in
// straight away, then on to the page they wanted (route.query.redirect) or home.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import RegisterForm from '../components/RegisterForm.vue'
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
  <section class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <div v-if="currentUser" class="alert alert-info text-center" role="status">
          You already have an account and are logged in as
          <strong>{{ currentUser.full_name }}</strong
          >.
          <router-link :to="{ name: 'profile' }" class="alert-link">Go to your profile</router-link>
        </div>

        <div v-else class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h3 mb-1">Create an account</h1>
            <p class="text-muted mb-4">
              Like cars, contact sellers and list your own car for sale.
            </p>

            <div v-if="generalError" class="alert alert-danger py-2" role="alert">
              {{ generalError }}
            </div>

            <RegisterForm :saving="saving" :server-error="errorMessage" @submit="register" />

            <p class="text-center mt-4 mb-0">
              Already have an account?
              <router-link :to="{ name: 'login', query: route.query }">Log in</router-link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
