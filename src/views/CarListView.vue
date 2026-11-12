<script setup>
import { ref, computed, watch, onMounted } from 'vue'

import Pagination from '../components/Pagination.vue'
import CarCard from '../components/CarCard.vue'
import SearchBar from '../components/SearchBar.vue'
import SmartMatch from '../components/SmartMatch.vue'
import { apiGet } from '../services/api'

// Car data retrieved from Feenix MySQL through the PHP API
const cars = ref([])
const loading = ref(true)
const errorMessage = ref('')

// Load all available listings for Browse Cars and Smart Match
async function loadCars() {
  loading.value = true
  errorMessage.value = ''

  try {
    const data = await apiGet('listings.php', {
      perPage: 100
    })

    if (!Array.isArray(data.cars)) {
      throw new Error('Invalid car listing data received.')
    }

    cars.value = data.cars
  } catch (error) {
    errorMessage.value =
      error.message || 'Unable to load car listings.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCars()
})

// Search and sorting preferences
const filters = ref({
  make: '',
  minPrice: '',
  maxPrice: '',
  sort: 'price_asc'
})

const page = ref(1)
const perPage = 6

// Filter and sort the cars retrieved from the API
const filteredCars = computed(() => {
  const search = String(
    filters.value.make || ''
  ).toLowerCase()

  const minPrice =
    filters.value.minPrice === ''
      ? 0
      : Number(filters.value.minPrice)

  const maxPrice =
    filters.value.maxPrice === ''
      ? Infinity
      : Number(filters.value.maxPrice)

  const results = cars.value.filter(car => {
    const carName =
      `${car.make} ${car.model}`.toLowerCase()

    return (
      carName.includes(search) &&
      Number(car.price) >= minPrice &&
      Number(car.price) <= maxPrice
    )
  })

  if (filters.value.sort === 'price_asc') {
    results.sort((a, b) => a.price - b.price)
  } else if (filters.value.sort === 'price_desc') {
    results.sort((a, b) => b.price - a.price)
  } else if (filters.value.sort === 'year_desc') {
    results.sort((a, b) => b.year - a.year)
  }

  return results
})

// Display six cars per page
const paginatedCars = computed(() => {
  const start = (page.value - 1) * perPage

  return filteredCars.value.slice(
    start,
    start + perPage
  )
})

// Reset pagination whenever filters change
watch(filters, () => {
  page.value = 1
}, { deep: true })
</script>

<template>
  <section class="container py-5">
    <div class="mb-4">
      <h1 class="fw-bold">Browse Cars</h1>
      <p class="text-muted">
        Explore available cars and find the right one for you.
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="alert alert-info" role="status">
      Loading car listings...
    </div>

    <!-- API error state -->
    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      Unable to load cars: {{ errorMessage }}

      <button type="button" class="btn btn-outline-danger btn-sm ms-3" @click="loadCars">
        Try Again
      </button>
    </div>

    <template v-else>
      <!-- Individual HD feature -->
      <SmartMatch :cars="cars" />

      <!-- Normal car search and sorting -->
      <SearchBar v-model="filters" />

      <p class="text-muted" role="status">
        Showing {{ filteredCars.length }}
        {{ filteredCars.length === 1 ? 'car' : 'cars' }}
      </p>

      <div v-if="filteredCars.length" class="row g-4">
        <div v-for="car in paginatedCars" :key="car.listing_id" class="col-12 col-md-6 col-lg-4">
          <CarCard :car="car" />
        </div>
      </div>

      <div v-else class="alert alert-info">
        No cars match your search.
        Try adjusting your filters.
      </div>

      <Pagination v-model:page="page" :total="filteredCars.length" :per-page="perPage" />
    </template>
  </section>
</template>
