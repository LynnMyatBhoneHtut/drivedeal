<script setup>
// Email + password form. OWNER: Yati.
// Usage: <LoginForm :saving="saving" @submit="login" />
// emit('submit', { email, password }) - LoginView calls login.php.
import { computed, reactive, ref } from 'vue'
import { emailError } from '../services/accountRules'
import AppIcon from './AppIcon.vue'

defineProps({
  saving: { type: Boolean, default: false }
})
const emit = defineEmits(['submit'])

const form = reactive({ email: '', password: '' })
const touched = reactive({})
const submitted = ref(false)
const showPassword = ref(false)

const errors = computed(() => {
  const e = {}
  const emailProblem = emailError(form.email)
  if (emailProblem) e.email = emailProblem
  if (!form.password) e.password = 'Password is required.'
  return e
})

function showError(field) {
  return (submitted.value || touched[field]) && Boolean(errors.value[field])
}

function submit() {
  submitted.value = true
  if (Object.keys(errors.value).length) {
    requestAnimationFrame(() => {
      const first = document.querySelector('.login-form .is-invalid')
      if (first) first.focus()
    })
    return
  }
  emit('submit', { email: form.email.trim().toLowerCase(), password: form.password })
}
</script>

<template>
  <form class="login-form" novalidate @submit.prevent="submit">
    <div class="mb-3">
      <label for="login-email" class="form-label">Email</label>
      <input
        id="login-email"
        v-model="form.email"
        type="email"
        class="form-control"
        :class="{ 'is-invalid': showError('email') }"
        autocomplete="email"
        placeholder="you@example.com"
        required
        @blur="touched.email = true"
      />
      <div class="invalid-feedback">{{ errors.email }}</div>
    </div>

    <div class="mb-4">
      <label for="login-password" class="form-label">Password</label>
      <div class="input-group has-validation">
        <input
          id="login-password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          class="form-control"
          :class="{ 'is-invalid': showError('password') }"
          autocomplete="current-password"
          required
          @blur="touched.password = true"
        />
        <button
          type="button"
          class="btn btn-outline-secondary"
          :aria-pressed="showPassword"
          @click="showPassword = !showPassword"
        >
          <AppIcon :name="showPassword ? 'x' : 'eye'" :size="18" />
          {{ showPassword ? 'Hide' : 'Show' }}
        </button>
        <div class="invalid-feedback">{{ errors.password }}</div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100" :disabled="saving">
      <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
      {{ saving ? 'Signing in...' : 'Sign in' }}
    </button>
  </form>
</template>
