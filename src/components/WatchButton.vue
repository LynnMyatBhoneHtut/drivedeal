<script setup>
// "Watch this price" panel for the Car detail page. OWNER: Yati (D/HD feature).
// Usage (CarDetailView): <WatchButton :listing-id="car.listing_id" :price="car.price" :seller-id="car.seller_id" />
//
// - Guests are sent to log in first (and come back here).
// - Sellers don't see it on their own cars.
// - Optional target price: "tell me when it goes below RM 45,000", checked before saving.
// - Uses the Pinia store, so the bell and the My watchlist page update at the same time.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWatchlistStore } from '../stores/watchlist'
import { currentUser } from '../services/auth'
import { formatRM, priceChange, targetPriceError } from '../services/priceChange'

const props = defineProps({
  listingId: { type: [Number, String], required: true },
  price: { type: Number, required: true },
  sellerId: { type: Number, default: 0 }
})

const watchlist = useWatchlistStore()
const route = useRoute()
const router = useRouter()

const editing = ref(false)
const targetInput = ref('')
const touched = ref(false)
const busy = ref(false)
const errorMessage = ref('')

const isOwnCar = computed(
  () => Boolean(currentUser.value) && currentUser.value.user_id === props.sellerId
)
const watched = computed(() => watchlist.watchFor(props.listingId))
const change = computed(() =>
  watched.value ? priceChange(watched.value.price_when_added, props.price) : null
)
const targetError = computed(() => targetPriceError(targetInput.value, props.price))

function openForm() {
  if (!currentUser.value) {
    router.push({ name: 'login', query: { redirect: route.fullPath } })
    return
  }
  targetInput.value = watched.value && watched.value.target_price ? watched.value.target_price : ''
  touched.value = false
  errorMessage.value = ''
  editing.value = true
}

// Quick suggestions: 5% and 10% below the current price, rounded to RM 500
function suggestion(percent) {
  return Math.floor((props.price * (1 - percent / 100)) / 500) * 500
}

async function save() {
  touched.value = true
  if (targetError.value) return
  busy.value = true
  errorMessage.value = ''
  const target = targetInput.value === '' ? null : Number(targetInput.value)
  try {
    if (watched.value) {
      await watchlist.setTarget(Number(props.listingId), target)
    } else {
      await watchlist.watch({ listing_id: Number(props.listingId) }, target)
    }
    editing.value = false
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    busy.value = false
  }
}

async function stopWatching() {
  busy.value = true
  errorMessage.value = ''
  try {
    await watchlist.unwatch(props.listingId)
    editing.value = false
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div v-if="!isOwnCar" class="watch-button">
    <!-- Not watching yet -->
    <button
      v-if="!watched && !editing"
      type="button"
      class="btn btn-outline-primary w-100"
      @click="openForm"
    >
      <span aria-hidden="true">&#128276;</span> Watch price
    </button>

    <!-- Watching -->
    <div v-else-if="watched && !editing" class="border rounded p-3 bg-light">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div>
          <span class="badge bg-primary mb-1">&#128065; Watching</span>
          <p class="small mb-1">
            Saved at {{ formatRM(watched.price_when_added) }}
            <span v-if="change.direction === 'down'" class="text-success fw-semibold">
              &middot; now {{ formatRM(change.amount) }} cheaper ({{ change.percent }}%)
            </span>
            <span v-else-if="change.direction === 'up'" class="text-danger">
              &middot; now {{ formatRM(change.amount) }} higher
            </span>
          </p>
          <p class="small text-muted mb-0">
            <template v-if="watched.target_price">
              Alert me below <strong>{{ formatRM(watched.target_price) }}</strong>
            </template>
            <template v-else>You'll get an alert on any price drop.</template>
          </p>
        </div>
      </div>
      <div class="d-flex gap-2 mt-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="busy"
          @click="openForm"
        >
          {{ watched.target_price ? 'Change target' : 'Set target price' }}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-link text-danger"
          :disabled="busy"
          @click="stopWatching"
        >
          Stop watching
        </button>
      </div>
    </div>

    <!-- Choose a target price -->
    <form v-else class="border rounded p-3" novalidate @submit.prevent="save">
      <label :for="`target-${listingId}`" class="form-label small fw-semibold">
        Target price <span class="text-muted fw-normal">(optional)</span>
      </label>
      <div class="input-group input-group-sm has-validation">
        <span class="input-group-text">RM</span>
        <input
          :id="`target-${listingId}`"
          v-model="targetInput"
          type="number"
          min="1"
          step="500"
          class="form-control"
          :class="{ 'is-invalid': touched && targetError }"
          :placeholder="`e.g. ${suggestion(5)}`"
          @blur="touched = true"
        />
        <div class="invalid-feedback">{{ targetError }}</div>
      </div>
      <div class="d-flex flex-wrap gap-1 mt-2">
        <button
          v-for="percent in [5, 10]"
          :key="percent"
          type="button"
          class="btn btn-sm btn-outline-secondary py-0"
          @click="targetInput = suggestion(percent)"
        >
          -{{ percent }}% ({{ formatRM(suggestion(percent)) }})
        </button>
      </div>
      <p class="form-text mb-2">Leave empty to be alerted on any price drop.</p>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary" :disabled="busy">
          {{ watched ? 'Save target' : 'Start watching' }}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="busy"
          @click="editing = false"
        >
          Cancel
        </button>
      </div>
    </form>

    <p v-if="errorMessage" class="small text-danger mt-2 mb-0" role="alert">{{ errorMessage }}</p>
  </div>
</template>
