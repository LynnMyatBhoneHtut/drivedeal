<script setup>
import { ref, computed, watch } from 'vue'
import Pagination from '../components/Pagination.vue'
import CarCard from '../components/CarCard.vue'
import SearchBar from '../components/SearchBar.vue'
import SmartMatch from '../components/SmartMatch.vue'

// Temporary sample data
const cars = [
  {
    listing_id: 1,
    make: 'Toyota',
    model: 'Corolla Cross',
    year: 2022,
    price: 105000,
    mileage_km: 35000,
    image_path: ''
  },
  {
    listing_id: 2,
    make: 'Honda',
    model: 'Civic',
    year: 2021,
    price: 89000,
    mileage_km: 48000,
    image_path: ''
  },
  {
    listing_id: 3,
    make: 'Perodua',
    model: 'Myvi',
    year: 2023,
    price: 48000,
    mileage_km: 18000,
    image_path: ''
  }
]

// Search and sorting preferences
const filters = ref({
  make: '',
  minPrice: '',
  maxPrice: '',
  sort: 'price_asc'
})
const page = ref(1)

// Use 2 cars per page for testing.
// We will change this to 6 for the final website.
const perPage = 2
// Automatically update results when filters change
const filteredCars = computed(() => {
  const search = filters.value.make.toLowerCase()

  const minPrice =
    filters.value.minPrice === ''
      ? 0
      : Number(filters.value.minPrice)

  const maxPrice =
    filters.value.maxPrice === ''
      ? Infinity
      : Number(filters.value.maxPrice)

  const results = cars.filter(car => {
    const carName =
      `${car.make} ${car.model}`.toLowerCase()

    return (
      carName.includes(search) &&
      car.price >= minPrice &&
      car.price <= maxPrice
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

const paginatedCars = computed(() => {
  const start = (page.value - 1) * perPage

  return filteredCars.value.slice(
    start,
    start + perPage
  )
})

// Return to page 1 whenever the user changes a filter.
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
    <SmartMatch :cars="cars" />
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
      No cars match your search. Try adjusting your filters.
    </div>
    <Pagination v-model:page="page" :total="filteredCars.length" :per-page="perPage" />
  </section>
</template>
