<script setup>
import { ref, watch } from 'vue'
import { apiGet, apiPost } from '../services/api'
import { currentUser } from '../services/auth'
import ImageGallery from '../components/ImageGallery.vue'
import LikeButton from '../components/LikeButton.vue'

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
    loadError.value =
      error.message || 'Unable to load vehicle details.'
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

</script>

<template>
  <section class="container py-5">
    <router-link :to="{ name: 'cars' }" class="back-link d-inline-block mb-4">
      &larr; Back to Browse Cars
    </router-link>


    <div v-if="loading" class="alert alert-info" role="status">
      Loading vehicle details...
    </div>

    <div v-else-if="loadError" class="alert alert-danger" role="alert">
      {{ loadError }}
    </div>

    <template v-else-if="car">

      <div class="row g-4">
        <!-- Vehicle information -->
        <div class="col-12 col-lg-8">
          <h1 class="fw-bold mb-2">
            {{ car.make }} {{ car.model }}
          </h1>

          <p class="text-muted mb-4">
            Vehicle Details
          </p>

          <ImageGallery :images="car.images || []" :car-name="`${car.make} ${car.model}`" class="mb-4" />

          <div class="details-card">
            <h2 class="h4 fw-bold mb-4">
              Vehicle Specifications
            </h2>

            <div class="row g-4">
              <div class="col-6">
                <p class="detail-label">Brand</p>
                <p class="fw-semibold">{{ car.make }}</p>
              </div>

              <div class="col-6">
                <p class="detail-label">Model</p>
                <p class="fw-semibold">{{ car.model }}</p>
              </div>

              <div class="col-6">
                <p class="detail-label">Year</p>
                <p class="fw-semibold">{{ car.year }}</p>
              </div>

              <div class="col-6">
                <p class="detail-label">Mileage</p>
                <p class="fw-semibold">
                  {{ formatNumber(car.mileage_km) }} km
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Price and seller panel -->
        <div class="col-12 col-lg-4">
          <div class="price-card">
            <p class="text-muted mb-2">Asking Price</p>


            <p class="car-price mb-4">
              RM {{ formatNumber(car.price) }}
            </p>

            <div class="mb-3">
              <LikeButton :listing-id="car.listing_id" :likes="Number(car.likes ?? 0)" :liked="Boolean(car.liked)" />
            </div>

            <hr />


            <h2 class="h5 fw-bold mb-3">
              Interested in this car?
            </h2>


            <p class="text-muted mb-3">
              Have a question about this vehicle?
              Send the seller an enquiry.
            </p>

            <div v-if="!currentUser">
              <p class="text-muted small">
                Please log in to contact the seller.
              </p>

              <router-link :to="{ name: 'login' }" class="btn btn-dark w-100">
                Log In to Enquire
              </router-link>
            </div>

            <form v-else @submit.prevent="sendInquiry">
              <div class="mb-3">
                <label for="sellerMessage" class="form-label fw-semibold">
                  Your Message
                </label>

                <textarea
id="sellerMessage" v-model.trim="message" class="form-control" rows="5" minlength="10"
                  maxlength="1000" placeholder="Hi, I'm interested in this car. Is it still available?"
                  required></textarea>

                <div class="form-text">
                  Minimum 10 characters.
                </div>
              </div>

              <div v-if="successMessage" class="alert alert-success" role="status">
                {{ successMessage }}
              </div>

              <div v-if="errorMessage" class="alert alert-danger" role="alert">
                {{ errorMessage }}
              </div>

              <button type="submit" class="btn btn-dark w-100" :disabled="isSending">
                {{ isSending ? 'Sending...' : 'Send Enquiry' }}
              </button>
            </form>

          </div>
        </div>
      </div>
    </template>

    <div v-else class="alert alert-warning" role="alert">
      <h1 class="h4">Car Not Found</h1>
      <p class="mb-0">
        This car listing could not be found.
      </p>
    </div>
  </section>
</template>

<style scoped>
.back-link {
  color: #9a3412;
  text-decoration: none;
  font-weight: 600;
}

.back-link:hover {
  text-decoration: underline;
}

.details-card,
.price-card {
  padding: 30px;
  background: white;
  border: 1px solid #e9e5e1;
  border-radius: 14px;
}

.detail-label {
  color: #6c757d;
  margin-bottom: 5px;
}

.car-price {
  color: #c2410c;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
}

@media (max-width: 767px) {
  .photo-placeholder {
    min-height: 220px;
  }

  .details-card,
  .price-card {
    padding: 22px;
  }
}
</style>
