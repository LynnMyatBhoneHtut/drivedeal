<script setup>
// Home page - /
// OWNER: Lai (blue redesign layout from design/DESIGN.md).
// One listings.php call (perPage 100, newest first) gives the featured cars, the makes and
// every live number on the page - nothing here is made up.
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import CarCard from '../components/CarCard.vue'
import { apiGet } from '../services/api'

const router = useRouter()

const heroImage = `${import.meta.env.BASE_URL}images/hero-car.jpg`
const heroImageSmall = `${import.meta.env.BASE_URL}images/hero-car-960.jpg`

const cars = ref([])
const total = ref(0)
const makes = ref([])
const loading = ref(true)
const errorMessage = ref('')

async function loadCars() {
  loading.value = true
  errorMessage.value = ''

  try {
    const data = await apiGet('listings.php', { sort: 'newest', page: 1, perPage: 100 })

    if (!Array.isArray(data.cars)) {
      throw new Error('Invalid car listing data received.')
    }

    cars.value = data.cars
    total.value = Number(data.total) || data.cars.length
    makes.value = Array.isArray(data.makes) ? data.makes : []
  } catch (error) {
    errorMessage.value = error.message || 'Unable to load cars.'
  } finally {
    loading.value = false
  }
}

onMounted(loadCars)

// ---- Live numbers ----
const featuredCars = computed(() => cars.value.slice(0, 4))

const averagePrice = computed(() => {
  if (!cars.value.length) return 0
  const sum = cars.value.reduce((acc, car) => acc + Number(car.price || 0), 0)
  return Math.round(sum / cars.value.length)
})

const addedThisWeek = computed(() => {
  const weekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000
  return cars.value.filter((car) => {
    const created = new Date(String(car.created_at || '').replace(' ', 'T')).getTime()
    return Number.isFinite(created) && created >= weekAgo
  }).length
})

function formatNumber(value) {
  return Number(value).toLocaleString('en-MY')
}

function shownNumber(value) {
  return loading.value || errorMessage.value ? '–' : formatNumber(value)
}

const stats = computed(() => [
  { icon: 'car', value: shownNumber(total.value), label: 'Cars for sale' },
  { icon: 'tag', value: shownNumber(makes.value.length), label: 'Car makes' },
  {
    icon: 'gauge',
    value: loading.value || errorMessage.value ? '–' : `RM ${formatNumber(averagePrice.value)}`,
    label: 'Average asking price'
  },
  { icon: 'sparkles', value: shownNumber(addedThisWeek.value), label: 'Added this week' }
])

// ---- Hero search card ----
const BODY_TYPES = ['Sedan', 'Hatchback', 'SUV', 'MPV', 'Pickup']
const PRICE_STEPS = [30000, 50000, 80000, 120000, 200000]

const search = reactive({ make: '', body_type: '', minPrice: '', maxPrice: '' })

function runSearch() {
  const query = {}
  if (search.make.trim()) query.make = search.make.trim()
  if (search.body_type) query.body_type = search.body_type
  if (search.minPrice !== '') query.minPrice = String(search.minPrice)
  if (search.maxPrice !== '') query.maxPrice = String(search.maxPrice)
  router.push({ name: 'cars', query })
}

const bodyTiles = BODY_TYPES.map((type) => ({ type, icon: type.toLowerCase() }))

const steps = [
  {
    icon: 'search',
    title: 'Browse & filter',
    text: 'Search by make, body type and price to find cars that fit.'
  },
  {
    icon: 'sparkles',
    title: 'Like & compare',
    text: 'Save favourites and let Smart Match rank cars for you.'
  },
  {
    icon: 'message',
    title: 'Contact the seller',
    text: 'Send the owner a message straight from the listing.'
  },
  {
    icon: 'deal',
    title: 'Agree the deal',
    text: 'Arrange a viewing and settle the price together.'
  }
]

const features = [
  {
    icon: 'users',
    title: 'Real sellers, real contact',
    text: 'Every enquiry goes straight to the person selling the car.'
  },
  {
    icon: 'tag',
    title: 'Free to list your car',
    text: 'Add photos and details in minutes, at no cost.'
  },
  {
    icon: 'sparkles',
    title: 'Smart Match recommendations',
    text: 'Tell us your budget and priorities, and get a ranked shortlist.'
  },
  {
    icon: 'heart',
    title: 'Save cars you like',
    text: 'Keep a list of favourites and come back to them any time.'
  }
]
</script>

<template>
  <div class="home">
    <!-- 1. Hero -->
    <section class="hero">
      <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
          <div class="col-12 col-lg-6">
            <span class="dd-eyebrow">Malaysia's friendly car marketplace</span>
            <h1 class="hero-title">
              Find your next car, make a <em class="text-primary">fair deal</em>
            </h1>
            <p class="hero-sub">
              Browse used cars from real owners, compare them side by side and talk to the seller
              directly.
            </p>

            <div class="d-flex flex-wrap gap-2 gap-sm-3 mb-4">
              <router-link :to="{ name: 'cars' }" class="btn btn-primary btn-lg dd-btn-arrow">
                Browse cars
                <span class="dd-arrow-box"><AppIcon name="arrow-right" :size="18" /></span>
              </router-link>
              <router-link :to="{ name: 'sell' }" class="btn btn-outline-primary btn-lg">
                Sell your car
              </router-link>
            </div>

            <ul class="hero-stats" aria-label="DriveDeal at a glance">
              <li>
                <span class="dd-icon-circle"><AppIcon name="car" /></span>
                <span>
                  <strong>{{ shownNumber(total) }}</strong>
                  <small>Cars for sale</small>
                </span>
              </li>
              <li>
                <span class="dd-icon-circle"><AppIcon name="tag" /></span>
                <span>
                  <strong>{{ shownNumber(makes.length) }}</strong>
                  <small>Car makes</small>
                </span>
              </li>
              <li>
                <span class="dd-icon-circle"><AppIcon name="message" /></span>
                <span>
                  <strong>Direct</strong>
                  <small>Chat with sellers</small>
                </span>
              </li>
            </ul>
          </div>

          <div class="col-12 col-lg-6">
            <div class="hero-frame">
              <img
                :src="heroImage"
                :srcset="`${heroImageSmall} 960w, ${heroImage} 1920w`"
                sizes="(min-width: 992px) 560px, 100vw"
                width="1920"
                height="1080"
                fetchpriority="high"
                alt="A silver sports car driving along a mountain road"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. Floating search card -->
    <div class="container search-wrap">
      <form class="search-card" role="search" aria-label="Find a car" @submit.prevent="runSearch">
        <div class="search-head">
          <AppIcon name="search" :size="18" />
          <span>Search cars</span>
        </div>
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-6 col-lg-4">
            <label for="home-make" class="form-label">Make or model</label>
            <input
              id="home-make"
              v-model="search.make"
              type="search"
              class="form-control"
              placeholder="e.g. Honda City"
              list="home-makes"
            />
            <datalist id="home-makes">
              <option v-for="make in makes" :key="make" :value="make"></option>
            </datalist>
          </div>
          <div class="col-12 col-md-6 col-lg-2">
            <label for="home-body" class="form-label">Body type</label>
            <select id="home-body" v-model="search.body_type" class="form-select">
              <option value="">Any</option>
              <option v-for="type in BODY_TYPES" :key="type" :value="type">{{ type }}</option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <label for="home-min" class="form-label">Min price</label>
            <select id="home-min" v-model="search.minPrice" class="form-select">
              <option value="">Any</option>
              <option v-for="step in PRICE_STEPS" :key="step" :value="step">
                RM {{ step / 1000 }}k
              </option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <label for="home-max" class="form-label">Max price</label>
            <select id="home-max" v-model="search.maxPrice" class="form-select">
              <option value="">Any</option>
              <option v-for="step in PRICE_STEPS" :key="step" :value="step">
                RM {{ step / 1000 }}k
              </option>
            </select>
          </div>
          <div class="col-12 col-lg-2">
            <button type="submit" class="btn btn-primary w-100 search-btn">
              <AppIcon name="search" :size="18" />
              Search
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- 3. Featured cars -->
    <section class="dd-section pt-5">
      <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 dd-section-head">
          <div>
            <span class="dd-eyebrow">Just listed</span>
            <h2 class="mb-0">Fresh on DriveDeal</h2>
          </div>
          <router-link :to="{ name: 'cars' }" class="dd-link-arrow">
            View all cars <AppIcon name="arrow-right" :size="18" />
          </router-link>
        </div>

        <div v-if="loading" class="row g-4" aria-busy="true">
          <div v-for="n in 4" :key="n" class="col-12 col-sm-6 col-lg-3">
            <div class="dd-card overflow-hidden">
              <div class="dd-skeleton skeleton-photo"></div>
              <div class="p-3">
                <div class="dd-skeleton skeleton-line w-75 mb-2"></div>
                <div class="dd-skeleton skeleton-line w-50 mb-3"></div>
                <div class="dd-skeleton skeleton-line w-100"></div>
              </div>
            </div>
          </div>
          <span class="visually-hidden" role="status">Loading cars...</span>
        </div>

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

        <div v-else-if="featuredCars.length" class="row g-4">
          <div v-for="car in featuredCars" :key="car.listing_id" class="col-12 col-sm-6 col-lg-3">
            <CarCard :car="car" />
          </div>
        </div>

        <div v-else class="dd-empty">
          <span class="dd-icon-circle"><AppIcon name="car" :size="28" /></span>
          <h3>No cars for sale yet</h3>
          <p>Be the first to list a car on DriveDeal.</p>
          <router-link :to="{ name: 'sell' }" class="btn btn-primary">Sell your car</router-link>
        </div>
      </div>
    </section>

    <!-- 4. Browse by make / body type -->
    <section class="pb-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-12 col-lg-5">
            <div class="dd-panel h-100">
              <h2 class="h3 mb-1">Browse by make</h2>
              <p class="text-muted small mb-3">Every make currently on DriveDeal.</p>
              <div v-if="loading" class="d-flex flex-wrap gap-2" aria-hidden="true">
                <span v-for="n in 6" :key="n" class="dd-skeleton chip-skeleton"></span>
              </div>
              <ul v-else-if="makes.length" class="make-list">
                <li v-for="make in makes" :key="make">
                  <router-link :to="{ name: 'cars', query: { make } }" class="dd-chip">
                    {{ make }}
                  </router-link>
                </li>
              </ul>
              <p v-else class="text-muted mb-0">No makes to show yet.</p>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <div class="dd-panel-blue h-100">
              <span class="panel-shape" aria-hidden="true"></span>
              <h2 class="h3 mb-1">Browse by body type</h2>
              <p class="panel-sub small mb-3">Pick a shape to see matching cars.</p>
              <ul class="body-tiles">
                <li v-for="tile in bodyTiles" :key="tile.type">
                  <router-link
                    :to="{ name: 'cars', query: { body_type: tile.type } }"
                    class="body-tile"
                  >
                    <AppIcon :name="tile.icon" :size="36" :stroke-width="1.5" />
                    <span>{{ tile.type }}</span>
                  </router-link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. How it works -->
    <section class="dd-section dd-section-surface">
      <div class="container">
        <div class="text-center dd-section-head">
          <span class="dd-eyebrow">How it works</span>
          <h2 class="mb-0">How DriveDeal works</h2>
          <span class="dd-title-bar" aria-hidden="true"></span>
        </div>
        <ol class="steps">
          <li v-for="(step, index) in steps" :key="step.title" class="step">
            <span class="step-icon"><AppIcon :name="step.icon" :size="24" /></span>
            <span class="dd-eyebrow mb-1">Step {{ index + 1 }}</span>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-text">{{ step.text }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- 6. Why DriveDeal -->
    <section class="dd-section">
      <div class="container">
        <div class="dd-section-head">
          <span class="dd-eyebrow">Why DriveDeal</span>
          <h2 class="mb-0">Buying and selling, made simple</h2>
        </div>
        <div class="row g-3 g-lg-4">
          <div v-for="feature in features" :key="feature.title" class="col-12 col-sm-6 col-lg-3">
            <div class="dd-feature-card">
              <span class="dd-feature-icon">
                <AppIcon :name="feature.icon" :size="28" :stroke-width="1.5" />
              </span>
              <h3>{{ feature.title }}</h3>
              <p>{{ feature.text }}</p>
            </div>
          </div>
        </div>

        <!-- 7. Stats band -->
        <ul class="dd-stats list-unstyled mt-4 mt-lg-5 mb-0" aria-label="DriveDeal in numbers">
          <li v-for="stat in stats" :key="stat.label" class="dd-stat">
            <span class="dd-icon-circle"><AppIcon :name="stat.icon" /></span>
            <span class="min-w-0">
              <span class="dd-stat-value">{{ stat.value }}</span>
              <span class="dd-stat-label">{{ stat.label }}</span>
            </span>
          </li>
        </ul>
      </div>
    </section>

    <!-- 8. CTA banner -->
    <section class="pb-5 mb-lg-4">
      <div class="container">
        <div class="cta dd-panel-blue">
          <span class="panel-shape" aria-hidden="true"></span>
          <div class="cta-inner">
            <div>
              <h2 class="mb-2">Selling your car? List it in minutes.</h2>
              <p class="mb-0 panel-sub">
                Add your car's details and photos, and buyers can contact you straight away.
              </p>
            </div>
            <router-link :to="{ name: 'sell' }" class="btn btn-light btn-lg dd-btn-arrow">
              Sell your car
              <span class="dd-arrow-box"><AppIcon name="arrow-right" :size="18" /></span>
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Hero */
.hero {
  padding: clamp(2.5rem, 1.5rem + 4vw, 5rem) 0 calc(48px + clamp(2rem, 1rem + 3vw, 3.5rem));
  background: var(--dd-hero-bg);
}

.hero-title {
  margin-bottom: 1rem;
}

.hero-title em {
  font-style: italic;
}

.hero-sub {
  max-width: 520px;
  margin-bottom: 1.75rem;
  font-size: 1.1rem;
  color: var(--dd-muted);
}

.hero-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem 1.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.hero-stats li {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.hero-stats strong {
  display: block;
  font-size: 1.1rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--dd-ink);
}

.hero-stats small {
  display: block;
  font-size: 0.8rem;
  color: var(--dd-muted);
}

.hero-frame {
  overflow: hidden;
  border-radius: var(--dd-radius-lg);
  background: var(--dd-surface);
  box-shadow: var(--dd-shadow-lg);
}

.hero-frame img {
  display: block;
  width: 100%;
  height: auto;
  aspect-ratio: 16 / 10;
  object-fit: cover;
}

/* Floating search card */
.search-wrap {
  position: relative;
  z-index: 2;
  margin-top: -48px;
}

.search-card {
  padding: 1.25rem;
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius);
  background: var(--dd-white);
  box-shadow: var(--dd-shadow-lg);
}

.search-head {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 0.45rem 0.9rem;
  border-radius: var(--dd-radius-sm);
  background: var(--dd-primary);
  color: var(--dd-white);
  font-size: 0.875rem;
  font-weight: 700;
}

.search-btn {
  min-height: 48px;
}

@media (min-width: 992px) {
  .search-card {
    padding: 1.5rem 1.75rem 1.75rem;
  }
}

/* Skeletons */
.skeleton-photo {
  aspect-ratio: 4 / 3;
  border-radius: 0;
}

.skeleton-line {
  height: 14px;
}

.chip-skeleton {
  width: 84px;
  height: 40px;
  border-radius: var(--dd-radius-pill);
}

/* Browse by */
.make-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.panel-sub {
  color: var(--dd-white-70);
}

.panel-shape {
  position: absolute;
  top: -80px;
  right: -60px;
  width: 260px;
  height: 260px;
  border-radius: 50%;
  background: var(--dd-white-15);
  pointer-events: none;
}

.body-tiles {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

@media (min-width: 576px) {
  .body-tiles {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

.body-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: 104px;
  padding: 0.85rem 0.5rem;
  border: 1px solid var(--dd-white-15);
  border-radius: var(--dd-radius);
  background: var(--dd-white-15);
  color: var(--dd-white);
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition:
    background-color 0.2s ease,
    transform 0.2s ease;
}

.body-tile:hover {
  background: var(--dd-white);
  color: var(--dd-primary-700);
}

.body-tile:focus-visible {
  outline-color: var(--dd-white);
  box-shadow: none;
}

@media (prefers-reduced-motion: no-preference) {
  .body-tile:hover {
    transform: translateY(-3px);
  }
}

/* Steps */
.steps {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.step {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.step-icon {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  margin-bottom: 0.9rem;
  border-radius: 50%;
  background: var(--dd-primary);
  color: var(--dd-white);
  box-shadow: 0 0 0 6px var(--dd-primary-50);
}

.step-title {
  margin-bottom: 0.35rem;
  font-size: 1.05rem;
}

.step-text {
  max-width: 240px;
  margin: 0;
  font-size: 0.925rem;
  color: var(--dd-muted);
}

@media (min-width: 768px) {
  .steps {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1.25rem;
  }

  /* Dotted connector between the circles */
  .step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 28px;
    left: calc(50% + 40px);
    right: calc(-50% + 40px);
    border-top: 2px dotted var(--dd-primary-100);
  }
}

/* CTA */
.cta {
  padding: clamp(1.75rem, 1rem + 3vw, 3rem);
}

.cta h2 {
  font-size: clamp(1.5rem, 1.1rem + 1.4vw, 2.1rem);
}

.cta-inner {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}

.min-w-0 {
  min-width: 0;
}
</style>
