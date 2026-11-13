<script setup>
// One negotiation, seen by the buyer or the seller (Lynn's individual feature).
// Shows the price board with the 10% gap meter, the full history, the actions for whoever's
// turn it is, and the test drive card. It only shows things and emits what the user chose;
// the parent (OfferInbox) calls the API.
//
// Usage: <OfferThread :offer="offer" role="buyer" :busy="false" error="" show-car
//          @respond="({ action, amount, message }) => ..."
//          @request-test-drive="({ date, time, note }) => ..."
//          @test-drive="({ testDriveId, action }) => ..." />
import { computed, reactive, ref, watch } from 'vue'
import AppIcon from './AppIcon.vue'
import { imageUrl } from '../services/api'
import {
  MESSAGE_MAX,
  NOTE_MAX,
  TEST_DRIVE_RANGE,
  TIME_SLOTS,
  describeEvent,
  formatRM,
  statusLabel,
  suggestedCounter,
  summarise,
  testDriveDateRange,
  validateCounter,
  validateTestDrive
} from '../services/offerRules'

const props = defineProps({
  offer: { type: Object, required: true },
  role: { type: String, required: true }, // 'buyer' | 'seller'
  busy: { type: Boolean, default: false },
  error: { type: String, default: '' },
  showCar: { type: Boolean, default: false }
})
const emit = defineEmits(['respond', 'request-test-drive', 'test-drive'])

const other = computed(() => (props.role === 'buyer' ? 'seller' : 'buyer'))
const otherName = computed(() =>
  props.role === 'buyer' ? props.offer.seller_name : props.offer.buyer_name
)

// Worked out on the page from the history (same rules as the server's summary)
const summary = computed(() =>
  summarise(props.offer.events, props.offer.asking_price, props.offer.status)
)
const isOpen = computed(() => props.offer.status === 'open')
const myTurn = computed(() => isOpen.value && summary.value.turn === props.role)
const carAvailable = computed(() => props.offer.car_status === 'available')
const canMovePrice = computed(() => myTurn.value && carAvailable.value)

// Price the user would accept: the other side's latest
const acceptAmount = computed(() =>
  props.role === 'buyer' ? summary.value.sellerAmount : summary.value.buyerAmount
)

const statusClass = computed(() => {
  if (props.offer.status === 'open') return 'status-open'
  if (props.offer.status === 'accepted') return 'dd-status-available'
  return 'dd-status-sold'
})

// Gap meter: 0% apart = full bar. The scale runs to 30% apart; the marker is at 10%.
const METER_SCALE = 30
const meterFill = computed(() => {
  const gap = summary.value.gapPercent
  if (gap === null) return 0
  return Math.max(4, (1 - Math.min(gap, METER_SCALE) / METER_SCALE) * 100)
})
const meterMarker = `${(1 - TEST_DRIVE_RANGE / METER_SCALE) * 100}%`

function formatDateTime(value) {
  const date = new Date(String(value || '').replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' })
}

function formatDay(value) {
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-MY', { weekday: 'short', day: 'numeric', month: 'short' })
}

// ---- Counter / accept / end ----
const counter = reactive({ amount: '', message: '' })
const counterTouched = ref(false)
const confirming = ref('') // 'accept' | 'end'

function resetForms() {
  counter.amount = suggestedCounter(props.role, summary.value)
  counter.message = ''
  counterTouched.value = false
  confirming.value = ''
}

// Start each new turn with a fresh suggestion
watch(() => [props.offer.offer_id, props.offer.events.length, props.offer.status], resetForms, {
  immediate: true
})

const counterError = computed(() => validateCounter(props.role, counter.amount, summary.value))
const showCounterError = computed(() => counterTouched.value && Boolean(counterError.value))

function sendCounter() {
  counterTouched.value = true
  if (counterError.value) return
  emit('respond', {
    action: 'counter',
    amount: Number(counter.amount),
    message: counter.message.trim()
  })
}

function confirmAccept() {
  emit('respond', { action: 'accept', message: counter.message.trim() })
}

const endAction = computed(() => (props.role === 'seller' ? 'reject' : 'withdraw'))
const endLabel = computed(() => (props.role === 'seller' ? 'Reject offer' : 'Withdraw offer'))

function confirmEnd() {
  emit('respond', { action: endAction.value, message: counter.message.trim() })
}

// ---- Test drive ----
const drive = computed(() => props.offer.test_drive)
const driveActive = computed(
  () => drive.value && (drive.value.status === 'requested' || drive.value.status === 'confirmed')
)
const canRequestDrive = computed(
  () =>
    props.role === 'buyer' &&
    props.offer.test_drive_unlocked &&
    !driveActive.value &&
    props.offer.car_status !== 'sold' &&
    (props.offer.status === 'open' || props.offer.status === 'accepted')
)
const dateRange = testDriveDateRange()
const driveForm = reactive({ date: '', time: '10:00', note: '' })
const driveTouched = ref(false)
const driveError = computed(() => validateTestDrive(driveForm))

function sendDriveRequest() {
  driveTouched.value = true
  if (driveError.value) return
  emit('request-test-drive', {
    date: driveForm.date,
    time: driveForm.time,
    note: driveForm.note.trim()
  })
}

watch(
  () => drive.value && drive.value.test_drive_id,
  () => {
    driveForm.date = ''
    driveForm.time = '10:00'
    driveForm.note = ''
    driveTouched.value = false
  }
)

const DRIVE_LABELS = {
  requested: 'Waiting for the seller',
  confirmed: 'Confirmed',
  declined: 'Declined',
  cancelled: 'Cancelled'
}
</script>

<template>
  <article
    class="offer-thread dd-card"
    :class="{ 'is-ended': !isOpen && offer.status !== 'accepted' }"
  >
    <!-- Car (list pages) -->
    <header v-if="showCar" class="thread-car">
      <router-link
        :to="{ name: 'car-detail', params: { id: offer.listing_id } }"
        class="thread-thumb"
        :aria-label="`${offer.year} ${offer.make} ${offer.model}`"
      >
        <img
          v-if="offer.image_path"
          :src="imageUrl(offer.image_path)"
          :alt="`${offer.make} ${offer.model} photo`"
          loading="lazy"
        />
        <AppIcon v-else name="car" :size="28" />
      </router-link>
      <div class="min-w-0 flex-grow-1">
        <router-link
          :to="{ name: 'car-detail', params: { id: offer.listing_id } }"
          class="thread-title"
        >
          {{ offer.year }} {{ offer.make }} {{ offer.model }}
        </router-link>
        <p class="small text-muted mb-0">
          Asking {{ formatRM(offer.asking_price) }} &middot;
          {{ role === 'buyer' ? 'Seller' : 'Buyer' }}: {{ otherName }}
        </p>
      </div>
      <span class="dd-status" :class="statusClass">{{ statusLabel(offer.status) }}</span>
    </header>
    <div v-else class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <p class="small text-muted mb-0">
        Negotiating with <strong class="text-body">{{ otherName }}</strong>
      </p>
      <span class="dd-status" :class="statusClass">{{ statusLabel(offer.status) }}</span>
    </div>

    <!-- Price board -->
    <div class="price-board">
      <div class="price-cell">
        <span class="price-label">Asking</span>
        <span class="price-value muted">{{ formatRM(offer.asking_price) }}</span>
      </div>
      <div class="price-cell">
        <span class="price-label">{{ role === 'buyer' ? 'Your offer' : 'Buyer offers' }}</span>
        <span class="price-value">{{ formatRM(summary.buyerAmount) }}</span>
      </div>
      <div class="price-cell">
        <span class="price-label">{{ role === 'seller' ? 'Your price' : 'Seller wants' }}</span>
        <span class="price-value">{{ formatRM(summary.sellerAmount) }}</span>
      </div>
    </div>

    <div v-if="offer.status === 'accepted'" class="deal-banner" role="status">
      <AppIcon name="check" :size="20" :stroke-width="2.25" />
      <div>
        <strong>Deal agreed at {{ formatRM(offer.agreed_price) }}.</strong>
        {{
          role === 'buyer'
            ? 'The car is reserved for you.'
            : 'Your car is now reserved for this buyer.'
        }}
      </div>
    </div>

    <div v-else-if="isOpen" class="gap-meter">
      <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
        <span class="small fw-semibold">
          {{ summary.gapPercent === 0 ? 'Same price' : `${summary.gapPercent}% apart` }}
        </span>
        <span
          class="small"
          :class="summary.withinRange ? 'text-success fw-semibold' : 'text-muted'"
        >
          <AppIcon :name="summary.withinRange ? 'check' : 'clock'" :size="14" />
          {{
            summary.withinRange
              ? 'Within 10%: test drive unlocked'
              : 'Test drive unlocks within 10%'
          }}
        </span>
      </div>
      <div
        class="meter"
        role="meter"
        aria-label="How close the offer is to the seller's price"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuenow="Math.round(meterFill)"
        :aria-valuetext="`${summary.gapPercent}% apart`"
      >
        <span
          class="meter-fill"
          :class="{ 'is-close': summary.withinRange }"
          :style="{ width: `${meterFill}%` }"
        ></span>
        <span class="meter-marker" :style="{ left: meterMarker }" aria-hidden="true"></span>
      </div>
    </div>

    <!-- History -->
    <details class="history" :open="!showCar || isOpen">
      <summary>Negotiation history ({{ offer.events.length }})</summary>
      <ol class="history-list">
        <li
          v-for="event in offer.events"
          :key="event.event_id"
          :class="`by-${event.actor === role ? 'me' : event.actor === 'system' ? 'system' : 'them'}`"
        >
          <p class="history-line">{{ describeEvent(event, role) }}</p>
          <p v-if="event.message" class="history-message">“{{ event.message }}”</p>
          <time class="history-time" :datetime="event.created_at">{{
            formatDateTime(event.created_at)
          }}</time>
        </li>
      </ol>
    </details>

    <!-- Actions -->
    <div v-if="error" class="alert alert-danger py-2 mb-3" role="alert">{{ error }}</div>

    <template v-if="isOpen">
      <div v-if="!carAvailable" class="alert alert-info py-2 mb-3" role="status">
        This car is no longer available, so the price can't change.
      </div>

      <div v-else-if="!myTurn" class="waiting" role="status">
        <AppIcon name="clock" :size="18" />
        Waiting for the {{ other }} to reply.
      </div>

      <div v-if="canMovePrice" class="respond">
        <p class="fw-semibold mb-2">Your move</p>
        <div class="row g-2 align-items-start">
          <div class="col-12 col-sm-6">
            <label :for="`counter-${offer.offer_id}`" class="form-label small"
              >Counter-offer (RM)</label
            >
            <input
              :id="`counter-${offer.offer_id}`"
              v-model="counter.amount"
              type="number"
              inputmode="numeric"
              step="100"
              class="form-control"
              :class="{ 'is-invalid': showCounterError }"
              :disabled="busy"
              @blur="counterTouched = true"
            />
            <div class="invalid-feedback">{{ counterError }}</div>
          </div>
          <div class="col-12 col-sm-6">
            <label :for="`counter-msg-${offer.offer_id}`" class="form-label small">
              Message <span class="text-muted">(optional)</span>
            </label>
            <input
              :id="`counter-msg-${offer.offer_id}`"
              v-model="counter.message"
              type="text"
              class="form-control"
              :maxlength="MESSAGE_MAX"
              :disabled="busy"
              placeholder="Add a short note"
            />
          </div>
        </div>

        <div v-if="confirming === 'accept'" class="confirm-box" role="alert">
          <p class="mb-2">
            Accept <strong>{{ formatRM(acceptAmount) }}</strong
            >? The deal is agreed and the car is reserved{{
              role === 'buyer' ? ' for you' : ' for this buyer'
            }}. Other negotiations on this car are closed.
          </p>
          <div class="d-flex flex-wrap gap-2">
            <button
              type="button"
              class="btn btn-primary btn-sm"
              :disabled="busy"
              @click="confirmAccept"
            >
              <span v-if="busy" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
              Yes, accept {{ formatRM(acceptAmount) }}
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              :disabled="busy"
              @click="confirming = ''"
            >
              Cancel
            </button>
          </div>
        </div>

        <div v-else class="d-flex flex-wrap gap-2 mt-3">
          <button type="button" class="btn btn-primary" :disabled="busy" @click="sendCounter">
            <span v-if="busy" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <AppIcon v-else name="arrow-right" :size="18" />
            Send counter-offer
          </button>
          <button
            type="button"
            class="btn btn-outline-primary"
            :disabled="busy"
            @click="confirming = 'accept'"
          >
            <AppIcon name="check" :size="18" /> Accept {{ formatRM(acceptAmount) }}
          </button>
        </div>
      </div>

      <div v-if="confirming === 'end'" class="confirm-box mt-3" role="alert">
        <p class="mb-2">
          {{
            role === 'seller'
              ? 'Reject this offer? The negotiation ends.'
              : 'Withdraw your offer? The negotiation ends.'
          }}
        </p>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-danger btn-sm" :disabled="busy" @click="confirmEnd">
            Yes, {{ role === 'seller' ? 'reject' : 'withdraw' }}
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            :disabled="busy"
            @click="confirming = ''"
          >
            Keep negotiating
          </button>
        </div>
      </div>
      <button
        v-else-if="confirming !== 'accept'"
        type="button"
        class="btn btn-link btn-sm text-danger px-0 mt-2"
        :disabled="busy"
        @click="confirming = 'end'"
      >
        {{ endLabel }}
      </button>
    </template>

    <!-- Test drive -->
    <section class="test-drive" :aria-label="`Test drive for offer ${offer.offer_id}`">
      <h4 class="test-drive-title"><AppIcon name="calendar" :size="18" /> Test drive</h4>

      <!-- An existing request -->
      <div v-if="drive" class="drive-card" :class="`drive-${drive.status}`">
        <div class="d-flex flex-wrap justify-content-between gap-2">
          <div>
            <p class="fw-semibold mb-0">
              {{ formatDay(drive.preferred_date) }} at {{ drive.preferred_time }}
            </p>
            <p v-if="drive.note" class="small text-muted mb-0">“{{ drive.note }}”</p>
          </div>
          <span class="drive-status">{{ DRIVE_LABELS[drive.status] || drive.status }}</span>
        </div>

        <div
          v-if="role === 'seller' && drive.status === 'requested'"
          class="d-flex flex-wrap gap-2 mt-2"
        >
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="busy"
            @click="emit('test-drive', { testDriveId: drive.test_drive_id, action: 'confirm' })"
          >
            <AppIcon name="check" :size="16" /> Confirm
          </button>
          <button
            type="button"
            class="btn btn-outline-danger btn-sm"
            :disabled="busy"
            @click="emit('test-drive', { testDriveId: drive.test_drive_id, action: 'decline' })"
          >
            Decline
          </button>
        </div>
        <button
          v-if="role === 'buyer' && driveActive"
          type="button"
          class="btn btn-link btn-sm text-danger px-0 mt-1"
          :disabled="busy"
          @click="emit('test-drive', { testDriveId: drive.test_drive_id, action: 'cancel' })"
        >
          Cancel test drive
        </button>

        <p v-if="drive.status === 'confirmed'" class="small mb-0 mt-2">
          <template v-if="role === 'buyer'">
            Contact the seller:
            <a v-if="offer.seller_phone" :href="`tel:${offer.seller_phone.replace(/\s+/g, '')}`">{{
              offer.seller_phone
            }}</a>
            <template v-if="offer.seller_phone && offer.seller_email"> &middot; </template>
            <a v-if="offer.seller_email" :href="`mailto:${offer.seller_email}`">{{
              offer.seller_email
            }}</a>
          </template>
          <template v-else>
            Contact the buyer:
            <a v-if="offer.buyer_phone" :href="`tel:${offer.buyer_phone.replace(/\s+/g, '')}`">{{
              offer.buyer_phone
            }}</a>
            <template v-if="offer.buyer_phone && offer.buyer_email"> &middot; </template>
            <a :href="`mailto:${offer.buyer_email}`">{{ offer.buyer_email }}</a>
          </template>
        </p>
      </div>

      <!-- Buyer: request form -->
      <form v-if="canRequestDrive" class="drive-form" novalidate @submit.prevent="sendDriveRequest">
        <p class="small text-muted mb-2">
          {{
            drive
              ? 'Pick another time:'
              : "You're close on price - book a viewing before you decide."
          }}
        </p>
        <div class="row g-2">
          <div class="col-12 col-sm-5">
            <label :for="`drive-date-${offer.offer_id}`" class="form-label small">Date</label>
            <input
              :id="`drive-date-${offer.offer_id}`"
              v-model="driveForm.date"
              type="date"
              class="form-control"
              :min="dateRange.min"
              :max="dateRange.max"
              :disabled="busy"
            />
          </div>
          <div class="col-12 col-sm-3">
            <label :for="`drive-time-${offer.offer_id}`" class="form-label small">Time</label>
            <select
              :id="`drive-time-${offer.offer_id}`"
              v-model="driveForm.time"
              class="form-select"
              :disabled="busy"
            >
              <option v-for="slot in TIME_SLOTS" :key="slot" :value="slot">{{ slot }}</option>
            </select>
          </div>
          <div class="col-12 col-sm-4">
            <label :for="`drive-note-${offer.offer_id}`" class="form-label small">
              Note <span class="text-muted">(optional)</span>
            </label>
            <input
              :id="`drive-note-${offer.offer_id}`"
              v-model="driveForm.note"
              type="text"
              class="form-control"
              :maxlength="NOTE_MAX"
              :disabled="busy"
              placeholder="e.g. Meeting place"
            />
          </div>
        </div>
        <p v-if="driveTouched && driveError" class="small text-danger mt-2 mb-0" role="alert">
          {{ driveError }}
        </p>
        <button type="submit" class="btn btn-primary btn-sm mt-2" :disabled="busy">
          <AppIcon name="calendar" :size="16" /> Request test drive
        </button>
      </form>

      <!-- Locked / nothing yet -->
      <p v-else-if="!drive" class="small text-muted mb-0 d-flex align-items-center gap-2">
        <template v-if="role === 'buyer' && isOpen && !offer.test_drive_unlocked">
          <AppIcon name="shield-check" :size="16" />
          Get within {{ TEST_DRIVE_RANGE }}% of the seller's price to unlock a test drive request.
        </template>
        <template v-else-if="role === 'seller'">
          {{
            offer.test_drive_unlocked
              ? 'The buyer can now request a test drive.'
              : `The buyer can request a test drive once you are within ${TEST_DRIVE_RANGE}% on price.`
          }}
        </template>
        <template v-else>No test drive was booked.</template>
      </p>
    </section>
  </article>
</template>

<style scoped>
.offer-thread {
  padding: 1.25rem;
}

.offer-thread.is-ended {
  background: var(--dd-surface);
}

.thread-car {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1rem;
}

.thread-thumb {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 72px;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
  color: var(--dd-muted);
}

.thread-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thread-title {
  display: block;
  color: var(--dd-ink);
  font-weight: 700;
  text-decoration: none;
}

.thread-title:hover {
  color: var(--dd-primary);
}

.status-open {
  background: var(--dd-primary-50);
  color: var(--dd-primary-700);
}

/* Price board */
.price-board {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
  margin-bottom: 0.9rem;
}

.price-cell {
  padding: 0.6rem 0.7rem;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
  min-width: 0;
}

.offer-thread.is-ended .price-cell {
  background: var(--dd-white);
}

.price-label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--dd-muted);
}

.price-value {
  display: block;
  font-size: 1rem;
  font-weight: 800;
  color: var(--dd-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.price-value.muted {
  color: var(--dd-ink);
}

/* Gap meter */
.gap-meter {
  margin-bottom: 0.9rem;
}

.meter {
  position: relative;
  height: 10px;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-primary-50);
}

.meter-fill {
  position: absolute;
  inset: 0 auto 0 0;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-primary-300);
  transition: width 0.3s ease;
}

.meter-fill.is-close {
  background: var(--dd-success);
}

.meter-marker {
  position: absolute;
  top: -4px;
  bottom: -4px;
  width: 2px;
  border-radius: 2px;
  background: var(--dd-ink);
}

.deal-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin-bottom: 0.9rem;
  padding: 0.75rem 0.9rem;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-success-50);
  color: var(--dd-ink);
}

.deal-banner :deep(svg) {
  flex-shrink: 0;
  margin-top: 2px;
  color: var(--dd-success);
}

/* History */
.history {
  margin-bottom: 0.9rem;
}

.history summary {
  color: var(--dd-primary);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
}

.history-list {
  position: relative;
  margin: 0.75rem 0 0;
  padding: 0 0 0 1.25rem;
  list-style: none;
}

.history-list::before {
  content: '';
  position: absolute;
  top: 0.4rem;
  bottom: 0.4rem;
  left: 4px;
  width: 1px;
  background: var(--dd-primary-100);
}

.history-list li {
  position: relative;
  padding-bottom: 0.7rem;
}

.history-list li:last-child {
  padding-bottom: 0;
}

.history-list li::before {
  content: '';
  position: absolute;
  top: 0.4rem;
  left: -1.25rem;
  width: 9px;
  height: 9px;
  border: 2px solid var(--dd-primary);
  border-radius: 50%;
  background: var(--dd-white);
}

.history-list li.by-me::before {
  background: var(--dd-primary);
}

.history-list li.by-system::before {
  border-color: var(--dd-muted);
}

.history-line {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--dd-ink);
}

.history-message {
  margin: 0.1rem 0 0;
  font-size: 0.875rem;
  color: var(--dd-body);
  overflow-wrap: anywhere;
}

.history-time {
  font-size: 0.75rem;
  color: var(--dd-muted);
}

/* Actions */
.waiting {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 0.8rem;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-primary-50);
  color: var(--dd-primary-700);
  font-size: 0.9rem;
  font-weight: 600;
}

.respond {
  padding: 1rem;
  border: 1px solid var(--dd-primary-100);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-white);
}

.confirm-box {
  margin-top: 0.75rem;
  padding: 0.75rem 0.9rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
  font-size: 0.9rem;
}

/* Test drive */
.test-drive {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--dd-line);
}

.test-drive-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.6rem;
  font-size: 0.95rem;
  font-weight: 700;
}

.drive-card {
  padding: 0.7rem 0.85rem;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-surface);
  margin-bottom: 0.6rem;
}

.drive-status {
  align-self: flex-start;
  padding: 0.2rem 0.6rem;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-white);
  color: var(--dd-muted);
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.drive-requested .drive-status {
  background: var(--dd-warning-50);
  color: var(--dd-warning-text);
}

.drive-confirmed {
  background: var(--dd-success-50);
}

.drive-confirmed .drive-status {
  color: var(--dd-success);
}

.min-w-0 {
  min-width: 0;
}

@media (max-width: 400px) {
  .price-value {
    font-size: 0.9rem;
  }
}
</style>
