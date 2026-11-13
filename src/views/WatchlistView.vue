<script setup>
// My watchlist page - /watchlist. OWNER: Yati (D/HD feature).
// Every watched car with its price now vs when it was saved: drop in RM and %, how close it is to
// the target price, and filters/sorting - all computed properties on the Pinia store's data.
import { computed, ref, watch } from 'vue'
import { useWatchlistStore } from '../stores/watchlist'
import { imageUrl } from '../services/api'
import { formatRM, targetPriceError, targetProgress, timeAgo } from '../services/priceChange'
import Pagination from '../components/Pagination.vue'

const PER_PAGE = 6

const watchlist = useWatchlistStore()

const filter = ref('all') // all | dropped | target
const sortBy = ref('drop_percent')
const page = ref(1)
const removedMessage = ref('')
const actionError = ref('')

const FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'dropped', label: 'Price dropped' },
  { key: 'target', label: 'Target reached' }
]

const SORTS = {
  drop_percent: {
    label: 'Biggest drop (%)',
    compare: (a, b) => signedPercent(b) - signedPercent(a)
  },
  drop_amount: { label: 'Biggest drop (RM)', compare: (a, b) => signedAmount(b) - signedAmount(a) },
  recent: { label: 'Recently added', compare: (a, b) => b.added_at.localeCompare(a.added_at) },
  price_asc: { label: 'Price: low to high', compare: (a, b) => a.price - b.price }
}

// A price rise counts as a negative drop, so it sorts below "no change"
function signedPercent(car) {
  return car.change.direction === 'up' ? -car.change.percent : car.change.percent
}
function signedAmount(car) {
  return car.change.direction === 'up' ? -car.change.amount : car.change.amount
}

const filteredCars = computed(() => {
  let list = watchlist.carsWithChange
  if (filter.value === 'dropped') list = list.filter((car) => car.change.direction === 'down')
  if (filter.value === 'target') list = list.filter((car) => car.targetReached)
  return [...list].sort(SORTS[sortBy.value].compare)
})

const pagedCars = computed(() => {
  const start = (page.value - 1) * PER_PAGE
  return filteredCars.value.slice(start, start + PER_PAGE)
})

function filterCount(key) {
  if (key === 'dropped') return watchlist.droppedCars.length
  if (key === 'target') return watchlist.targetsReached.length
  return watchlist.count
}

// Back to page 1 when the filter or sort changes; stay in range when a car is removed
watch([filter, sortBy], () => (page.value = 1))
watch(
  () => filteredCars.value.length,
  (total) => {
    const lastPage = Math.max(1, Math.ceil(total / PER_PAGE))
    if (page.value > lastPage) page.value = lastPage
  }
)

// ---- Edit target inline ----
const editingId = ref(null)
const targetInput = ref('')
const targetTouched = ref(false)
const savingTarget = ref(false)
const editingCar = computed(() =>
  watchlist.carsWithChange.find((c) => c.listing_id === editingId.value)
)
const targetError = computed(() =>
  editingCar.value ? targetPriceError(targetInput.value, editingCar.value.price) : ''
)

function startEditTarget(car) {
  editingId.value = car.listing_id
  targetInput.value = car.target_price || ''
  targetTouched.value = false
  actionError.value = ''
}

async function saveTarget() {
  targetTouched.value = true
  if (targetError.value) return
  savingTarget.value = true
  try {
    await watchlist.setTarget(
      editingId.value,
      targetInput.value === '' ? null : Number(targetInput.value)
    )
    editingId.value = null
  } catch (e) {
    actionError.value = e.message
  } finally {
    savingTarget.value = false
  }
}

async function remove(car) {
  actionError.value = ''
  try {
    await watchlist.unwatch(car.listing_id)
    removedMessage.value = `${car.make} ${car.model} was removed from your watchlist.`
  } catch (e) {
    actionError.value = e.message
  }
}

function statusBadge(status) {
  if (status === 'sold') return { text: 'Sold', className: 'bg-secondary' }
  if (status === 'reserved') return { text: 'Reserved', className: 'bg-warning text-dark' }
  return null
}
</script>

<template>
  <section class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h1 class="mb-1">My watchlist</h1>
        <p class="text-muted mb-0">
          We'll alert you (&#128276; in the menu bar) when any of these cars gets cheaper.
        </p>
      </div>
      <router-link :to="{ name: 'cars' }" class="btn btn-outline-primary"
        >Find more cars</router-link
      >
    </div>

    <div v-if="watchlist.loading && !watchlist.loaded" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading your watchlist...</span>
      </div>
    </div>

    <div v-else-if="watchlist.error" class="alert alert-danger" role="alert">
      {{ watchlist.error }}
      <button
        type="button"
        class="btn btn-sm btn-outline-danger ms-2"
        @click="watchlist.loadWatchlist()"
      >
        Try again
      </button>
    </div>

    <div v-else-if="!watchlist.count" class="text-center border rounded p-5 bg-light">
      <p class="display-6 mb-2" aria-hidden="true">&#128276;</p>
      <p class="lead mb-2">You aren't watching any cars yet.</p>
      <p class="text-muted">
        Open a car and press <strong>Watch price</strong>. You can set a target price too.
      </p>
      <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
    </div>

    <template v-else>
      <!-- Summary -->
      <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
        <div class="col">
          <div class="summary-card">
            <span class="summary-number">{{ watchlist.count }}</span
            >cars watched
          </div>
        </div>
        <div class="col">
          <div class="summary-card">
            <span class="summary-number text-success">{{ watchlist.droppedCars.length }}</span
            >got cheaper
          </div>
        </div>
        <div class="col">
          <div class="summary-card">
            <span class="summary-number text-success">{{ formatRM(watchlist.totalSavings) }}</span
            >total drops
          </div>
        </div>
        <div class="col">
          <div class="summary-card">
            <span class="summary-number text-primary">{{ watchlist.targetsReached.length }}</span
            >targets reached
          </div>
        </div>
      </div>

      <!-- Filter + sort -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="btn-group" role="group" aria-label="Filter watched cars">
          <button
            v-for="option in FILTERS"
            :key="option.key"
            type="button"
            class="btn btn-sm"
            :class="filter === option.key ? 'btn-primary' : 'btn-outline-primary'"
            :aria-pressed="filter === option.key"
            @click="filter = option.key"
          >
            {{ option.label }} ({{ filterCount(option.key) }})
          </button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <label for="watch-sort" class="form-label mb-0 small text-nowrap">Sort by</label>
          <select id="watch-sort" v-model="sortBy" class="form-select form-select-sm">
            <option v-for="(sort, key) in SORTS" :key="key" :value="key">{{ sort.label }}</option>
          </select>
        </div>
      </div>

      <div v-if="removedMessage" class="alert alert-secondary alert-dismissible py-2" role="status">
        {{ removedMessage }}
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="removedMessage = ''"
        ></button>
      </div>
      <div v-if="actionError" class="alert alert-danger py-2" role="alert">{{ actionError }}</div>

      <p v-if="!filteredCars.length" class="text-muted text-center py-4">
        No cars match this filter yet.
      </p>

      <TransitionGroup
        tag="div"
        name="watch-list"
        class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4"
      >
        <div v-for="car in pagedCars" :key="car.listing_id" class="col">
          <article class="card h-100 shadow-sm" :class="{ 'border-success': car.targetReached }">
            <div class="position-relative">
              <img
                v-if="car.image_path"
                :src="imageUrl(car.image_path)"
                :alt="`${car.year} ${car.make} ${car.model}`"
                class="card-img-top watch-img"
              />
              <span
                v-if="car.change.direction === 'down'"
                class="badge bg-success position-absolute top-0 start-0 m-2 fs-6"
              >
                &darr; {{ car.change.percent }}%
              </span>
              <span
                v-if="statusBadge(car.status)"
                class="badge position-absolute top-0 end-0 m-2"
                :class="statusBadge(car.status).className"
              >
                {{ statusBadge(car.status).text }}
              </span>
            </div>

            <div class="card-body d-flex flex-column">
              <h2 class="h5 card-title mb-1">
                <router-link
                  :to="{ name: 'car-detail', params: { id: String(car.listing_id) } }"
                  class="text-reset"
                >
                  {{ car.year }} {{ car.make }} {{ car.model }}
                </router-link>
              </h2>
              <p class="small text-muted mb-2">Watching since {{ timeAgo(car.added_at) }}</p>

              <div class="d-flex align-items-baseline gap-2 mb-1">
                <span class="fs-5 fw-bold">{{ formatRM(car.price) }}</span>
                <s v-if="car.change.direction !== 'same'" class="text-muted small">
                  {{ formatRM(car.price_when_added) }}
                </s>
              </div>
              <p class="small mb-3">
                <span v-if="car.change.direction === 'down'" class="text-success fw-semibold">
                  {{ formatRM(car.change.amount) }} cheaper since you saved it
                </span>
                <span v-else-if="car.change.direction === 'up'" class="text-danger">
                  {{ formatRM(car.change.amount) }} more than when you saved it
                </span>
                <span v-else class="text-muted">No change yet</span>
              </p>

              <!-- Target -->
              <div class="mt-auto">
                <form
                  v-if="editingId === car.listing_id"
                  class="mb-2"
                  novalidate
                  @submit.prevent="saveTarget"
                >
                  <label :for="`wl-target-${car.listing_id}`" class="form-label small mb-1">
                    Target price (empty = any drop)
                  </label>
                  <div class="input-group input-group-sm has-validation">
                    <span class="input-group-text">RM</span>
                    <input
                      :id="`wl-target-${car.listing_id}`"
                      v-model="targetInput"
                      type="number"
                      min="1"
                      step="500"
                      class="form-control"
                      :class="{ 'is-invalid': targetTouched && targetError }"
                      @blur="targetTouched = true"
                    />
                    <button type="submit" class="btn btn-primary" :disabled="savingTarget">
                      Save
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="editingId = null"
                    >
                      Cancel
                    </button>
                    <div class="invalid-feedback">{{ targetError }}</div>
                  </div>
                </form>

                <template v-else-if="car.target_price">
                  <div class="d-flex justify-content-between small mb-1">
                    <span>
                      Target {{ formatRM(car.target_price) }}
                      <span v-if="car.targetReached" class="badge bg-success ms-1">Reached!</span>
                    </span>
                    <span class="text-muted">
                      {{ targetProgress(car.price_when_added, car.price, car.target_price) }}%
                    </span>
                  </div>
                  <div
                    class="progress mb-2"
                    style="height: 6px"
                    role="progressbar"
                    :aria-label="`Progress towards target price ${formatRM(car.target_price)}`"
                    :aria-valuenow="
                      targetProgress(car.price_when_added, car.price, car.target_price)
                    "
                    aria-valuemin="0"
                    aria-valuemax="100"
                  >
                    <div
                      class="progress-bar"
                      :class="car.targetReached ? 'bg-success' : 'bg-primary'"
                      :style="{
                        width:
                          targetProgress(car.price_when_added, car.price, car.target_price) + '%'
                      }"
                    ></div>
                  </div>
                </template>
                <p v-else class="small text-muted mb-2">Alert on any price drop</p>

                <div class="d-flex justify-content-between">
                  <button
                    v-if="editingId !== car.listing_id"
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="startEditTarget(car)"
                  >
                    {{ car.target_price ? 'Change target' : 'Set target' }}
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-link text-danger ms-auto"
                    @click="remove(car)"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </article>
        </div>
      </TransitionGroup>

      <Pagination v-model:page="page" :total="filteredCars.length" :per-page="PER_PAGE" />
    </template>
  </section>
</template>

<style scoped>
.summary-card {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  height: 100%;
  font-size: 0.875rem;
  color: var(--bs-secondary-color);
}
.summary-number {
  display: block;
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--bs-body-color);
}
.watch-img {
  height: 180px;
  object-fit: cover;
}
.watch-list-enter-active,
.watch-list-leave-active {
  transition: all 0.3s ease;
}
.watch-list-enter-from,
.watch-list-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
