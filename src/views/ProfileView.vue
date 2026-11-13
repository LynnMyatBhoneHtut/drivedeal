<script setup>
// Profile page - /profile
// OWNER: Yati
// Shows the logged-in user (users.php?id=) and lets them edit name, email, phone and
// password. Saves with users.php PUT, then setUser() so the Navbar name updates by itself.
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { apiGet, apiPut } from '../services/api'
import AppIcon from '../components/AppIcon.vue'
import PageHeader from '../components/PageHeader.vue'
import { currentUser, logout, setUser } from '../services/auth'
import {
  emailError,
  fieldForServerError,
  fullNameError,
  passwordError,
  phoneError
} from '../services/accountRules'

const router = useRouter()

const profile = ref(null) // what is saved in the database
const loading = ref(false)
const loadError = ref('')

const editing = ref(false)
const changePassword = ref(false)
const form = reactive({
  full_name: '',
  email: '',
  phone: '',
  current_password: '',
  new_password: '',
  confirm: ''
})
const touched = reactive({})
const submitted = ref(false)
const saving = ref(false)
const saveError = ref('')
const successMessage = ref('')

const initials = computed(() => {
  if (!profile.value) return ''
  return profile.value.full_name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join('')
})

const memberSince = computed(() => {
  if (!profile.value || !profile.value.created_at) return ''
  const date = new Date(profile.value.created_at.replace(' ', 'T'))
  return date.toLocaleDateString('en-MY', { month: 'long', year: 'numeric' })
})

async function load(userId) {
  profile.value = null
  if (!userId) return
  loading.value = true
  loadError.value = ''
  try {
    profile.value = await apiGet('users.php', { id: userId })
  } catch (e) {
    loadError.value = e.message
  } finally {
    loading.value = false
  }
}

watch(() => currentUser.value && currentUser.value.user_id, load, { immediate: true })

// ---- Edit form ----
function startEditing() {
  form.full_name = profile.value.full_name
  form.email = profile.value.email
  form.phone = profile.value.phone || ''
  form.current_password = ''
  form.new_password = ''
  form.confirm = ''
  for (const key of Object.keys(touched)) delete touched[key]
  submitted.value = false
  changePassword.value = false
  saveError.value = ''
  successMessage.value = ''
  editing.value = true
}

function cancelEditing() {
  editing.value = false
  saveError.value = ''
}

const errors = computed(() => {
  const e = {
    full_name: fullNameError(form.full_name),
    email: emailError(form.email),
    phone: phoneError(form.phone)
  }
  if (changePassword.value) {
    e.current_password = form.current_password ? '' : 'Enter your current password.'
    e.new_password = passwordError(form.new_password)
    e.confirm = form.confirm === form.new_password ? '' : 'Passwords do not match.'
  }
  for (const key of Object.keys(e)) if (!e[key]) delete e[key]
  return e
})

const hasChanges = computed(() => {
  if (!profile.value) return false
  return (
    changePassword.value ||
    form.full_name.trim() !== profile.value.full_name ||
    form.email.trim().toLowerCase() !== profile.value.email ||
    form.phone.trim() !== (profile.value.phone || '')
  )
})

// The field a server error belongs to, e.g. "Another account already uses this email."
const serverField = computed(() => fieldForServerError(saveError.value))

function showError(field) {
  if (serverField.value === field) return true
  return (submitted.value || touched[field]) && Boolean(errors.value[field])
}

function errorText(field) {
  return errors.value[field] || (serverField.value === field ? saveError.value : '')
}

async function save() {
  submitted.value = true
  saveError.value = ''
  if (Object.keys(errors.value).length) {
    requestAnimationFrame(() => {
      const first = document.querySelector('.profile-form .is-invalid')
      if (first) first.focus()
    })
    return
  }

  const body = {
    user_id: currentUser.value.user_id,
    full_name: form.full_name.trim(),
    email: form.email.trim().toLowerCase(),
    phone: form.phone.trim()
  }
  if (changePassword.value) {
    body.current_password = form.current_password
    body.new_password = form.new_password
  }

  saving.value = true
  try {
    const updated = await apiPut('users.php', body)
    profile.value = updated
    setUser({
      user_id: updated.user_id,
      full_name: updated.full_name,
      email: updated.email,
      role: updated.role
    })
    editing.value = false
    successMessage.value = changePassword.value
      ? 'Your profile and password have been updated.'
      : 'Your profile has been updated.'
  } catch (e) {
    saveError.value = e.message
  } finally {
    saving.value = false
  }
}

// Typing in a field clears the server error about it
watch(
  () => [form.email, form.current_password, form.full_name, form.phone],
  () => {
    if (serverField.value) saveError.value = ''
  }
)

function handleLogout() {
  logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div>
    <PageHeader title="My profile">
      <template v-if="currentUser" #subtitle>Your account details and shortcuts.</template>
    </PageHeader>

    <section class="container py-4 py-lg-5">
      <div v-if="!currentUser" class="dd-empty">
        <span class="dd-icon-circle"><AppIcon name="user" :size="28" /></span>
        <h2>Please sign in to see your profile</h2>
        <router-link
          :to="{ name: 'login', query: { redirect: '/profile' } }"
          class="btn btn-primary mt-2"
        >
          Sign in
        </router-link>
      </div>

      <template v-else>
        <div v-if="loading" class="row g-4" aria-busy="true">
          <div class="col-12 col-lg-4"><div class="dd-skeleton" style="height: 340px"></div></div>
          <div class="col-12 col-lg-8"><div class="dd-skeleton" style="height: 340px"></div></div>
          <span class="visually-hidden" role="status">Loading your profile...</span>
        </div>

        <div
          v-else-if="loadError"
          class="alert alert-danger d-flex flex-wrap align-items-center gap-3"
          role="alert"
        >
          <span>{{ loadError }}</span>
          <button
            type="button"
            class="btn btn-sm btn-outline-danger"
            @click="load(currentUser.user_id)"
          >
            Try again
          </button>
        </div>

        <div v-else-if="profile" class="row g-4">
          <!-- Summary card -->
          <div class="col-12 col-lg-4">
            <div class="dd-card dd-card-body text-center h-100">
              <span class="dd-avatar profile-avatar mb-3" aria-hidden="true">{{ initials }}</span>
              <h2 class="h3 mb-1">{{ profile.full_name }}</h2>
              <p class="text-muted mb-2 text-break">{{ profile.email }}</p>
              <span v-if="profile.role === 'admin'" class="dd-status dd-status-available mb-2">
                Admin
              </span>
              <p v-if="memberSince" class="small text-muted mb-4">
                <AppIcon name="calendar" :size="16" /> Member since {{ memberSince }}
              </p>
              <div class="d-grid gap-2">
                <router-link :to="{ name: 'my-listings' }" class="btn btn-outline-primary">
                  <AppIcon name="list" :size="18" /> My listings
                </router-link>
                <router-link :to="{ name: 'liked' }" class="btn btn-outline-primary">
                  <AppIcon name="heart" :size="18" /> Liked cars
                </router-link>
                <button type="button" class="btn btn-ghost" @click="handleLogout">
                  <AppIcon name="logout" :size="18" /> Log out
                </button>
              </div>
            </div>
          </div>

          <!-- Details / edit -->
          <div class="col-12 col-lg-8">
            <div class="dd-card dd-card-body h-100">
              <div
                v-if="successMessage"
                class="alert alert-success alert-dismissible"
                role="status"
              >
                {{ successMessage }}
                <button
                  type="button"
                  class="btn-close"
                  aria-label="Close"
                  @click="successMessage = ''"
                ></button>
              </div>

              <!-- View mode -->
              <template v-if="!editing">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h2 class="h3 mb-0">Account details</h2>
                  <button type="button" class="btn btn-primary btn-sm" @click="startEditing">
                    <AppIcon name="edit" :size="16" /> Edit profile
                  </button>
                </div>
                <dl class="detail-list mb-0">
                  <div class="detail-row">
                    <span class="dd-icon-circle"><AppIcon name="user" /></span>
                    <div>
                      <dt>Full name</dt>
                      <dd>{{ profile.full_name }}</dd>
                    </div>
                  </div>
                  <div class="detail-row">
                    <span class="dd-icon-circle"><AppIcon name="mail" /></span>
                    <div>
                      <dt>Email</dt>
                      <dd class="text-break">{{ profile.email }}</dd>
                    </div>
                  </div>
                  <div class="detail-row">
                    <span class="dd-icon-circle"><AppIcon name="phone" /></span>
                    <div>
                      <dt>Phone</dt>
                      <dd>
                        {{ profile.phone || 'Not added' }}
                        <span v-if="!profile.phone" class="d-block small text-muted">
                          Add a phone number so buyers can call you about your cars.
                        </span>
                      </dd>
                    </div>
                  </div>
                  <div class="detail-row">
                    <span class="dd-icon-circle"><AppIcon name="shield-check" /></span>
                    <div>
                      <dt>Password</dt>
                      <dd>&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</dd>
                    </div>
                  </div>
                </dl>
              </template>

              <!-- Edit mode -->
              <form v-else class="profile-form" novalidate @submit.prevent="save">
                <h2 class="h3 mb-3">Edit profile</h2>

                <div class="mb-3">
                  <label for="pf-name" class="form-label">Full name</label>
                  <input
                    id="pf-name"
                    v-model="form.full_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': showError('full_name') }"
                    maxlength="100"
                    autocomplete="name"
                    @blur="touched.full_name = true"
                  />
                  <div class="invalid-feedback">{{ errorText('full_name') }}</div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-12 col-md-7">
                    <label for="pf-email" class="form-label">Email</label>
                    <input
                      id="pf-email"
                      v-model="form.email"
                      type="email"
                      class="form-control"
                      :class="{ 'is-invalid': showError('email') }"
                      maxlength="150"
                      autocomplete="email"
                      @blur="touched.email = true"
                    />
                    <div class="invalid-feedback">{{ errorText('email') }}</div>
                  </div>
                  <div class="col-12 col-md-5">
                    <label for="pf-phone" class="form-label">
                      Phone <span class="text-muted small">(optional)</span>
                    </label>
                    <input
                      id="pf-phone"
                      v-model="form.phone"
                      type="tel"
                      class="form-control"
                      :class="{ 'is-invalid': showError('phone') }"
                      maxlength="20"
                      autocomplete="tel"
                      placeholder="e.g. 012-345 6789"
                      @blur="touched.phone = true"
                    />
                    <div class="invalid-feedback">{{ errorText('phone') }}</div>
                  </div>
                </div>

                <div class="form-check form-switch mb-3">
                  <input
                    id="pf-change-password"
                    v-model="changePassword"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                  />
                  <label class="form-check-label" for="pf-change-password">
                    Change my password
                  </label>
                </div>

                <div v-if="changePassword" class="password-box mb-3">
                  <div class="mb-3">
                    <label for="pf-current" class="form-label">Current password</label>
                    <input
                      id="pf-current"
                      v-model="form.current_password"
                      type="password"
                      class="form-control"
                      :class="{ 'is-invalid': showError('current_password') }"
                      autocomplete="current-password"
                      @blur="touched.current_password = true"
                    />
                    <div class="invalid-feedback">{{ errorText('current_password') }}</div>
                  </div>
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label for="pf-new" class="form-label">New password</label>
                      <input
                        id="pf-new"
                        v-model="form.new_password"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': showError('new_password') }"
                        maxlength="72"
                        autocomplete="new-password"
                        @blur="touched.new_password = true"
                      />
                      <div class="invalid-feedback">{{ errorText('new_password') }}</div>
                      <div class="form-text">
                        At least 8 characters, with a letter and a number.
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <label for="pf-confirm" class="form-label">Confirm new password</label>
                      <input
                        id="pf-confirm"
                        v-model="form.confirm"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': showError('confirm') }"
                        maxlength="72"
                        autocomplete="new-password"
                        @blur="touched.confirm = true"
                      />
                      <div class="invalid-feedback">{{ errorText('confirm') }}</div>
                    </div>
                  </div>
                </div>

                <div v-if="saveError && !serverField" class="alert alert-danger py-2" role="alert">
                  {{ saveError }}
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <button type="submit" class="btn btn-primary" :disabled="saving || !hasChanges">
                    <span
                      v-if="saving"
                      class="spinner-border spinner-border-sm me-2"
                      aria-hidden="true"
                    ></span>
                    {{ saving ? 'Saving...' : 'Save changes' }}
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="saving"
                    @click="cancelEditing"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.profile-avatar {
  width: 88px;
  height: 88px;
  background: var(--dd-gradient);
  color: var(--dd-white);
  font-size: 1.75rem;
}

.detail-list {
  display: grid;
  gap: 1rem;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--dd-line);
}

.detail-row:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.detail-row dt {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--dd-muted);
}

.detail-row dd {
  margin: 0;
  font-weight: 600;
  color: var(--dd-ink);
}

.password-box {
  padding: 1rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
}
</style>
