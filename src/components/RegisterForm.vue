<script setup>
// Sign-up form (full_name, email, password, phone). OWNER: Yati.
// Usage: <RegisterForm :saving="saving" :server-error="error" @submit="register" />
// emit('submit', { full_name, email, password, phone }) - RegisterView calls register.php.
// Errors show after a field is left (blur) or after pressing the button.
import { computed, reactive, ref, watch } from 'vue'
import {
  emailError,
  fieldForServerError,
  fullNameError,
  passwordError,
  passwordStrength,
  phoneError
} from '../services/accountRules'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  saving: { type: Boolean, default: false },
  serverError: { type: String, default: '' } // message from register.php, e.g. email taken
})
const emit = defineEmits(['submit'])

const form = reactive({ full_name: '', email: '', phone: '', password: '', confirm: '' })
const touched = reactive({})
const submitted = ref(false)
const showPassword = ref(false)

const errors = computed(() => {
  const e = {
    full_name: fullNameError(form.full_name),
    email: emailError(form.email),
    phone: phoneError(form.phone),
    password: passwordError(form.password),
    confirm: form.confirm === form.password ? '' : 'Passwords do not match.'
  }
  if (!form.confirm) e.confirm = 'Please type your password again.'
  for (const key of Object.keys(e)) if (!e[key]) delete e[key]
  return e
})

// A server error (e.g. "email already exists") is shown under the field it is about,
// until the user changes that field.
const serverField = computed(() => fieldForServerError(props.serverError))
const valueAtServerError = ref('')
watch(
  () => props.serverError,
  () => {
    valueAtServerError.value = serverField.value ? form[serverField.value] : ''
  }
)

function hasServerError(field) {
  return (
    Boolean(props.serverError) &&
    serverField.value === field &&
    form[field] === valueAtServerError.value
  )
}

function showError(field) {
  if (hasServerError(field)) return true
  return (submitted.value || touched[field]) && Boolean(errors.value[field])
}

function errorText(field) {
  return errors.value[field] || (hasServerError(field) ? props.serverError : '')
}

function fieldClass(field) {
  return { 'is-invalid': showError(field), 'is-valid': touched[field] && !showError(field) }
}

// Password strength meter
const STRENGTH = [
  { label: 'Too short', className: 'bg-danger', width: '10%' },
  { label: 'Weak', className: 'bg-danger', width: '25%' },
  { label: 'Fair', className: 'bg-warning', width: '50%' },
  { label: 'Good', className: 'bg-primary', width: '75%' },
  { label: 'Strong', className: 'bg-success', width: '100%' }
]
const strength = computed(() => STRENGTH[passwordStrength(form.password)])

function submit() {
  submitted.value = true
  if (Object.keys(errors.value).length) {
    requestAnimationFrame(() => {
      const first = document.querySelector('.register-form .is-invalid')
      if (first) first.focus()
    })
    return
  }
  emit('submit', {
    full_name: form.full_name.trim(),
    email: form.email.trim().toLowerCase(),
    phone: form.phone.trim(),
    password: form.password
  })
}
</script>

<template>
  <form class="register-form" novalidate @submit.prevent="submit">
    <div class="mb-3">
      <label for="reg-name" class="form-label">Full name <span class="text-danger">*</span></label>
      <input
        id="reg-name"
        v-model="form.full_name"
        type="text"
        class="form-control"
        :class="fieldClass('full_name')"
        maxlength="100"
        autocomplete="name"
        placeholder="e.g. Sarah Lim"
        required
        @blur="touched.full_name = true"
      />
      <div class="invalid-feedback">{{ errorText('full_name') }}</div>
    </div>

    <div class="mb-3">
      <label for="reg-email" class="form-label">Email <span class="text-danger">*</span></label>
      <input
        id="reg-email"
        v-model="form.email"
        type="email"
        class="form-control"
        :class="fieldClass('email')"
        maxlength="150"
        autocomplete="email"
        placeholder="you@example.com"
        required
        @blur="touched.email = true"
      />
      <div class="invalid-feedback">{{ errorText('email') }}</div>
    </div>

    <div class="mb-3">
      <label for="reg-phone" class="form-label">
        Phone <span class="text-muted small">(optional)</span>
      </label>
      <input
        id="reg-phone"
        v-model="form.phone"
        type="tel"
        class="form-control"
        :class="fieldClass('phone')"
        maxlength="20"
        autocomplete="tel"
        placeholder="e.g. 012-345 6789"
        @blur="touched.phone = true"
      />
      <div class="invalid-feedback">{{ errorText('phone') }}</div>
      <div class="form-text">Buyers see this on your listings so they can call you.</div>
    </div>

    <div class="mb-3">
      <label for="reg-password" class="form-label"
        >Password <span class="text-danger">*</span></label
      >
      <div class="input-group has-validation">
        <input
          id="reg-password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          class="form-control"
          :class="fieldClass('password')"
          maxlength="72"
          autocomplete="new-password"
          aria-describedby="reg-password-help"
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
        <div class="invalid-feedback">{{ errorText('password') }}</div>
      </div>
      <div v-if="form.password" class="mt-2" aria-live="polite">
        <div class="progress" style="height: 6px" role="presentation">
          <div
            class="progress-bar"
            :class="strength.className"
            :style="{ width: strength.width }"
          ></div>
        </div>
        <div class="small text-muted mt-1">Strength: {{ strength.label }}</div>
      </div>
      <div id="reg-password-help" class="form-text">
        At least 8 characters, with a letter and a number.
      </div>
    </div>

    <div class="mb-4">
      <label for="reg-confirm" class="form-label">
        Confirm password <span class="text-danger">*</span>
      </label>
      <input
        id="reg-confirm"
        v-model="form.confirm"
        :type="showPassword ? 'text' : 'password'"
        class="form-control"
        :class="fieldClass('confirm')"
        maxlength="72"
        autocomplete="new-password"
        required
        @blur="touched.confirm = true"
      />
      <div class="invalid-feedback">{{ errorText('confirm') }}</div>
    </div>

    <button type="submit" class="btn btn-primary w-100" :disabled="saving">
      <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
      {{ saving ? 'Creating account...' : 'Create account' }}
    </button>
  </form>
</template>
