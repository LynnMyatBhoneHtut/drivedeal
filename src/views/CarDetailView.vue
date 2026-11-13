<script setup>
import { computed, ref, watch } from 'vue'
import { apiGet, apiPost } from '../services/api'
import { currentUser } from '../services/auth'
import ImageGallery from '../components/ImageGallery.vue'
import LikeButton from '../components/LikeButton.vue'
import AppIcon from '../components/AppIcon.vue'

const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

const car = ref(null)
const loading = ref(true)
const loadError = ref('')

// Retrieve vehicle details from Feenix MySQL
async function loadCar(id) {
  loading.value = true
  loadError.value = ''
  car.value = null

  try {
    const data = await apiGet('listings.php', { id })

    if (!data || Number(data.listing_id) !== Number(id)) {
      throw new Error('Invalid vehicle data received.')
    }

    car.value = data
  } catch (error) {
    loadError.value = error.message || 'Unable to load vehicle details.'
  } finally {
    loading.value = false
  }
}

function formatNumber(value) {
  return Number(value).toLocaleString('en-MY')
}

const message = ref('')
const isSending = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
// Reload when the selected vehicle changes
watch(
  () => props.id,
  (id) => {
    message.value = ''
    successMessage.value = ''
    errorMessage.value = ''
    loadCar(id)
  },
  { immediate: true }
)

async function sendInquiry() {
  successMessage.value = ''
  errorMessage.value = ''

  if (!car.value || !currentUser.value) {
    errorMessage.value = 'Please log in to contact the seller.'
    return
  }

  const cleanMessage = message.value.trim()

  if (cleanMessage.length < 10) {
    errorMessage.value = 'Please enter at least 10 characters.'
    return
  }

  isSending.value = true

  try {
    await apiPost('inquiries.php', {
      listing_id: car.value.listing_id,
      buyer_id: currentUser.value.user_id,
      message: cleanMessage
    })

    successMessage.value = 'Your enquiry has been sent successfully.'
    message.value = ''
  } catch (error) {
    errorMessage.value = error.message || 'Unable to send your enquiry.'
  } finally {
    isSending.value = false
  }
}

// ---- Display helpers (look only) ----
const title = computed(() =>
  car.value ? `${car.value.year} ${car.value.make} ${car.value.model}` : ''
)

const specs = computed(() => {
  if (!car.value) return []
  const c = car.value
  return [
    { icon: 'calendar', label: 'Year', value: c.year },
    {
      icon: 'gauge',
      label: 'Mileage',
      value: c.mileage_km != null ? `${formatNumber(c.mileage_km)} km` : ''
    },
    { icon: 'gear', label: 'Transmission', value: c.transmission },
    { icon: 'fuel', label: 'Fuel', value: c.fuel_type },
    { icon: 'car', label: 'Body', value: c.body_type },
    { icon: 'palette', label: 'Colour', value: c.colour }
  ].map((spec) => ({ ...spec, value: spec.value || 'Not specified' }))
})

const sellerInitials = computed(() => {
  const name = (car.value && car.value.seller_name) || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  return parts.length
    ? (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase()
    : '?'
})

const isOwnCar = computed(
  () =>
    Boolean(currentUser.value && car.value) &&
    Number(currentUser.value.user_id) === Number(car.value.seller_id)
)

const statusLabel = computed(() => {
  if (!car.value) return ''
  return car.value.status === 'reserved'
    ? 'Reserved'
    : car.value.status === 'sold'
      ? 'Sold'
      : 'Available'
})
</script>

<template>
  <div>
    <div class="detail-top">
      <div class="container">
        <nav aria-label="Breadcrumb">
          <ol class="breadcrumb dd-breadcrumb mb-0">
            <li class="breadcrumb-item"><router-link :to="{ name: 'home' }">Home</router-link></li>
            <li class="breadcrumb-item">
              <router-link :to="{ name: 'cars' }">Browse cars</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
              {{ car ? `${car.make} ${car.model}` : 'Car' }}
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <section class="container pb-5">
      <div v-if="loading" class="row g-4" aria-busy="true">
        <div class="col-12 col-lg-8">
          <div class="dd-skeleton mb-3" style="height: 44px; width: 60%"></div>
          <div class="dd-skeleton" style="aspect-ratio: 16 / 10"></div>
        </div>
        <div class="col-12 col-lg-4">
          <div class="dd-skeleton" style="height: 320px"></div>
        </div>
        <span class="visually-hidden" role="status">Loading vehicle details...</span>
      </div>

      <div v-else-if="loadError" class="dd-empty" role="alert">
        <span class="dd-icon-circle"><AppIcon name="alert" :size="28" /></span>
        <h1 class="h3">We couldn't load this car</h1>
        <p>{{ loadError }}</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <button type="button" class="btn btn-primary" @click="loadCar(id)">Try again</button>
          <router-link :to="{ name: 'cars' }" class="btn btn-outline-primary">
            Browse cars
          </router-link>
        </div>
      </div>

      <div v-else-if="car" class="row g-4 g-xl-5">
        <!-- Vehicle information -->
        <div class="col-12 col-lg-7 col-xl-8">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span
              class="dd-status"
              :class="`dd-status-${car.status === 'reserved' || car.status === 'sold' ? car.status : 'available'}`"
            >
              {{ statusLabel }}
            </span>
            <span
              v-if="car.location"
              class="text-muted small d-inline-flex align-items-center gap-1"
            >
              <AppIcon name="pin" :size="16" /> {{ car.location }}
            </span>
          </div>
          <h1 class="detail-title">{{ title }}</h1>

          <ImageGallery
            :images="car.images || []"
            :car-name="`${car.make} ${car.model}`"
            class="mb-4"
          />

          <div class="dd-card dd-card-body mb-4">
            <h2 class="h3 mb-3">Vehicle specifications</h2>
            <dl class="spec-grid">
              <div v-for="spec in specs" :key="spec.label" class="spec">
                <span class="dd-icon-circle"><AppIcon :name="spec.icon" /></span>
                <div>
                  <dt>{{ spec.label }}</dt>
                  <dd>{{ spec.value }}</dd>
                </div>
              </div>
            </dl>
          </div>

          <div class="dd-card dd-card-body">
            <h2 class="h3 mb-3">Description</h2>
            <p v-if="car.description" class="description mb-0">{{ car.description }}</p>
            <p v-else class="text-muted mb-0">The seller hasn't added a description yet.</p>
          </div>
        </div>

        <!-- Price, seller and contact -->
        <div class="col-12 col-lg-5 col-xl-4">
          <div class="detail-side">
            <div class="dd-card dd-card-body mb-3">
              <p class="text-muted small mb-1">Asking price</p>
              <p class="car-price mb-3">RM {{ formatNumber(car.price) }}</p>
              <LikeButton
                :listing-id="car.listing_id"
                :likes="Number(car.likes ?? 0)"
                :liked="Boolean(car.liked)"
              />
            </div>

            <div class="dd-card dd-card-body mb-3">
              <p class="text-muted small mb-2">Seller</p>
              <div class="d-flex align-items-center gap-3">
                <span class="dd-avatar" aria-hidden="true">{{ sellerInitials }}</span>
                <div class="min-w-0">
                  <p class="fw-bold text-truncate mb-0">
                    {{ car.seller_name || 'DriveDeal seller' }}
                  </p>
                  <p class="small text-muted mb-0">Private seller</p>
                </div>
              </div>
            </div>

            <div class="dd-card dd-card-body">
              <h2 class="h3 mb-1 d-flex align-items-center gap-2">
                <AppIcon name="message" :size="20" /> Contact seller
              </h2>

              <template v-if="isOwnCar">
                <p class="text-muted mb-3">
                  This is your listing, so you can't send it an enquiry.
                </p>
                <router-link
                  :to="{ name: 'edit-listing', params: { id: car.listing_id } }"
                  class="btn btn-outline-primary w-100"
                >
                  <AppIcon name="edit" :size="18" /> Edit listing
                </router-link>
              </template>

              <template v-else>
                <p class="text-muted mb-3">
                  Have a question about this car? Ask the seller directly.
                </p>

                <div v-if="!currentUser">
                  <p class="text-muted small">Please log in to contact the seller.</p>

                  <router-link
                    :to="{ name: 'login', query: { redirect: `/cars/${car.listing_id}` } }"
                    class="btn btn-primary w-100"
                  >
                    Log in to enquire
                  </router-link>
                </div>

                <form v-else @submit.prevent="sendInquiry">
                  <div class="mb-3">
                    <label for="sellerMessage" class="form-label">Your message</label>

                    <textarea
                      id="sellerMessage"
                      v-model.trim="message"
                      class="form-control"
                      rows="5"
                      minlength="10"
                      maxlength="1000"
                      placeholder="Hi, I'm interested in this car. Is it still available?"
                      required
                    ></textarea>

                    <div class="form-text">Minimum 10 characters.</div>
                  </div>

                  <div v-if="successMessage" class="alert alert-success" role="status">
                    {{ successMessage }}
                  </div>

                  <div v-if="errorMessage" class="alert alert-danger" role="alert">
                    {{ errorMessage }}
                  </div>

                  <button type="submit" class="btn btn-primary w-100" :disabled="isSending">
                    <span
                      v-if="isSending"
                      class="spinner-border spinner-border-sm"
                      aria-hidden="true"
                    ></span>
                    {{ isSending ? 'Sending...' : 'Send enquiry' }}
                  </button>
                </form>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="dd-empty" role="alert">
        <span class="dd-icon-circle"><AppIcon name="car" :size="28" /></span>
        <h1 class="h3">Car not found</h1>
        <p>This car listing could not be found.</p>
        <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
      </div>
    </section>
  </div>
</template>

<style scoped>
.detail-top {
  padding: 1.25rem 0 1rem;
}

.detail-title {
  margin: 0.25rem 0 1.25rem;
  font-size: clamp(1.75rem, 1.3rem + 1.6vw, 2.5rem);
}

.spec-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.1rem 1rem;
  margin: 0;
}

.spec {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.spec dt {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--dd-muted);
}

.spec dd {
  margin: 0;
  font-weight: 700;
  color: var(--dd-ink);
  overflow-wrap: anywhere;
}

@media (min-width: 768px) {
  .spec-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.description {
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.car-price {
  color: var(--dd-primary);
  font-size: clamp(1.8rem, 1.4rem + 1.4vw, 2.3rem);
  font-weight: 800;
  line-height: 1.1;
}

.min-w-0 {
  min-width: 0;
}

@media (min-width: 992px) {
  .detail-side {
    position: sticky;
    top: calc(var(--dd-navbar-h) + 16px);
  }
}
</style>
