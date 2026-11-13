<script setup>
// My listings dashboard - /my-listings
// OWNER: Lynn
// The logged-in user's cars (listings.php?seller_id=) with View / Edit / Delete,
// a status summary, buyer offers (offers.php?seller_id=, Lynn's individual feature)
// and buyer enquiries (inquiries.php?seller_id=) in InquiryList.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import InquiryList from '../components/InquiryList.vue'
import OfferInbox from '../components/OfferInbox.vue'
import { fetchOffers } from '../services/offersApi'
import AppIcon from '../components/AppIcon.vue'
import PageHeader from '../components/PageHeader.vue'
import { apiDelete, apiGet, imageUrl } from '../services/api'
import { currentUser } from '../services/auth'

const cars = ref([])
const loadingCars = ref(false)
const carsError = ref('')

const inquiries = ref([])
const loadingInquiries = ref(false)
const inquiriesError = ref('')

const offers = ref([])
const loadingOffers = ref(false)
const offersError = ref('')
const offerTab = ref('open')
const route = useRoute()

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
  { label: 'Total listings', value: summary.value.total, icon: 'list' },
  { label: 'Available', value: summary.value.available, icon: 'check' },
  { label: 'Reserved', value: summary.value.reserved, icon: 'clock' },
  { label: 'Sold', value: summary.value.sold, icon: 'tag' }
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

async function loadOffers(userId, { quiet = false } = {}) {
  if (!quiet) loadingOffers.value = true
  offersError.value = ''
  try {
    offers.value = await fetchOffers({ seller_id: userId })
  } catch (e) {
    offersError.value = e.message
  } finally {
    loadingOffers.value = false
  }
}

const openOffers = computed(() => offers.value.filter((o) => o.status === 'open'))
const otherOffers = computed(() => offers.value.filter((o) => o.status !== 'open'))
const shownOffers = computed(() =>
  offerTab.value === 'open' ? openOffers.value : otherOffers.value
)
const waitingForMe = computed(
  () => openOffers.value.filter((o) => o.summary.turn === 'seller').length
)

/** After a reply: a deal reserves the car and closes the other offers, so refresh both lists. */
function onOfferUpdated(offer) {
  const index = offers.value.findIndex((o) => o.offer_id === offer.offer_id)
  if (index !== -1) offers.value.splice(index, 1, offer)
  if (offer.status === 'accepted') {
    const car = cars.value.find((c) => c.listing_id === offer.listing_id)
    if (car) car.status = offer.car_status
    loadOffers(currentUser.value.user_id, { quiet: true })
  }
}

function loadAll() {
  const user = currentUser.value
  cars.value = []
  inquiries.value = []
  offers.value = []
  if (!user) return
  loadInquiries(user.user_id)
  Promise.all([loadCars(user.user_id), loadOffers(user.user_id)]).then(async () => {
    // Coming from "Manage offers" on a car page: jump to the offers once everything is shown
    if (route.hash !== '#offers') return
    await nextTick()
    const section = document.getElementById('offers')
    if (section) section.scrollIntoView({ block: 'start', behavior: 'instant' })
  })
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
  <div>
    <PageHeader
      title="My listings"
      :crumbs="currentUser ? [{ label: 'Profile', to: { name: 'profile' } }] : []"
    >
      <template v-if="currentUser" #subtitle>Manage your cars and reply to buyers.</template>
      <template v-if="currentUser" #actions>
        <router-link :to="{ name: 'sell' }" class="btn btn-primary">
          <AppIcon name="plus" :size="18" /> Sell a car
        </router-link>
      </template>
    </PageHeader>

    <section class="container py-4 py-lg-5">
      <div v-if="!currentUser" class="dd-empty">
        <span class="dd-icon-circle"><AppIcon name="list" :size="28" /></span>
        <h2>Please sign in to see your listings</h2>
        <p>Sign in to sell cars and see enquiries from buyers.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <router-link :to="{ name: 'login' }" class="btn btn-primary">Sign in</router-link>
          <router-link :to="{ name: 'register' }" class="btn btn-outline-primary">
            Create an account
          </router-link>
        </div>
      </div>

      <template v-else>
        <div v-if="successMessage" class="alert alert-success alert-dismissible" role="status">
          {{ successMessage }}
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="successMessage = ''"
          ></button>
        </div>

        <div v-if="loadingCars" class="row g-4 mb-5" aria-busy="true">
          <div class="col-12"><div class="dd-skeleton" style="height: 96px"></div></div>
          <div v-for="n in 3" :key="n" class="col-12 col-sm-6 col-lg-4">
            <div class="dd-skeleton" style="height: 320px"></div>
          </div>
          <span class="visually-hidden" role="status">Loading your cars...</span>
        </div>

        <div
          v-else-if="carsError"
          class="alert alert-danger d-flex flex-wrap align-items-center gap-3"
          role="alert"
        >
          <span>{{ carsError }}</span>
          <button
            type="button"
            class="btn btn-sm btn-outline-danger"
            @click="loadCars(currentUser.user_id)"
          >
            Try again
          </button>
        </div>

        <div v-else-if="!cars.length" class="dd-empty mb-5">
          <span class="dd-icon-circle"><AppIcon name="car" :size="28" /></span>
          <h2>You haven't listed any cars yet</h2>
          <p>Add your car's details and photos, and buyers can contact you straight away.</p>
          <router-link :to="{ name: 'sell' }" class="btn btn-primary"
            >Sell your first car</router-link
          >
        </div>

        <template v-else>
          <!-- Summary -->
          <ul class="dd-stats list-unstyled mb-4 mb-lg-5" aria-label="Listing summary">
            <li v-for="item in summaryCards" :key="item.label" class="dd-stat">
              <span class="dd-icon-circle"><AppIcon :name="item.icon" /></span>
              <span>
                <span class="dd-stat-value">{{ item.value }}</span>
                <span class="dd-stat-label">{{ item.label }}</span>
              </span>
            </li>
          </ul>

          <!-- Cars -->
          <h2 class="h3 mb-3">Your cars</h2>
          <div class="row g-4 mb-5">
            <div v-for="car in cars" :key="car.listing_id" class="col-12 col-sm-6 col-lg-4">
              <article class="listing-card dd-card h-100">
                <div class="listing-photo">
                  <img
                    v-if="car.image_path"
                    :src="imageUrl(car.image_path)"
                    :alt="`${car.make} ${car.model} photo`"
                    loading="lazy"
                  />
                  <div v-else class="listing-placeholder">
                    <AppIcon name="image" :size="32" />
                    <span>No photo</span>
                  </div>
                  <span class="dd-status listing-status" :class="STATUS_BADGES[car.status]">
                    {{ statusLabel(car.status) }}
                  </span>
                </div>
                <div class="listing-body">
                  <h3 class="listing-title">{{ car.year }} {{ car.make }} {{ car.model }}</h3>
                  <p class="listing-price">{{ formatPrice(car.price) }}</p>
                  <p class="small text-muted mb-0 d-flex align-items-center gap-1">
                    <AppIcon name="heart" :size="16" />
                    {{ car.likes }} {{ car.likes === 1 ? 'like' : 'likes' }}
                  </p>
                </div>
                <div
                  class="listing-actions"
                  role="group"
                  :aria-label="`Actions for ${car.make} ${car.model}`"
                >
                  <router-link
                    :to="{ name: 'car-detail', params: { id: car.listing_id } }"
                    class="btn btn-outline-secondary btn-sm"
                  >
                    <AppIcon name="eye" :size="16" /> View
                  </router-link>
                  <router-link
                    :to="{ name: 'edit-listing', params: { id: car.listing_id } }"
                    class="btn btn-outline-primary btn-sm"
                  >
                    <AppIcon name="edit" :size="16" /> Edit
                  </router-link>
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="deleting"
                    :aria-label="`Delete ${car.make} ${car.model}`"
                    @click="askDelete(car)"
                  >
                    <AppIcon name="trash" :size="16" /> Delete
                  </button>
                </div>
              </article>
            </div>
          </div>
        </template>

        <!-- Offers (Lynn's individual feature) -->
        <section id="offers" class="offers-section mb-5" aria-labelledby="offersTitle">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <h2 id="offersTitle" class="h3 mb-0">Offers on your cars</h2>
              <span
                v-if="waitingForMe"
                class="enquiry-count"
                :title="`${waitingForMe} waiting for your reply`"
              >
                {{ waitingForMe }}
              </span>
            </div>
            <div v-if="offers.length" class="offer-tabs" role="tablist" aria-label="Filter offers">
              <button
                type="button"
                role="tab"
                class="offer-tab"
                :class="{ active: offerTab === 'open' }"
                :aria-selected="offerTab === 'open'"
                @click="offerTab = 'open'"
              >
                Open <span class="tab-count">{{ openOffers.length }}</span>
              </button>
              <button
                type="button"
                role="tab"
                class="offer-tab"
                :class="{ active: offerTab === 'done' }"
                :aria-selected="offerTab === 'done'"
                @click="offerTab = 'done'"
              >
                Agreed &amp; ended <span class="tab-count">{{ otherOffers.length }}</span>
              </button>
            </div>
          </div>
          <p v-if="waitingForMe" class="small fw-semibold text-primary" role="status">
            {{ waitingForMe }} {{ waitingForMe === 1 ? 'buyer is' : 'buyers are' }} waiting for your
            reply.
          </p>

          <div v-if="loadingOffers" aria-busy="true">
            <div class="dd-skeleton mb-2" style="height: 200px"></div>
            <span class="visually-hidden" role="status">Loading offers...</span>
          </div>
          <div v-else-if="offersError" class="alert alert-danger" role="alert">
            {{ offersError }}
          </div>
          <div v-else-if="!offers.length" class="dd-empty">
            <span class="dd-icon-circle"><AppIcon name="tag" :size="28" /></span>
            <h3>No offers yet</h3>
            <p class="mb-0">
              When buyers make an offer on your cars, you can accept, reject or counter it here.
            </p>
          </div>
          <template v-else>
            <p v-if="!shownOffers.length" class="text-muted">
              {{
                offerTab === 'open' ? 'No open offers right now.' : 'No agreed or ended offers yet.'
              }}
            </p>
            <OfferInbox
              :key="offerTab"
              :offers="shownOffers"
              role="seller"
              show-car
              class="offers-list"
              @updated="onOfferUpdated"
            />
          </template>
        </section>

        <!-- Enquiries -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <h2 class="h3 mb-0">Enquiries from buyers</h2>
          <span v-if="inquiries.length" class="enquiry-count">{{ inquiries.length }}</span>
        </div>
        <div v-if="loadingInquiries" aria-busy="true">
          <div class="dd-skeleton mb-2" style="height: 110px"></div>
          <div class="dd-skeleton" style="height: 110px"></div>
          <span class="visually-hidden" role="status">Loading enquiries...</span>
        </div>
        <div v-else-if="inquiriesError" class="alert alert-danger" role="alert">
          {{ inquiriesError }}
        </div>
        <InquiryList v-else :inquiries="inquiries" />
      </template>
    </section>

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
  </div>
</template>

<style scoped>
.listing-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.listing-photo {
  position: relative;
  aspect-ratio: 4 / 3;
  background: var(--dd-surface);
}

.listing-photo img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.listing-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  height: 100%;
  color: var(--dd-muted);
  font-size: 0.875rem;
}

.listing-status {
  position: absolute;
  top: 12px;
  left: 12px;
  box-shadow: var(--dd-shadow-sm);
}

.listing-body {
  flex: 1;
  padding: 1rem 1.1rem 0.75rem;
}

.listing-title {
  margin: 0 0 0.35rem;
  font-size: 1.05rem;
}

.listing-price {
  margin: 0 0 0.35rem;
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--dd-primary);
}

.listing-actions {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
  padding: 0.75rem 1.1rem 1.1rem;
  border-top: 1px solid var(--dd-line);
}

.listing-actions .btn {
  --bs-btn-padding-x: 0.4rem;
  gap: 0.3rem;
}

.offers-list {
  max-width: 860px;
}

.offer-tabs {
  display: inline-flex;
  gap: 0.25rem;
  padding: 0.25rem;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-surface);
  border: 1px solid var(--dd-line);
}

.offer-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 40px;
  padding: 0.35rem 1rem;
  border: 0;
  border-radius: var(--dd-radius-pill);
  background: transparent;
  color: var(--dd-body);
  font-weight: 600;
}

.offer-tab.active {
  background: var(--dd-white);
  color: var(--dd-primary);
  box-shadow: var(--dd-shadow-sm);
}

.tab-count {
  min-width: 22px;
  padding: 0 6px;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-primary-50);
  color: var(--dd-primary-700);
  font-size: 0.75rem;
  text-align: center;
}

.enquiry-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 26px;
  height: 26px;
  padding: 0 8px;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-primary);
  color: var(--dd-white);
  font-size: 0.8rem;
  font-weight: 700;
}
</style>
