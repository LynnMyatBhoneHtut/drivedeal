<script setup>
// Liked cars page - /liked
// OWNER: Yati
// Cars the user liked (likes.php?user_id=), shown with Lai's CarCard; its heart button unlikes.
// Sorting is a computed list, so changing the dropdown re-orders without calling the API again.
import { computed, ref, watch } from 'vue'
import CarCard from '../components/CarCard.vue'
import AppIcon from '../components/AppIcon.vue'
import PageHeader from '../components/PageHeader.vue'
import { apiGet } from '../services/api'
import { currentUser } from '../services/auth'

const cars = ref([]) // in the order the API gives: most recently liked first
const loading = ref(false)
const errorMessage = ref('')
const sortBy = ref('recent')
const removedMessage = ref('')

const SORTS = {
  recent: { label: 'Recently liked', compare: null },
  price_asc: { label: 'Price: low to high', compare: (a, b) => a.price - b.price },
  price_desc: { label: 'Price: high to low', compare: (a, b) => b.price - a.price },
  year_desc: { label: 'Year: newest first', compare: (a, b) => b.year - a.year }
}

const sortedCars = computed(() => {
  const compare = SORTS[sortBy.value].compare
  return compare ? [...cars.value].sort(compare) : cars.value
})

const totalValue = computed(() => cars.value.reduce((sum, car) => sum + Number(car.price), 0))

function formatPrice(price) {
  return `RM ${Number(price).toLocaleString('en-MY')}`
}

async function load(userId) {
  cars.value = []
  if (!userId) return
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await apiGet('likes.php', { user_id: userId })
    cars.value = data.cars || []
  } catch (e) {
    errorMessage.value = e.message
  } finally {
    loading.value = false
  }
}

// Load now, and again if a different user logs in
watch(() => currentUser.value && currentUser.value.user_id, load, { immediate: true })

/** Unliked from this page -> take the car off the list. */
function onLikeChange(car, { liked, likes }) {
  if (liked) {
    car.likes = likes
    return
  }
  cars.value = cars.value.filter((item) => item.listing_id !== car.listing_id)
  removedMessage.value = `${car.make} ${car.model} was removed from your liked cars.`
}
</script>

<template>
  <div>
    <PageHeader
      title="Liked cars"
      :crumbs="currentUser ? [{ label: 'Profile', to: { name: 'profile' } }] : []"
    >
      <template v-if="currentUser && cars.length" #subtitle>
        {{ cars.length }} {{ cars.length === 1 ? 'car' : 'cars' }} saved &middot; total
        {{ formatPrice(totalValue) }}
      </template>
      <template v-if="currentUser && cars.length > 1" #actions>
        <div class="d-flex align-items-center gap-2">
          <label for="liked-sort" class="form-label mb-0 text-nowrap">Sort by</label>
          <select id="liked-sort" v-model="sortBy" class="form-select">
            <option v-for="(sort, key) in SORTS" :key="key" :value="key">{{ sort.label }}</option>
          </select>
        </div>
      </template>
    </PageHeader>

    <section class="container py-4 py-lg-5">
      <div v-if="!currentUser" class="dd-empty">
        <span class="dd-icon-circle"><AppIcon name="heart" :size="28" /></span>
        <h2>Please sign in to see your liked cars</h2>
        <router-link
          :to="{ name: 'login', query: { redirect: '/liked' } }"
          class="btn btn-primary mt-2"
        >
          Sign in
        </router-link>
      </div>

      <template v-else>
        <div v-if="removedMessage" class="alert alert-info alert-dismissible" role="status">
          {{ removedMessage }}
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="removedMessage = ''"
          ></button>
        </div>

        <div v-if="loading" class="row g-4" aria-busy="true">
          <div v-for="n in 3" :key="n" class="col-12 col-sm-6 col-lg-4">
            <div class="dd-skeleton" style="height: 340px"></div>
          </div>
          <span class="visually-hidden" role="status">Loading your liked cars...</span>
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

        <div v-else-if="!cars.length" class="dd-empty">
          <span class="dd-icon-circle"><AppIcon name="heart" :size="28" /></span>
          <h2>No liked cars yet</h2>
          <p>Tap the heart on a car you're interested in to save it here.</p>
          <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
        </div>

        <div v-else class="row g-4">
          <div v-for="car in sortedCars" :key="car.listing_id" class="col-12 col-sm-6 col-lg-4">
            <CarCard :car="car" :liked="true" @like-change="onLikeChange(car, $event)" />
          </div>
        </div>
      </template>
    </section>
  </div>
</template>
