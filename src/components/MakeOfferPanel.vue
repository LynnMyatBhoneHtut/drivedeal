<script setup>
// "Make an offer" card on the Car detail page (Lynn's individual feature).
//   Guest        -> sign in to make an offer
//   Seller       -> how many offers this car has, with a link to My listings
//   Buyer        -> the negotiation if one is going on, otherwise the first-offer form
// Usage: <MakeOfferPanel :car="car" @car-status="(status) => (car.status = status)" />
import { computed, reactive, ref, watch } from 'vue'
import AppIcon from './AppIcon.vue'
import OfferInbox from './OfferInbox.vue'
import { currentUser, getUser } from '../services/auth'
import { fetchOffers, startOffer } from '../services/offersApi'
import {
  MESSAGE_MAX,
  TEST_DRIVE_RANGE,
  formatRM,
  gapPercent,
  isWithinRange,
  minimumOffer,
  quickOffers,
  statusLabel,
  validateFirstOffer
} from '../services/offerRules'

const props = defineProps({
  car: { type: Object, required: true }
})
const emit = defineEmits(['car-status'])

const offers = ref([])
const loading = ref(false)
const loadError = ref('')

const isSeller = computed(
  () =>
    Boolean(currentUser.value) && Number(currentUser.value.user_id) === Number(props.car.seller_id)
)

// The buyer's live negotiation (open or agreed), and their last ended one
const activeOffer = computed(() =>
  offers.value.find((o) => o.status === 'open' || o.status === 'accepted')
)
const lastEnded = computed(() =>
  offers.value.find((o) => o.status !== 'open' && o.status !== 'accepted')
)
const openCount = computed(() => offers.value.filter((o) => o.status === 'open').length)
const waitingOnSeller = computed(
  () => offers.value.filter((o) => o.status === 'open' && o.summary.turn === 'seller').length
)

async function load() {
  offers.value = []
  loadError.value = ''
  const user = currentUser.value
  if (!user) return
  loading.value = true
  try {
    offers.value = await fetchOffers({ listing_id: props.car.listing_id, user_id: user.user_id })
  } catch (e) {
    loadError.value = e.message
  } finally {
    loading.value = false
  }
}

watch(() => [props.car.listing_id, currentUser.value && currentUser.value.user_id], load, {
  immediate: true
})

// ---- First offer ----
const form = reactive({ amount: '', message: '' })
const touched = ref(false)
const sending = ref(false)
const sendError = ref('')

const quick = computed(() => quickOffers(props.car.price))
const amountError = computed(() => validateFirstOffer(form.amount, props.car.price))
const previewGap = computed(() =>
  amountError.value ? null : gapPercent(Number(form.amount), props.car.price)
)

function pick(amount) {
  form.amount = amount
  touched.value = true
}

async function send() {
  touched.value = true
  sendError.value = ''
  if (amountError.value) return
  const user = getUser()
  if (!user) return
  sending.value = true
  try {
    const offer = await startOffer({
      listingId: props.car.listing_id,
      buyerId: user.user_id,
      amount: form.amount,
      message: form.message.trim()
    })
    offers.value = [offer, ...offers.value]
    form.amount = ''
    form.message = ''
    touched.value = false
  } catch (e) {
    sendError.value = e.message
  } finally {
    sending.value = false
  }
}

function onUpdated(offer) {
  const index = offers.value.findIndex((o) => o.offer_id === offer.offer_id)
  if (index !== -1) offers.value.splice(index, 1, offer)
  if (offer.car_status !== props.car.status) emit('car-status', offer.car_status)
}
</script>

<template>
  <div class="dd-card dd-card-body offer-panel">
    <h2 class="h3 mb-1 d-flex align-items-center gap-2">
      <AppIcon name="tag" :size="20" /> Make an offer
    </h2>

    <!-- Guest -->
    <template v-if="!currentUser">
      <p class="text-muted mb-3">
        Think the price could be better? Sign in to send the seller an offer and negotiate.
      </p>
      <router-link
        :to="{ name: 'login', query: { redirect: `/cars/${car.listing_id}` } }"
        class="btn btn-outline-primary w-100"
      >
        Sign in to make an offer
      </router-link>
    </template>

    <!-- Seller of this car -->
    <template v-else-if="isSeller">
      <div v-if="loading" class="dd-skeleton mt-2" style="height: 48px"></div>
      <template v-else>
        <p class="text-muted mb-3">
          <template v-if="openCount">
            {{ openCount }} open {{ openCount === 1 ? 'negotiation' : 'negotiations' }} on this
            car<template v-if="waitingOnSeller"
              >, {{ waitingOnSeller }} waiting for your reply</template
            >.
          </template>
          <template v-else>No open offers on this car yet.</template>
        </p>
        <router-link
          :to="{ name: 'my-listings', hash: '#offers' }"
          class="btn btn-outline-primary w-100"
        >
          Manage offers in My listings
        </router-link>
      </template>
    </template>

    <!-- Buyer -->
    <template v-else>
      <div v-if="loading" class="dd-skeleton mt-2" style="height: 160px" aria-busy="true"></div>

      <div v-else-if="loadError" class="alert alert-danger py-2 mb-0" role="alert">
        {{ loadError }}
        <button type="button" class="btn btn-link btn-sm p-0 ms-1" @click="load">Try again</button>
      </div>

      <OfferInbox
        v-else-if="activeOffer"
        :offers="[activeOffer]"
        role="buyer"
        class="mt-3"
        @updated="onUpdated"
      />

      <p v-else-if="car.status !== 'available'" class="text-muted mb-0">
        This car is {{ car.status }}, so it isn't taking offers.
      </p>

      <form v-else class="mt-2" novalidate @submit.prevent="send">
        <p class="text-muted small mb-3">
          Asking price <strong class="text-body">{{ formatRM(car.price) }}</strong
          >. Get within {{ TEST_DRIVE_RANGE }}% of the seller's price to unlock a test drive.
        </p>

        <div v-if="lastEnded" class="alert alert-info py-2 small" role="status">
          Your last offer ({{ formatRM(lastEnded.summary.buyer_amount) }}) was
          {{ statusLabel(lastEnded.status).toLowerCase() }}. You can make a new one.
        </div>

        <div v-if="quick.length" class="d-flex flex-wrap gap-2 mb-3" aria-label="Quick offers">
          <button
            v-for="option in quick"
            :key="option.ratio"
            type="button"
            class="dd-chip"
            :class="{ 'dd-chip-active': Number(form.amount) === option.amount }"
            @click="pick(option.amount)"
          >
            {{ formatRM(option.amount) }}
            <span class="text-muted small">-{{ Math.round((1 - option.ratio) * 100) }}%</span>
          </button>
        </div>

        <label for="offerAmount" class="form-label">Your offer (RM)</label>
        <input
          id="offerAmount"
          v-model="form.amount"
          type="number"
          inputmode="numeric"
          step="100"
          :min="minimumOffer(car.price)"
          :max="car.price"
          class="form-control"
          :class="{ 'is-invalid': touched && amountError }"
          placeholder="e.g. 45000"
          @blur="touched = true"
        />
        <div class="invalid-feedback">{{ amountError }}</div>
        <p v-if="previewGap !== null" class="form-text mb-0">
          {{ previewGap }}% below the asking price<span v-if="isWithinRange(previewGap)">
            - close enough to request a test drive straight away</span
          >.
        </p>

        <label for="offerMessage" class="form-label mt-3">
          Message <span class="text-muted small">(optional)</span>
        </label>
        <textarea
          id="offerMessage"
          v-model="form.message"
          class="form-control"
          rows="2"
          :maxlength="MESSAGE_MAX"
          placeholder="Tell the seller a little about your offer"
        ></textarea>

        <div v-if="sendError" class="alert alert-danger py-2 mt-3 mb-0" role="alert">
          {{ sendError }}
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3" :disabled="sending">
          <span v-if="sending" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
          <AppIcon v-else name="tag" :size="18" />
          {{ sending ? 'Sending...' : 'Send offer' }}
        </button>
      </form>
    </template>
  </div>
</template>

<style scoped>
/* The negotiation card sits inside this card, so drop its own frame */
.offer-panel :deep(.offer-thread) {
  padding: 0;
  border: 0;
  box-shadow: none;
  background: transparent;
}
</style>
