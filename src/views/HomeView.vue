<script setup>
import { ref, onMounted } from 'vue'
import CarCard from '../components/CarCard.vue'
import { apiGet } from '../services/api'

// Keep the existing hero image
const heroImage = `${import.meta.env.BASE_URL}images/hero-car.jpg`

// Featured vehicles retrieved from Feenix MySQL
const featuredCars = ref([])
const loading = ref(true)
const errorMessage = ref('')

// Load the three newest non-sold listings
async function loadFeaturedCars() {
  loading.value = true
  errorMessage.value = ''

  try {
    const data = await apiGet('listings.php', {
      sort: 'newest',
      page: 1,
      perPage: 3
    })

    if (!Array.isArray(data.cars)) {
      throw new Error('Invalid car listing data received.')
    }

    featuredCars.value = data.cars
  } catch (error) {
    errorMessage.value =
      error.message || 'Unable to load featured cars.'
  } finally {
    loading.value = false
  }
}

// Fetch car listings when the Home page opens
onMounted(() => {
  loadFeaturedCars()
})
</script>

<template>
  <div>
    <!-- Hero section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center g-4">

          <!-- Left side: Welcome text -->
          <div class="col-12 col-lg-7">
            <p class="hero-label mb-3">
              WELCOME TO DRIVEDEAL
            </p>

            <h1 class="hero-title fw-bold mb-3">
              Find Your Next Car
              <span class="hero-highlight">
                With Confidence.
              </span>
            </h1>

            <p class="hero-description mb-4">
              Explore cars that match your needs and budget.
              Browse available listings, compare options,
              and connect with sellers in one place.
            </p>

            <div class="d-flex flex-wrap gap-3">
              <router-link :to="{ name: 'cars' }" class="btn btn-primary btn-lg px-4">
                Browse Cars
              </router-link>

              <router-link :to="{ name: 'sell' }" class="btn btn-outline-primary btn-lg px-4">
                Sell a Car
              </router-link>
            </div>
          </div>

          <!-- Right side: Car photograph -->
          <div class="col-12 col-lg-5">
            <div class="hero-photo">
              <img
:src="heroImage" alt="Black sports car against a grey backdrop" class="hero-photo-img" width="900"
                height="600" />

              <div class="hero-photo-label dd-glass">
                DISCOVER YOUR NEXT DRIVE
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Featured cars -->
    <section class="container py-5">
      <div
class="d-flex flex-wrap justify-content-between
               align-items-center gap-3 mb-4">
        <div>
          <h2 class="fw-bold mb-2">Explore Our Cars</h2>
          <p class="text-muted mb-0">
            Take a look at some of the cars on DriveDeal.
          </p>
        </div>

        <router-link :to="{ name: 'cars' }" class="btn btn-outline-primary">
          View All Cars
        </router-link>
      </div>


      <!-- Loading state -->
      <div v-if="loading" class="alert alert-info" role="status">
        Loading featured cars...
      </div>

      <!-- Error state -->
      <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
        <p>{{ errorMessage }}</p>

        <button type="button" class="btn btn-outline-danger btn-sm" @click="loadFeaturedCars">
          Try Again
        </button>
      </div>

      <!-- Featured car listings -->
      <div v-else-if="featuredCars.length" class="row g-4">
        <div v-for="car in featuredCars" :key="car.listing_id" class="col-12 col-md-6 col-lg-4">
          <CarCard :car="car" />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="alert alert-info">
        No featured cars are available at the moment.
      </div>

    </section>

    <!-- How it works -->
    <section class="how-it-works py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">How DriveDeal Works</h2>
          <p class="text-muted">
            A simple way to explore, enquire and sell cars.
          </p>
        </div>

        <div class="row g-4">
          <div class="col-12 col-md-4">
            <div class="step-card text-center h-100">
              <div class="step-number">01</div>
              <h3 class="h5 fw-bold">Browse Cars</h3>
              <p class="text-muted mb-0">
                Explore car listings and narrow down
                your choices with search and filters.
              </p>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="step-card text-center h-100">
              <div class="step-number">02</div>
              <h3 class="h5 fw-bold">Find Your Match</h3>
              <p class="text-muted mb-0">
                View car details and compare the
                available options before deciding.
              </p>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="step-card text-center h-100">
              <div class="step-number">03</div>
              <h3 class="h5 fw-bold">Contact the Seller</h3>
              <p class="text-muted mb-0">
                Found a car you like? Send an enquiry
                to learn more from the seller.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Bottom call to action -->
    <section class="container py-5 text-center">
      <h2 class="fw-bold mb-3">
        Ready to Find Your Next Car?
      </h2>

      <p class="text-muted mb-4">
        Start exploring the available listings today.
      </p>

      <router-link :to="{ name: 'cars' }" class="btn btn-primary btn-lg px-5">
        Explore Cars
      </router-link>
    </section>
  </div>
</template>

<style scoped>
/* Hero section (rebuilt in Phase 2) */
.hero-section {
  background: var(--dd-hero-bg);
  padding: 85px 0;
}

.hero-label {
  color: var(--dd-primary);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.hero-title {
  font-size: clamp(2.3rem, 5vw, 3.8rem);
  line-height: 1.15;
}

.hero-highlight {
  display: block;
  color: var(--dd-primary);
}

.hero-description {
  max-width: 560px;
  color: var(--dd-muted);
  font-size: 1.1rem;
  line-height: 1.7;
}

.hero-photo {
  position: relative;
  overflow: hidden;
  border-radius: var(--dd-radius-lg);
  background: var(--dd-surface);
  box-shadow: var(--dd-shadow);
}

.hero-photo-img {
  display: block;
  width: 100%;
  height: 350px;
  object-fit: cover;
}

.hero-photo-label {
  position: absolute;
  bottom: 18px;
  left: 18px;
  right: 18px;
  padding: 12px 16px;
  border-radius: var(--dd-radius-sm);
  color: var(--dd-ink);
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.1em;
}

.how-it-works {
  background: var(--dd-surface);
}

.step-card {
  padding: 30px 22px;
  background: var(--dd-white);
  border: 1px solid var(--dd-line);
  border-radius: var(--dd-radius);
}

.step-number {
  font-size: 30px;
  font-weight: 800;
  color: var(--dd-primary);
  margin-bottom: 15px;
}

@media (max-width: 767px) {
  .hero-section {
    padding: 55px 0;
  }

  .hero-photo-img {
    height: 260px;
  }
}
</style>
