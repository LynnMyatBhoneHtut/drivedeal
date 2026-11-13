<script setup>
// A list of negotiations that handles the API calls (Lynn's individual feature).
// Used on Car detail (one negotiation), My offers (buyer) and My listings (seller).
// Usage: <OfferInbox :offers="offers" role="seller" show-car @updated="onUpdated" />
// @updated(offer) fires with the server's new copy after every change.
import { reactive, ref, watch } from 'vue'
import OfferThread from './OfferThread.vue'
import { getUser } from '../services/auth'
import { requestTestDrive, respondToOffer, updateTestDrive } from '../services/offersApi'

const props = defineProps({
  offers: { type: Array, default: () => [] },
  role: { type: String, required: true }, // 'buyer' | 'seller'
  showCar: { type: Boolean, default: false }
})
const emit = defineEmits(['updated'])

const list = ref([])
watch(
  () => props.offers,
  (offers) => {
    list.value = [...offers]
  },
  { immediate: true }
)

const busyId = ref(null)
const errors = reactive({})

async function run(offer, call) {
  const user = getUser()
  if (!user) {
    errors[offer.offer_id] = 'Please sign in again.'
    return
  }
  busyId.value = offer.offer_id
  errors[offer.offer_id] = ''
  try {
    const updated = await call(user.user_id)
    const index = list.value.findIndex((item) => item.offer_id === updated.offer_id)
    if (index !== -1) list.value.splice(index, 1, updated)
    emit('updated', updated)
  } catch (e) {
    errors[offer.offer_id] = e.message
  } finally {
    busyId.value = null
  }
}

function respond(offer, { action, amount, message }) {
  run(offer, (userId) =>
    respondToOffer({ offerId: offer.offer_id, userId, action, amount, message })
  )
}

function askTestDrive(offer, { date, time, note }) {
  run(offer, (userId) =>
    requestTestDrive({ offerId: offer.offer_id, buyerId: userId, date, time, note })
  )
}

function changeTestDrive(offer, { testDriveId, action }) {
  run(offer, (userId) => updateTestDrive({ testDriveId, userId, action }))
}
</script>

<template>
  <div class="offer-inbox">
    <OfferThread
      v-for="offer in list"
      :key="offer.offer_id"
      :offer="offer"
      :role="role"
      :show-car="showCar"
      :busy="busyId === offer.offer_id"
      :error="errors[offer.offer_id] || ''"
      @respond="respond(offer, $event)"
      @request-test-drive="askTestDrive(offer, $event)"
      @test-drive="changeTestDrive(offer, $event)"
    />
  </div>
</template>

<style scoped>
.offer-inbox {
  display: grid;
  gap: 1rem;
}
</style>
