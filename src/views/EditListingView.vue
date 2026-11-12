<script setup>
// Edit listing page - /cars/:id/edit
// OWNER: Lynn
// Loads the car, checks the logged-in user owns it, then CarForm pre-filled:
// save = listings.php PUT + upload.php for new photos; Remove photo = upload.php DELETE;
// Delete = confirm modal + listings.php DELETE -> My listings.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import CarForm from '../components/CarForm.vue'
import { apiDelete, apiGet, apiPut, apiUpload } from '../services/api'
import { currentUser, getUser } from '../services/auth'

const props = defineProps({
  id: { type: String, required: true } // from the URL, e.g. /cars/3/edit -> '3'
})

const router = useRouter()

const car = ref(null)
const loading = ref(true)
const notFound = ref(false)
const loadError = ref('')

const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
// Photos that failed to upload (also passed here by the Sell page through history state)
const failedPhotos = ref([])

const showDeleteModal = ref(false)
const deleting = ref(false)
const deleteError = ref('')

const isOwner = computed(
  () =>
    Boolean(currentUser.value && car.value) &&
    Number(car.value.seller_id) === Number(currentUser.value.user_id)
)

async function loadCar({ quiet = false } = {}) {
  if (!quiet) loading.value = true
  loadError.value = ''
  notFound.value = false
  try {
    const data = await apiGet('listings.php', { id: props.id })
    // A non-number id makes listings.php return the car list instead of one car
    if (!data || !data.listing_id) {
      notFound.value = true
      car.value = null
    } else {
      car.value = data
    }
  } catch (e) {
    if (e.message === 'Car not found.') notFound.value = true
    else loadError.value = e.message
    car.value = null
  } finally {
    loading.value = false
  }
}

watch(
  () => props.id,
  () => {
    errorMessage.value = ''
    successMessage.value = ''
    const state = window.history.state
    failedPhotos.value = state && Array.isArray(state.failedPhotos) ? state.failedPhotos : []
    if (failedPhotos.value.length) {
      // Show the message once: forget it so a page reload doesn't show it again
      window.history.replaceState({ ...state, failedPhotos: null }, '')
    }
    loadCar()
  },
  { immediate: true }
)

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function uploadPhotos(listingId, files) {
  const failed = []
  for (const file of files) {
    try {
      const form = new FormData()
      form.append('image', file)
      form.append('listing_id', listingId)
      form.append('seller_id', getUser().user_id)
      await apiUpload('upload.php', form)
    } catch (e) {
      failed.push(`${file.name}: ${e.message}`)
    }
  }
  return failed
}

async function save(carData, files) {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  failedPhotos.value = []
  try {
    await apiPut('listings.php', {
      ...carData,
      listing_id: car.value.listing_id,
      seller_id: getUser().user_id
    })
  } catch (e) {
    errorMessage.value = e.message
    saving.value = false
    scrollToTop()
    return
  }

  failedPhotos.value = await uploadPhotos(car.value.listing_id, files)
  await loadCar({ quiet: true }) // fresh data (and photos) for the form
  saving.value = false
  successMessage.value = failedPhotos.value.length
    ? 'Your changes were saved, but some photos could not be uploaded.'
    : 'Your changes have been saved.'
  scrollToTop()
}

async function removeImage(image) {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await apiDelete('upload.php', { image_id: image.image_id, seller_id: getUser().user_id })
    // Update the photo list only, so unsaved changes in the form are kept
    car.value.images = car.value.images.filter((item) => item.image_id !== image.image_id)
  } catch (e) {
    errorMessage.value = e.message
    scrollToTop()
  } finally {
    saving.value = false
  }
}

const cancelButton = ref(null)

async function openDeleteModal() {
  deleteError.value = ''
  showDeleteModal.value = true
  await nextTick()
  if (cancelButton.value) cancelButton.value.focus() // so Esc closes it
}

function closeDeleteModal() {
  if (!deleting.value) showDeleteModal.value = false
}

async function confirmDelete() {
  deleting.value = true
  deleteError.value = ''
  try {
    await apiDelete('listings.php', {
      listing_id: car.value.listing_id,
      seller_id: getUser().user_id
    })
    showDeleteModal.value = false
    router.push({ name: 'my-listings' })
  } catch (e) {
    deleteError.value = e.message
  } finally {
    deleting.value = false
  }
}

// Stop the page behind the modal from scrolling, like Bootstrap's own modal does
watch(showDeleteModal, (open) => document.body.classList.toggle('modal-open', open))
onBeforeUnmount(() => document.body.classList.remove('modal-open'))
</script>

<template>
  <section class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-9">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="notFound" class="alert alert-warning" role="alert">
          <h1 class="h4">Car not found</h1>
          <p class="mb-0">
            This car doesn't exist or has been deleted.
            <router-link :to="{ name: 'my-listings' }" class="alert-link">
              Back to My listings
            </router-link>
          </p>
        </div>

        <div v-else-if="loadError" class="alert alert-danger" role="alert">
          {{ loadError }}
          <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="loadCar()">
            Try again
          </button>
        </div>

        <div v-else-if="!isOwner" class="alert alert-warning" role="alert">
          <h1 class="h4">You can only edit your own listings</h1>
          <p class="mb-0">
            <template v-if="!currentUser">
              Please <router-link :to="{ name: 'login' }" class="alert-link">log in</router-link>
              as the seller of this car.
            </template>
            <router-link :to="{ name: 'my-listings' }" class="alert-link">
              Go to My listings
            </router-link>
          </p>
        </div>

        <template v-else>
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
              <h1 class="mb-1">Edit listing</h1>
              <p class="text-muted mb-0">
                {{ car.make }} {{ car.model }} ({{ car.year }}) &middot;
                <router-link :to="{ name: 'car-detail', params: { id: car.listing_id } }">
                  View listing
                </router-link>
              </p>
            </div>
            <button
              type="button"
              class="btn btn-danger"
              :disabled="saving || deleting"
              @click="openDeleteModal"
            >
              Delete listing
            </button>
          </div>

          <div v-if="successMessage" class="alert alert-success" role="status">
            {{ successMessage }}
          </div>
          <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>
          <div v-if="failedPhotos.length" class="alert alert-warning" role="alert">
            <p class="fw-semibold mb-1">These photos could not be uploaded:</p>
            <ul class="mb-1">
              <li v-for="message in failedPhotos" :key="message">{{ message }}</li>
            </ul>
            <p class="mb-0 small">You can try adding them again below.</p>
          </div>

          <div class="card shadow-sm">
            <div class="card-body p-3 p-md-4">
              <CarForm
                :initial-car="car"
                :saving="saving"
                @submit="save"
                @remove-image="removeImage"
              />
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Delete confirmation (Bootstrap modal markup, opened and closed by Vue) -->
    <template v-if="showDeleteModal && car">
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
                <strong>{{ car.make }} {{ car.model }} ({{ car.year }})</strong> will be removed
                with all its photos, likes and enquiries. This can't be undone.
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
