<script setup>
// My offers page - /offers (Lynn's individual feature)
// Every negotiation the logged-in buyer has started (offers.php?buyer_id=), with tabs for
// the ones still going and the ones that have ended.
import { computed, ref, watch } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import OfferInbox from '../components/OfferInbox.vue'
import PageHeader from '../components/PageHeader.vue'
import { currentUser } from '../services/auth'
import { fetchOffers } from '../services/offersApi'

const offers = ref([])
const loading = ref(false)
const errorMessage = ref('')
const tab = ref('active')

async function load(userId) {
  offers.value = []
  if (!userId) return
  loading.value = true
  errorMessage.value = ''
  try {
    offers.value = await fetchOffers({ buyer_id: userId })
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    loading.value = false
  }
}

watch(() => currentUser.value && currentUser.value.user_id, load, { immediate: true })

const isActive = (offer) => offer.status === 'open' || offer.status === 'accepted'
const activeOffers = computed(() => offers.value.filter(isActive))
const endedOffers = computed(() => offers.value.filter((offer) => !isActive(offer)))
const shown = computed(() => (tab.value === 'active' ? activeOffers.value : endedOffers.value))
const yourTurn = computed(
  () => offers.value.filter((o) => o.status === 'open' && o.summary.turn === 'buyer').length
)

function onUpdated(offer) {
  const index = offers.value.findIndex((o) => o.offer_id === offer.offer_id)
  if (index !== -1) offers.value.splice(index, 1, offer)
}
</script>

<template>
  <div>
    <PageHeader
      title="My offers"
      :crumbs="currentUser ? [{ label: 'Profile', to: { name: 'profile' } }] : []"
    >
      <template v-if="currentUser" #subtitle>
        Your price offers, the seller's replies and your test drive requests.
      </template>
    </PageHeader>

    <section class="container py-4 py-lg-5">
      <div v-if="!currentUser" class="dd-empty">
        <span class="dd-icon-circle"><AppIcon name="tag" :size="28" /></span>
        <h2>Please sign in to see your offers</h2>
        <router-link
          :to="{ name: 'login', query: { redirect: '/offers' } }"
          class="btn btn-primary mt-2"
        >
          Sign in
        </router-link>
      </div>

      <template v-else>
        <div v-if="loading" aria-busy="true">
          <div class="dd-skeleton mb-3" style="height: 44px; max-width: 320px"></div>
          <div class="dd-skeleton mb-3" style="height: 260px"></div>
          <span class="visually-hidden" role="status">Loading your offers...</span>
        </div>

        <div
          v-else-if="errorMessage"
          class="alert alert-danger d-flex flex-wrap align-items-center gap-3"
          role="alert"
        >
          <span>{{ errorMessage }}</span>
          <button
            type="button"
            class="btn btn-sm btn-outline-danger"
            @click="load(currentUser.user_id)"
          >
            Try again
          </button>
        </div>

        <div v-else-if="!offers.length" class="dd-empty">
          <span class="dd-icon-circle"><AppIcon name="tag" :size="28" /></span>
          <h2>No offers yet</h2>
          <p>Open a car you like and press "Send offer" to start negotiating with the seller.</p>
          <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
        </div>

        <template v-else>
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="offer-tabs" role="tablist" aria-label="Filter offers">
              <button
                type="button"
                role="tab"
                class="offer-tab"
                :class="{ active: tab === 'active' }"
                :aria-selected="tab === 'active'"
                @click="tab = 'active'"
              >
                Active <span class="tab-count">{{ activeOffers.length }}</span>
              </button>
              <button
                type="button"
                role="tab"
                class="offer-tab"
                :class="{ active: tab === 'ended' }"
                :aria-selected="tab === 'ended'"
                @click="tab = 'ended'"
              >
                Ended <span class="tab-count">{{ endedOffers.length }}</span>
              </button>
            </div>
            <p v-if="yourTurn" class="small fw-semibold text-primary mb-0" role="status">
              <AppIcon name="clock" :size="16" />
              {{ yourTurn }} {{ yourTurn === 1 ? 'negotiation is' : 'negotiations are' }} waiting
              for your reply
            </p>
          </div>

          <p v-if="!shown.length" class="text-muted">
            {{
              tab === 'active' ? 'No active negotiations right now.' : 'No ended negotiations yet.'
            }}
          </p>
          <div class="offers-wrap">
            <OfferInbox :key="tab" :offers="shown" role="buyer" show-car @updated="onUpdated" />
          </div>
        </template>
      </template>
    </section>
  </div>
</template>

<style scoped>
.offers-wrap {
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
</style>
