<script setup>
// My listings dashboard - /my-listings
// OWNER: Lynn
// The logged-in user's cars (listings.php?seller_id=) with View / Edit / Delete,
// a status summary, and buyer enquiries (inquiries.php?seller_id=) in InquiryList.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import InquiryList from '../components/InquiryList.vue'
import { apiDelete, apiGet, imageUrl } from '../services/api'
import { currentUser } from '../services/auth'

const cars = ref([])
const loadingCars = ref(false)
const carsError = ref('')

const inquiries = ref([])
const loadingInquiries = ref(false)
const inquiriesError = ref('')

const carToDelete = ref(null)
const deleting = ref(false)
const deleteError = ref('')
const successMessage = ref('')
const cancelButton = ref(null)

const STATUS_BADGES = {
  available: 'dd-status-available',
  reserved: 'dd-status-reserved',
  sold: 'dd-status-sold'
}

const summary = computed(() => {
  const counts = { total: cars.value.length, available: 0, reserved: 0, sold: 0 }
  for (const car of cars.value) {
    if (counts[car.status] !== undefined) counts[car.status]++
  }
  return counts
})

const summaryCards = computed(() => [
  { label: 'Total', value: summary.value.total, className: 'text-primary' },
  { label: 'Available', value: summary.value.available, className: 'text-success' },
  { label: 'Reserved', value: summary.value.reserved, className: 'text-warning' },
  { label: 'Sold', value: summary.value.sold, className: 'text-secondary' }
])

function formatPrice(price) {
  return `RM ${Number(price).toLocaleString('en-MY')}`
}

function statusLabel(status) {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

async function loadCars(userId) {
  loadingCars.value = true
  carsError.value = ''
  try {
    const data = await apiGet('listings.php', { seller_id: userId, perPage: 100 })
    cars.value = data.cars || []
  } catch (e) {
    carsError.value = e.message
  } finally {
    loadingCars.value = false
  }
}

async function loadInquiries(userId) {
  loadingInquiries.value = true
  inquiriesError.value = ''
  try {
    inquiries.value = await apiGet('inquiries.php', { seller_id: userId })
  } catch (e) {
    inquiriesError.value = e.message
  } finally {
    loadingInquiries.value = false
  }
}

function loadAll() {
  const user = currentUser.value
  cars.value = []
  inquiries.value = []
  if (!user) return
  loadCars(user.user_id)
  loadInquiries(user.user_id)
}

// Load now, and again if a different user logs in
watch(() => currentUser.value && currentUser.value.user_id, loadAll, { immediate: true })

// ---- Delete with a confirm modal ----
async function askDelete(car) {
  deleteError.value = ''
  successMessage.value = ''
  carToDelete.value = car
  await nextTick()
  if (cancelButton.value) cancelButton.value.focus() // so Esc closes it
}

function closeDeleteModal() {
  if (!deleting.value) carToDelete.value = null
}

async function confirmDelete() {
  const car = carToDelete.value
  deleting.value = true
  deleteError.value = ''
  try {
    await apiDelete('listings.php', {
      listing_id: car.listing_id,
      seller_id: currentUser.value.user_id
    })
    carToDelete.value = null
    successMessage.value = `${car.make} ${car.model} (${car.year}) has been deleted.`
    cars.value = cars.value.filter((item) => item.listing_id !== car.listing_id)
    loadInquiries(currentUser.value.user_id) // its enquiries were deleted too
  } catch (e) {
    deleteError.value = e.message
  } finally {
    deleting.value = false
  }
}

watch(carToDelete, (car) => document.body.classList.toggle('modal-open', car !== null))
onBeforeUnmount(() => document.body.classList.remove('modal-open'))
</script>

<template>
  <section class="container py-4">
    <div v-if="!currentUser" class="alert alert-info" role="alert">
      <h1 class="h4">Please log in to see your listings</h1>
      <p class="mb-0">
        <router-link :to="{ name: 'login' }" class="alert-link">Log in</router-link>
        or
        <router-link :to="{ name: 'register' }" class="alert-link">create an account</router-link>
        to sell cars and see enquiries from buyers.
      </p>
    </div>

    <template v-else>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="mb-0">My listings</h1>
        <router-link :to="{ name: 'sell' }" class="btn btn-primary">+ Sell a car</router-link>
      </div>

      <div v-if="successMessage" class="alert alert-success alert-dismissible" role="status">
        {{ successMessage }}
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="successMessage = ''"
        ></button>
      </div>

      <div v-if="loadingCars" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading your cars...</span>
        </div>
      </div>

      <div v-else-if="carsError" class="alert alert-danger" role="alert">
        {{ carsError }}
        <button
          type="button"
          class="btn btn-sm btn-outline-danger ms-2"
          @click="loadCars(currentUser.user_id)"
        >
          Try again
        </button>
      </div>

      <div v-else-if="!cars.length" class="text-center border rounded p-5 mb-4 bg-light">
        <p class="lead mb-3">You haven't listed any cars yet.</p>
        <router-link :to="{ name: 'sell' }" class="btn btn-primary"
          >Sell your first car</router-link
        >
      </div>

      <template v-else>
        <!-- Summary -->
        <div class="row g-2 g-md-3 mb-4">
          <div v-for="item in summaryCards" :key="item.label" class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <div class="fs-3 fw-bold" :class="item.className">{{ item.value }}</div>
                <div class="small text-muted">{{ item.label }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cars -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 mb-5">
          <div v-for="car in cars" :key="car.listing_id" class="col">
            <div class="card h-100 shadow-sm">
              <div class="position-relative">
                <img
                  v-if="car.image_path"
                  :src="imageUrl(car.image_path)"
                  class="card-img-top cover"
                  :alt="`${car.make} ${car.model}`"
                />
                <div
                  v-else
                  class="cover d-flex align-items-center justify-content-center bg-light text-muted"
                >
                  No photo
                </div>
                <span
                  class="dd-status position-absolute top-0 end-0 m-2"
                  :class="STATUS_BADGES[car.status]"
                >
                  {{ statusLabel(car.status) }}
                </span>
              </div>
              <div class="card-body">
                <h2 class="card-title h5 mb-1">{{ car.make }} {{ car.model }} {{ car.year }}</h2>
                <p class="fs-5 fw-semibold text-primary mb-1">{{ formatPrice(car.price) }}</p>
                <p class="small text-muted mb-0">
                  &hearts; {{ car.likes }} {{ car.likes === 1 ? 'like' : 'likes' }}
                </p>
              </div>
              <div class="card-footer bg-transparent border-top-0 pb-3">
                <div
                  class="btn-group w-100"
                  role="group"
                  :aria-label="`Actions for ${car.make} ${car.model}`"
                >
                  <router-link
                    :to="{ name: 'car-detail', params: { id: car.listing_id } }"
                    class="btn btn-outline-secondary btn-sm"
                  >
                    View
                  </router-link>
                  <router-link
                    :to="{ name: 'edit-listing', params: { id: car.listing_id } }"
                    class="btn btn-outline-primary btn-sm"
                  >
                    Edit
                  </router-link>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="deleting"
                    @click="askDelete(car)"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Enquiries -->
      <h2 class="h4 mb-3">Enquiries from buyers</h2>
      <div v-if="loadingInquiries" class="text-center py-4">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Loading enquiries...</span>
        </div>
      </div>
      <div v-else-if="inquiriesError" class="alert alert-danger" role="alert">
        {{ inquiriesError }}
      </div>
      <InquiryList v-else :inquiries="inquiries" />
    </template>

    <!-- Delete confirmation (Bootstrap modal markup, opened and closed by Vue) -->
    <template v-if="carToDelete">
      <div
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-modal-title"
        @click.self="closeDeleteModal"
        @keydown.esc="closeDeleteModal"
      >
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h2 id="delete-modal-title" class="modal-title h5">Delete this listing?</h2>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                :disabled="deleting"
                @click="closeDeleteModal"
              ></button>
            </div>
            <div class="modal-body">
              <p class="mb-2">
                <strong>
                  {{ carToDelete.make }} {{ carToDelete.model }} ({{ carToDelete.year }})
                </strong>
                will be removed with all its photos, likes and enquiries. This can't be undone.
              </p>
              <div v-if="deleteError" class="alert alert-danger mb-0" role="alert">
                {{ deleteError }}
              </div>
            </div>
            <div class="modal-footer">
              <button
                ref="cancelButton"
                type="button"
                class="btn btn-outline-secondary"
                :disabled="deleting"
                @click="closeDeleteModal"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-danger"
                :disabled="deleting"
                @click="confirmDelete"
              >
                <span
                  v-if="deleting"
                  class="spinner-border spinner-border-sm me-2"
                  aria-hidden="true"
                ></span>
                {{ deleting ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </template>
  </section>
</template>

<style scoped>
.cover {
  aspect-ratio: 4 / 3;
  object-fit: cover;
  width: 100%;
}
</style>
