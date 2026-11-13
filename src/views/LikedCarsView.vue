<script setup>
// Liked cars page - /liked
// OWNER: Yati
// Cars the user liked (likes.php?user_id=), shown with Lai's CarCard and a LikeButton to unlike.
// Sorting is a computed list, so changing the dropdown re-orders without calling the API again.
import { computed, ref, watch } from 'vue'
import CarCard from '../components/CarCard.vue'
import LikeButton from '../components/LikeButton.vue'
import AppIcon from '../components/AppIcon.vue'
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
  <section class="container py-4">
    <div v-if="!currentUser" class="alert alert-info" role="alert">
      <h1 class="h4">Please log in to see your liked cars</h1>
      <router-link :to="{ name: 'login', query: { redirect: '/liked' } }" class="alert-link">
        Log in
      </router-link>
    </div>

    <template v-else>
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
          <h1 class="mb-1">Liked cars</h1>
          <p v-if="cars.length" class="text-muted mb-0">
            {{ cars.length }} {{ cars.length === 1 ? 'car' : 'cars' }} saved &middot; total
            {{ formatPrice(totalValue) }}
          </p>
        </div>
        <div v-if="cars.length > 1" class="d-flex align-items-center gap-2">
          <label for="liked-sort" class="form-label mb-0 text-nowrap">Sort by</label>
          <select id="liked-sort" v-model="sortBy" class="form-select form-select-sm">
            <option v-for="(sort, key) in SORTS" :key="key" :value="key">{{ sort.label }}</option>
          </select>
        </div>
      </div>

      <div v-if="removedMessage" class="alert alert-secondary alert-dismissible" role="status">
        {{ removedMessage }}
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="removedMessage = ''"
        ></button>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading your liked cars...</span>
        </div>
      </div>

      <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
        {{ errorMessage }}
        <button
          type="button"
          class="btn btn-sm btn-outline-danger ms-2"
          @click="load(currentUser.user_id)"
        >
          Try again
        </button>
      </div>

      <div v-else-if="!cars.length" class="dd-empty">
        <span class="dd-icon-circle"><AppIcon name="heart" :size="28" /></span>
        <h2>No liked cars yet</h2>
        <p class="text-muted">Tap the heart on a car you're interested in to save it here.</p>
        <router-link :to="{ name: 'cars' }" class="btn btn-primary">Browse cars</router-link>
      </div>

      <div v-else class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
        <div v-for="car in sortedCars" :key="car.listing_id" class="col d-flex flex-column">
          <CarCard :car="car" class="flex-grow-1" />
          <div class="d-flex justify-content-between align-items-center mt-2">
            <span
              v-if="car.status !== 'available'"
              class="dd-status"
              :class="car.status === 'sold' ? 'dd-status-sold' : 'dd-status-reserved'"
            >
              {{ car.status === 'sold' ? 'Sold' : 'Reserved' }}
            </span>
            <span v-else></span>
            <LikeButton
              :listing-id="car.listing_id"
              :likes="car.likes"
              :liked="true"
              @change="onLikeChange(car, $event)"
            />
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
