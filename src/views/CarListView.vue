<script setup>
// Browse cars page - /cars
// OWNER: Lai (blue redesign layout from design/DESIGN.md).
// Loads every listing once (perPage 100) for the grid and Smart Match, then filters on the page.
// Filters can arrive in the URL from Home: /cars?make=Honda&body_type=SUV&minPrice=30000&maxPrice=80000&sort=year_desc
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import Pagination from '../components/Pagination.vue'
import CarCard from '../components/CarCard.vue'
import SearchBar from '../components/SearchBar.vue'
import SmartMatch from '../components/SmartMatch.vue'
import AppIcon from '../components/AppIcon.vue'
import { apiGet } from '../services/api'

const route = useRoute()

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
    errorMessage.value = error.message || 'Unable to load car listings.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCars()
})

// Search and sorting preferences
const SORTS = ['price_asc', 'price_desc', 'year_desc']
const BODY_TYPES = ['Sedan', 'Hatchback', 'SUV', 'MPV', 'Pickup']

const filters = ref({
  make: '',
  body_type: '',
  minPrice: '',
  maxPrice: '',
  sort: 'price_asc'
})

/** A price from the URL, or '' when it is missing or not a number. */
function priceFromQuery(value) {
  const number = Number(value)
  return value !== undefined && value !== '' && Number.isFinite(number) && number >= 0 ? number : ''
}

// Read filters from the URL (Home search, make pills, body type tiles)
watch(
  () => route.query,
  (query) => {
    const bodyType = BODY_TYPES.find(
      (type) => type.toLowerCase() === String(query.body_type || '').toLowerCase()
    )
    filters.value = {
      make: typeof query.make === 'string' ? query.make.trim() : '',
      body_type: bodyType || '',
      minPrice: priceFromQuery(query.minPrice),
      maxPrice: priceFromQuery(query.maxPrice),
      sort: SORTS.includes(query.sort) ? query.sort : 'price_asc'
    }
  },
  { immediate: true }
)

const page = ref(1)
const perPage = 6

// Filter and sort the cars retrieved from the API
const filteredCars = computed(() => {
  const search = String(filters.value.make || '').toLowerCase()
  const bodyType = String(filters.value.body_type || '').toLowerCase()

  const minPrice = filters.value.minPrice === '' ? 0 : Number(filters.value.minPrice)

  const maxPrice = filters.value.maxPrice === '' ? Infinity : Number(filters.value.maxPrice)

  const results = cars.value.filter((car) => {
    const carName = `${car.make} ${car.model}`.toLowerCase()

    return (
      carName.includes(search) &&
      (!bodyType || String(car.body_type || '').toLowerCase() === bodyType) &&
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

  return filteredCars.value.slice(start, start + perPage)
})

// Reset pagination whenever filters change
watch(
  filters,
  () => {
    page.value = 1
  },
  { deep: true }
)

// ---- Active filter chips ----
function formatPrice(value) {
  return `RM ${Number(value).toLocaleString('en-MY')}`
}

const activeFilters = computed(() => {
  const chips = []
  const f = filters.value
  if (f.make) chips.push({ key: 'make', label: `“${f.make}”` })
  if (f.body_type) chips.push({ key: 'body_type', label: f.body_type })
  if (f.minPrice !== '') chips.push({ key: 'minPrice', label: `From ${formatPrice(f.minPrice)}` })
  if (f.maxPrice !== '') chips.push({ key: 'maxPrice', label: `Up to ${formatPrice(f.maxPrice)}` })
  return chips
})

function removeFilter(key) {
  filters.value[key] = ''
}

function clearFilters() {
  filters.value = { ...filters.value, make: '', body_type: '', minPrice: '', maxPrice: '' }
}

// Mobile: filters open in a panel
const filtersOpen = ref(false)

const resultsText = computed(() => {
  const n = filteredCars.value.length
  return `${n} ${n === 1 ? 'car' : 'cars'} found`
})
</script>

<template>
  <div>
    <header class="dd-page-header">
      <div class="container">
        <nav aria-label="Breadcrumb">
          <ol class="breadcrumb dd-breadcrumb">
            <li class="breadcrumb-item"><router-link :to="{ name: 'home' }">Home</router-link></li>
            <li class="breadcrumb-item active" aria-current="page">Browse cars</li>
          </ol>
        </nav>
        <h1 class="dd-page-title">Browse cars</h1>
        <p class="text-muted mb-0 mt-1" role="status">
          {{
            loading ? 'Loading cars...' : errorMessage ? 'Cars could not be loaded' : resultsText
          }}
        </p>
      </div>
    </header>

    <section class="container py-4 py-lg-5">
      <!-- Loading state -->
      <div v-if="loading" class="row g-4" aria-busy="true">
        <div class="col-12 col-lg-4 col-xl-3 d-none d-lg-block">
          <div class="dd-skeleton" style="height: 420px"></div>
        </div>
        <div class="col-12 col-lg-8 col-xl-9">
          <div class="row g-4">
            <div v-for="n in 6" :key="n" class="col-12 col-sm-6 col-xl-4">
              <div class="dd-skeleton" style="height: 340px"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- API error state -->
      <div
        v-else-if="errorMessage"
        class="alert alert-danger d-flex flex-wrap align-items-center gap-3"
        role="alert"
      >
        <span>Unable to load cars: {{ errorMessage }}</span>
        <button type="button" class="btn btn-outline-danger btn-sm" @click="loadCars">
          Try again
        </button>
      </div>

      <template v-else>
        <!-- Individual HD feature -->
        <SmartMatch :cars="cars" />

        <div class="row g-4">
          <!-- Filters -->
          <aside class="col-12 col-lg-4 col-xl-3" aria-label="Filters">
            <button
              type="button"
              class="btn btn-outline-primary w-100 d-lg-none"
              aria-controls="filterPanel"
              :aria-expanded="filtersOpen ? 'true' : 'false'"
              @click="filtersOpen = !filtersOpen"
            >
              <AppIcon name="sliders" :size="18" />
              {{ filtersOpen ? 'Hide filters' : 'Filters' }}
              <span v-if="activeFilters.length" class="filter-count">{{
                activeFilters.length
              }}</span>
            </button>
            <div id="filterPanel" class="filter-panel" :class="{ 'is-open': filtersOpen }">
              <SearchBar v-model="filters" />
            </div>
          </aside>

          <!-- Results -->
          <div class="col-12 col-lg-8 col-xl-9">
            <div class="results-bar">
              <div class="chips" aria-label="Active filters">
                <button
                  v-for="chip in activeFilters"
                  :key="chip.key"
                  type="button"
                  class="dd-chip dd-chip-active"
                  :aria-label="`Remove filter ${chip.label}`"
                  @click="removeFilter(chip.key)"
                >
                  {{ chip.label }}
                  <AppIcon name="x" :size="14" />
                </button>
                <span v-if="!activeFilters.length" class="text-muted small">
                  Showing all cars
                </span>
              </div>
              <div class="d-flex align-items-center gap-2 ms-auto">
                <label for="carSort" class="form-label mb-0 text-nowrap">Sort by</label>
                <select id="carSort" v-model="filters.sort" class="form-select sort-select">
                  <option value="price_asc">Price: low to high</option>
                  <option value="price_desc">Price: high to low</option>
                  <option value="year_desc">Year: newest first</option>
                </select>
              </div>
            </div>

            <div v-if="filteredCars.length" class="row g-4">
              <div
                v-for="car in paginatedCars"
                :key="car.listing_id"
                class="col-12 col-sm-6 col-xl-4"
              >
                <CarCard :car="car" />
              </div>
            </div>

            <div v-else class="dd-empty">
              <span class="dd-icon-circle"><AppIcon name="car" :size="28" /></span>
              <h2>No cars match your filters</h2>
              <p>Try a different make, body type or price range.</p>
              <button type="button" class="btn btn-primary" @click="clearFilters">
                Clear filters
              </button>
            </div>

            <Pagination v-model:page="page" :total="filteredCars.length" :per-page="perPage" />
          </div>
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.results-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem 1rem;
  margin-bottom: 1.25rem;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.sort-select {
  width: auto;
  min-width: 190px;
}

.filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: var(--dd-radius-pill);
  background: var(--dd-primary);
  color: var(--dd-white);
  font-size: 0.75rem;
}

/* Mobile: panel opens under the Filters button */
.filter-panel {
  display: none;
  margin-top: 0.75rem;
}

.filter-panel.is-open {
  display: block;
}

@media (min-width: 992px) {
  .filter-panel {
    position: sticky;
    top: calc(var(--dd-navbar-h) + 16px);
    display: block;
    margin-top: 0;
  }
}

@media (max-width: 575.98px) {
  .sort-select {
    min-width: 0;
  }
}
</style>
